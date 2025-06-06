<?php

namespace Deep\FormTool\Core;

use Closure;
use Deep\FormTool\Exceptions\FormToolException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

trait BaseImportExport
{
    protected $sampleData = null;
    protected $importDateFormat = 'd-M-Y';
    protected $importExcelFormat = 'dd-mmm-yyyy';

    protected $uniqueColumns = [];
    protected $importData = [];

    protected function setupImport()
    {
        $this->setup();

        $this->sampleData = null;
    }

    protected function setupExport()
    {
        $this->setupImport();
    }

    protected function setExportData()
    {
        return $this->crud->getModel()->getWhere(['deletedAt' => null]);
    }

    protected function setUnique($columns)
    {
        $this->uniqueColumns = Arr::wrap($columns);
    }

    public function import()
    {
        $data['title'] = $this->title;

        $data['route'] = $this->route;
        $data['menuActive'] = $this->route;

        return $this->render('form-tool::list.import.index', $data);
    }

    public function importStore()
    {
        $this->setupImport();

        $this->validateImport($insertData, $errors);
        $this->formatData($insertData, $errors);

        if ($errors) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong! There are some errors please resolve it first:',
                'errors' => $errors,
            ], 421);
        }

        $this->crud->getModel()->addMany($insertData);

        return response()->json([
            'status' => true,
            'message' => ($this->title ?? 'Data').' imported successfully!',
        ]);
    }

    protected function validateImport(&$insert, &$errors)
    {
        $request = request();

        //TODO: Validate unique columns, if exists in blueprint

        // Validating the .xlxs file
        $request->validate([
            'file' => ['bail', 'required', 'mimes:xlsx', function ($attribute, $value, Closure $fail) {
                $info = pathinfo($value->getClientOriginalName());
                if ('xlsx' != strtolower(trim($info['extension'] ?? null))) {
                    $fail('The :attribute must be a file of type: xlsx.');
                }
            }],
        ], [], ['file' => 'Upload XLSX File']);

        $headerRowCount = 1;

        // Slicing the header part
        $excelData = Excel::toArray([], $request->file('file'))[0] ?? [];
        $excelHeaderLabels = array_slice($excelData, 0, 1)[0] ?? [];
        $data = array_slice($excelData, $headerRowCount);

        // Get the header columns and inputs
        $headers = $this->getHeaders();
        $originalHeaderLabels = array_keys($headers);

        // Include unsupplied labels
        $excelHeaderLabels = array_merge($excelHeaderLabels, array_diff($originalHeaderLabels, $excelHeaderLabels));

        // Set custom messages
        $messages = [
            'unique' => 'The :attribute has already been taken: <b>:input</b>',
        ];

        // $niceDateFormat = config('form-tool.formatDate');

        // Get the validation from our Blueprint setup
        $validations = [];
        $attributes = [];
        $uniqueColumnValidations = $this->uniqueColumns;
        foreach ($headers as $input) {
            $rawValidations = $input->getValidations('store');
            $messages = array_merge($messages, $input->getValidationMessages());
            $validations[$input->getDbField()] = [];

            foreach ($rawValidations as  $val) {
                if ($val instanceof \Illuminate\Validation\Rules\Unique) {
                    $uniqueColumnValidations[] = $input->getDbField();
                } elseif (is_string($val) && false !== strpos($val, 'date_format:')) {
                    // $val = str_replace('date_format:'.$niceDateFormat, 'date_format:'.$this->importDateFormat, $val);

                    // $messages[$input->getDbField().'.date_format'] = sprintf(
                    //     'The :attribute does not match the format: %s (%s).',
                    //     date($this->importDateFormat, strtotime('25-08-2022 06:30 pm')),
                    //     $this->importExcelFormat
                    // );

                    // We are going to skip this validation, as excel keeps date in number format
                    continue;
                }

                $validations[$input->getDbField()][] = $val;
            }

            $attributes[$input->getDbField()] = $input->getLabel();
        }

        $uniqueColumnValidations = array_unique($uniqueColumnValidations);

        // Let's validate row by row
        $insert = [];
        $errors = [];
        $uniqueData = [];
        foreach ($data as $index => $row) {
            $rowData = [];
            $i = 0;
            foreach ($excelHeaderLabels as $headerLabel) {
                $input = $headers[$headerLabel] ?? null;
                if ($input) {
                    $rowData[$input->getDbField()] = $row[$i] ?? null;

                    $newValue = $input->beforeValidation($row[$i] ?? null);
                    if ($newValue !== null) {
                        $rowData[$input->getDbField()] = $newValue;
                    }
                }
                $i++;
            }

            $this->setImportData($rowData);

            // Merge with request helps to get the request data in the validation closure
            // If you want to get the request data in the validation closure like request()->input('name')
            $request->merge($rowData);

            $validator = Validator::make($rowData, $validations, $messages, $attributes);
            if ($validator->fails()) {
                $errors[$index + $headerRowCount + 1] = $validator->getMessageBag()->toArray();
            } else {
                $insert[] = $rowData;
            }

            foreach ($uniqueColumnValidations as $uniqueCol) {
                if (! isset($rowData[$uniqueCol])) {
                    continue;
                }

                if (in_array($rowData[$uniqueCol], $uniqueData[$uniqueCol] ?? [])) {
                    if (! isset($errors[$index + $headerRowCount + 1][$uniqueCol])) {
                        $errors[$index + $headerRowCount + 1][$uniqueCol] = [];
                    }

                    $errors[$index + $headerRowCount + 1][$uniqueCol][] = sprintf(
                        'The %s is repeated/duplicated in the file: <b>%s</b>',
                        $this->getHeaderLabel($uniqueCol),
                        $rowData[$uniqueCol]
                    );
                }

                if ($rowData[$uniqueCol]) {
                    $uniqueData[$uniqueCol][] = $rowData[$uniqueCol];
                }
            }
        }

        return ! $errors;
    }

    protected function formatData(&$data, &$errors)
    {
        if ($errors) {
            return false;
        }

        $headers = $this->getHeaders();
        $headerRowCount = 1;

        $model = $this->crud->getModel();

        $metaColumns = \config('form-tool.table_meta_columns');
        $createdBy = ($metaColumns['createdBy'] ?? 'createdBy') ?: 'createdBy';
        $createdAt = ($metaColumns['createdAt'] ?? 'createdAt') ?: 'createdAt';

        foreach ($data as $index => &$row) {
            foreach ($headers as $input) {
                $input->reset();
                $value = trim($row[$input->getDbField()]);

                if (strlen($value)) {
                    $response = $input->getImportValue($value);

                    if ($response === null) {
                        // We don't have map/appropriate data for the value
                        if (! isset($errors[$index + $headerRowCount + 1][$input->getDbField()])) {
                            $errors[$index + $headerRowCount + 1][$input->getDbField()] = [];
                        }

                        $errors[$index + $headerRowCount + 1][$input->getDbField()][] = sprintf(
                            'The %s have invalid data: <b>%s</b>',
                            $this->getHeaderLabel($input->getDbField()),
                            $row[$input->getDbField()]
                        );
                    } else {
                        $value = $response;
                    }
                } else {
                    $value = null;
                }

                $row[$input->getDbField()] = $value;
                $input->setValue($value);

                $response = $input->beforeStore((object) $row);
                if ($response !== null) {
                    $row[$input->getDbField()] = $response;
                }

                // Set default value
                if ($value === null && $input->getDefaultValue() !== null) {
                    $value = $input->getDefaultValue();

                    $row[$input->getDbField()] = $value;
                    $input->setValue($value);
                }
            }

            if ($model->isToken()) {
                $row[$model->getTokenCol()] = \Deep\FormTool\Support\Random::unique($model);
            }

            $row[$createdBy] = Auth::id();
            $row[$createdAt] = \date('Y-m-d H:i:s');
        }

        return ! $errors;
    }

    public function sample()
    {
        $this->setupImport();

        $this->crud->setImportSample($this->sampleData);

        $filename = $this->title.'_sample.csv';

        $headers = $this->getHeaders();

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_keys($headers));

            $data = [];
            foreach ($headers as $input) {
                $data[] = $input->getImportSample();
            }

            fputcsv($file, $data);

            fclose($file);
        };

        return $this->download($filename, $callback);
    }

    public function export()
    {
        $this->setupExport();

        return $this->doExport();
    }

    protected function doExport()
    {
        $filename = $this->title.'.csv';

        $resultData = $this->setExportData();

        $headers = $this->getHeaders();

        $callback = function () use ($resultData, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_keys($headers));

            foreach ($resultData as $row) {
                $data = [];
                foreach ($headers as $input) {
                    $data[] = $input->getExportValue($row->{$input->getDbField()} ?? null);
                }

                fputcsv($file, $data);
            }

            fclose($file);
        };

        return $this->download($filename, $callback);
    }

    public function getImportValue($column)
    {
        return $this->importData[$column] ?? null;
    }

    public function getImportData()
    {
        return $this->importData;
    }

    protected function setImportData($data, $id = null)
    {
        //$this->crud->setId($id);
        $this->importData = $data;
    }

    private function getHeaders()
    {
        if (! $this->sampleData) {
            return [[], []];
        }

        $headers = [];
        foreach ($this->sampleData as $col => $sample) {
            $input = $this->crud->getBluePrint()->getInputTypeByDbField($col);
            if (! $input) {
                throw new FormToolException(sprintf('Column "%s" not found in blue print!', $col));
            }

            if ($sample instanceof ImportConfig) {
                $label = $sample->getHeader();
            } else {
                $label = $this->createHeaderLabel($input->getLabel());
            }

            if (isset($headers[$label])) {
                throw new FormToolException(sprintf('Duplicate header label found: %s', $label));
            }

            $headers[$label] = $input;
        }

        return $headers;
    }

    private function getHeaderLabel($column)
    {
        $input = $this->crud->getBluePrint()->getInputTypeByDbField($column);
        if ($input) {
            return $this->createHeaderLabel($input->getLabel());
        }

        return $column;
    }

    private function createHeaderLabel($label)
    {
        $label = str_replace("'s", '', trim($label));
        $label = preg_replace('/[^A-Za-z0-9_]+/', '_', $label);

        return trim($label, '_');
    }

    private function download($filename, $callback)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream($callback, 200, $headers);
    }
}
