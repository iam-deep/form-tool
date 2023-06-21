<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\Common\IPluginableType;
use Deep\FormTool\Core\InputTypes\Common\ISearchable;
use Deep\FormTool\Support\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class EditorType extends BaseInputType implements ISearchable, IPluginableType
{
    public int $type = InputType::EDITOR;
    public string $typeInString = 'editor';

    private string $uploadPath = '';
    private int $limitTableViewLength = 50;

    protected string $currentPlugin = 'ckeditor';
    protected $pluginOptions = [];

    public function __construct()
    {
        parent::__construct();

        $this->classes[] = 'editor';
    }

    public function limitTableView(int $length)
    {
        $this->limitTableViewLength = $length;

        return $this;
    }

    public function path(string $uploadPath)
    {
        $this->uploadPath = \trim($uploadPath);

        return $this;
    }

    public function getNiceValue($value)
    {
        $value = \strip_tags($this->decodeHTML($value));
        $length = \mb_strlen($value);

        return \mb_substr($value, 0, $this->limitTableViewLength).($length > $this->limitTableViewLength ? '...' : '');
    }

    public function getExportValue($value)
    {
        return $value;
    }

    public function getLoggerValue(string $action, $oldValue = null)
    {
        $newValue = $this->value;

        if ($action == 'update') {
            if ($oldValue != $newValue) {
                return [
                    'type' => $this->typeInString,
                    'data' => [$oldValue ?? '', $newValue ?? ''],
                ];
            }

            return '';
        }

        return $newValue !== null ? ['type' => $this->typeInString, 'data' => $newValue] : '';
    }

    public function beforeStore(object $newData)
    {
        $this->value = $this->encodeHTML($newData->{$this->dbField});

        return $this->value;
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        return $this->beforeStore($newData);
    }

    public function uploadImage(Request $request)
    {
        // TODO: Change this to our Crud method after ajax implementation
        // TODO: Multiple Images for different screens

        $fieldName = 'upload';

        $rules[$fieldName] = (new ImageType())->getValidations('store');
        $labels[$fieldName] = 'Image';

        $validator = Validator::make($request->all(), $rules, [], $labels);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'message' => $validator->getMessageBag()->first($fieldName),
                ],
            ], 400); // 400 being the HTTP code for an invalid request.
        }

        $path = FileManager::uploadFile($request->file($fieldName), $request->query('path'));
        if ($path != null) {
            return response()->json(['url' => URL::asset($path)], 200);
        }

        return response()->json([
            'error' => [
                'message' => 'Something went wrong! Please try again.',
            ],
        ], 400);

        /*{
            "urls": {
                "default": "https://example.com/images/foo.jpg",
                "800": "https://example.com/images/foo-800.jpg",
                "1024": "https://example.com/images/foo-1024.jpg",
                "1920": "https://example.com/images/foo-1920.jpg"
            }
        }*/
    }

    public function getHTML()
    {
        $this->setDependencies($this->currentPlugin);
        $this->setJs($this->dbField, $this->uploadPath);

        $input = '<textarea data-path="'.$this->uploadPath.'" class="'.\implode(' ', $this->classes).'" id="'.
            $this->dbField.'" name="'.$this->dbField.'" placeholder="Type the content here!" placeholder="'.
            $this->placeholder.'">'.old($this->dbField, $this->decodeHTML($this->value)).'</textarea>';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $this->setDependencies($this->currentPlugin);

        $selectorId = $key.'-'.$this->dbField.'-'.$index;

        if ($index != '{__index}') {
            $this->setJs($selectorId, $this->uploadPath);
        }

        $value = $this->decodeHTML($oldValue ?? $this->value);

        return '<textarea data-path="'.$this->uploadPath.'" class="'.\implode(' ', $this->classes).'" id="'
            .$selectorId.'" name="'.$key.'['.$index.']['.$this->dbField.']" placeholder="'.$this->placeholder.'">'.
            $value.'</textarea>';
    }

    public function getMultipleScript()
    {
        // TODO:
    }

    public function plugin($plugin, $options = [])
    {
        if (! in_array($plugin, $this->getPlugins())) {
            throw new \Exception(sprintf(
                'Unknown plugin: %s. Available Options: [%s]',
                $plugin,
                implode(', ', $this->getPlugins())
            ));
        }

        $this->currentPlugin = $plugin;
        $this->pluginOptions = $options;

        return $this;
    }

    public function getPlugins()
    {
        return ['ckeditor', 'tinymce'];
    }

    public function setDependencies($plugin)
    {
        if ($plugin == 'ckeditor') {
            Doc::addJsLink('assets/form-tool/plugins/ckeditor5-35.1.0/build/ckeditor.js');
            Doc::addJsLink('assets/form-tool/configs/ckeditor.js');

            Doc::addCss(
                '.ck-editor__editable[role="textbox"] {
                /* editing area */
                min-height: 100px;
            }
            .ck-content .image {
                /* block images */
                max-width: 80%;
                margin: 20px auto;
            }
            .table .ck.ck-editor {
                max-width: 400px;
            }
            ',
                'ckeditor'
            );
        } elseif ($plugin == 'tinymce') {
            Doc::addJsLink('assets/form-tool/plugins/tinymce/tinymce.min.js');
            Doc::addJsLink('assets/form-tool/configs/tinymce.js');
        }
    }

    public function setJs(string $selectorId, string $uploadPath = '')
    {
        $uploadPath = url(config('form-tool.adminURL').'/form-tool/editor-upload').'?path='.$uploadPath;

        if ($this->currentPlugin == 'ckeditor') {
            Doc::addJs(
                '// CkEditor config for field: '.$selectorId."
                createCkEditor(document.querySelector('#".$selectorId."'), '".$uploadPath."');
            ",
                $selectorId
            );
        } elseif ($this->currentPlugin == 'tinymce') {
            Doc::addJs(
                '// Tinymce config for field: '.$selectorId."
                createTinymce('#".$selectorId."', '".$uploadPath."', ".json_encode($this->pluginOptions).');
            ',
                $selectorId
            );
        }
    }

    public function decodeHTML($data)
    {
        return \str_replace('{BASE_URL}', URL::to('/'), \htmlspecialchars_decode($data));
    }

    public function encodeHTML($data)
    {
        return \str_replace(URL::to('/'), '{BASE_URL}', \htmlspecialchars($data));
    }
}
