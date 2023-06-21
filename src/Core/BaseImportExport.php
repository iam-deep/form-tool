<?php

namespace Deep\FormTool\Core;

use Closure;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

trait BaseImportExport
{
    protected $sampleData = null;

    protected function setupImport()
    {
        $this->setup();

        $this->sampleData = null;
    }

    public function import()
    {
        $data['title'] = $this->title;

        $data['route'] = $this->route;

        return $this->render('form-tool::list.import.index', $data);
    }

    public function importStore()
    {
        $this->setupImport();

        if (! $this->validateImport($insertData, $errors)) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong! There are some errors please resolve it first:',
                'errors' => $errors,
            ], 421);
        }

        if (! $this->formatData($insertData, $errors)) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong! There are some errors please resolve it first:',
                'errors' => $errors,
            ], 421);
        }

        $this->crud->getModel()->addMany($insertData);

        return response()->json([
            'status' => true,
            'message' => 'Data imported successfully!',
        ]);
    }

    protected function validateImport(&$insert, &$errors)
    {
        $request = request();

        // Validating the .csv file
        $request->validate([
            'file' => ['bail', 'required', 'mimes:csv,txt', function ($attribute, $value, Closure $fail) {
                $info = pathinfo($value->getClientOriginalName());
                if ('csv' != strtolower(trim($info['extension'] ?? null))) {
                    $fail('The :attribute must be a file of type: csv.');
                }
            }],
        ], [], ['file' => 'Upload CSV File']);

        $headerRowCount = 1;

        // Slicing the header part
        $data = array_slice(Excel::toArray([], $request->file('file'))[0] ?? [], $headerRowCount);

        // Get the header columns and inputs
        [$headerColumns, $inputs] = $this->getHeaders();
        $headerCount = count($headerColumns);

        // Set custom messages
        $messages = [
            'email' => 'The :attribute must be a valid email address: <b>:input</b>',
            'unique' => 'The :attribute has already been taken: <b>:input</b>',
        ];

        // Get the validation from our Blueprint setup
        $validations = [];
        $attributes = [];
        $uniqueColumnValidations = [];
        for ($i = 0; $i < $headerCount; $i++) {
            $input = $inputs[$i];

            $rawValidations = $input->getValidations('store');
            $validations[$input->getDbField()] = [];

            foreach ($rawValidations as $val) {
                if ($val instanceof \Illuminate\Validation\Rules\Unique) {
                    $uniqueColumnValidations[] = $input->getDbField();
                }

                $validations[$input->getDbField()][] = $val;
            }

            $attributes[$input->getDbField()] = $input->getLabel();
        }

        // Let's validate row by row
        $insert = [];
        $errors = [];
        $uniqueData = [];
        foreach ($data as $index => $row) {
            $rowData = [];
            for ($i = 0; $i < $headerCount; $i++) {
                $input = $inputs[$i];
                $rowData[$input->getDbField()] = $row[$i];
            }

            $validator = Validator::make($rowData, $validations, $messages, $attributes);
            if ($validator->fails()) {
                $errors[$index + $headerRowCount + 1] = $validator->getMessageBag()->toArray();
            } else {
                $insert[] = $rowData;
            }

            if (in_array($input->getDbField(), $uniqueColumnValidations)) {
                if (in_array($rowData[$input->getDbField()], $uniqueData[$input->getDbField()] ?? [])) {
                    if (! isset($errors[$index + $headerRowCount + 1][$input->getDbField()])) {
                        $errors[$index + $headerRowCount + 1][$input->getDbField()] = [];
                    }

                    $errors[$index + $headerRowCount + 1][$input->getDbField()][] = sprintf(
                        'The %s is repeated/duplicated in the file: <b>%s</b>',
                        $this->getHeaderLabel($input->getDbField()),
                        $rowData[$input->getDbField()]
                    );
                }

                $uniqueData[$input->getDbField()][] = $rowData[$input->getDbField()];
            }
        }

        return ! $errors;
    }

    protected function formatData(&$data, &$errors)
    {
        [, $inputs] = $this->getHeaders();
        $headerRowCount = 1;

        $model = $this->crud->getModel();

        $metaColumns = \config('form-tool.table_meta_columns');
        $createdBy = ($metaColumns['createdBy'] ?? 'createdBy') ?: 'createdBy';
        $createdAt = ($metaColumns['createdAt'] ?? 'createdAt') ?: 'createdAt';

        foreach ($data as $index => &$row) {
            foreach ($inputs as $input) {
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

        [$headerColumns, $inputs] = $this->getHeaders();

        $callback = function () use ($headerColumns, $inputs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headerColumns);

            $data = [];
            foreach ($inputs as $input) {
                $data[] = $input->getImportSample();
            }

            fputcsv($file, $data);

            fclose($file);
        };

        return $this->download($filename, $callback);
    }

    public function export()
    {
        $this->setupImport();

        $filename = $this->title.'.csv';

        $resultData = $this->crud->getModel()->getWhere(['deletedAt' => null]);

        [$headerColumns, $inputs] = $this->getHeaders();

        $callback = function () use ($resultData, $headerColumns, $inputs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headerColumns);

            foreach ($resultData as $row) {
                $data = [];
                foreach ($inputs as $input) {
                    $data[] = $input->getExportValue($row->{$input->getDbField()} ?? null);
                }

                fputcsv($file, $data);
            }

            fclose($file);
        };

        return $this->download($filename, $callback);
    }

    private function getHeaders()
    {
        if (! $this->sampleData) {
            return [[], []];
        }

        $headerColumns = [];
        $inputs = [];
        foreach ($this->sampleData as $col => $sample) {
            $input = $this->crud->getBluePrint()->getInputTypeByDbField($col);

            $headerColumns[] = $this->createHeaderLabel($input->getLabel());
            $inputs[] = $input;
        }

        return [$headerColumns, $inputs];
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
        return preg_replace('/\s+/', '_', trim($label));
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
