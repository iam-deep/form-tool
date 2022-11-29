<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\Doc;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Core\InputTypes\Common\ISearchable;
use Biswadeep\FormTool\Support\FileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EditorType extends BaseInputType implements ISearchable
{
    public int $type = InputType::Editor;
    public string $typeInString = 'editor';

    private string $uploadPath = '';
    private int $limitTableViewLength = 50;

    public function __construct()
    {
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

    public function getValidations($type)
    {
        $validations = parent::getValidations($type);

        return $validations;
    }

    public function getNiceValue($value)
    {
        $value = \strip_tags($this->decodeHTML($value));
        $length = \mb_strlen($value);

        return \mb_substr($value, 0, $this->limitTableViewLength).($length > $this->limitTableViewLength ? '...' : '');
    }

    public function getLoggerValue(string $action, $oldValue = null)
    {
        $newValue = $this->value;

        if ($action == 'update') {
            if ($oldValue != $newValue) {
                return [
                    'type' => $this->typeInString,
                    'data' => [$oldValue ?: '', $newValue ?: ''],
                ];
            }

            return '';
        }

        return $newValue ? ['type' => $this->typeInString, 'data' => $newValue] : '';
    }

    public function beforeStore(object $newData)
    {
        $this->value = $this->encodeHTML($newData->{$this->dbField});

        return $this->value;
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $this->value = $this->encodeHTML($newData->{$this->dbField});

        return $this->value;
    }

    public function uploadImage(Request $request)
    {
        // TODO: Change this to our Crud method after ajax implementation
        // TODO: Multiple Images for different screens

        $fieldName = 'upload';

        $rules[$fieldName] = (new ImageType())->getValidations('store');
        $labels[$fieldName] = 'Image';

        $validator = \Validator::make($request->all(), $rules, [], $labels);

        if ($validator->fails()) {
            return \Response::json([
                'error' => [
                    'message' => $validator->getMessageBag()->first($fieldName),
                ],
            ], 400); // 400 being the HTTP code for an invalid request.
        }

        $path = FileManager::uploadFile($request->file($fieldName), $request->query('path'));
        if ($path != null) {
            return \Response::json(['url' => URL::asset($path)], 200);
        }

        return \Response::json([
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
        $this->setDependencies();

        Doc::addJs('
        // CkEditor config for field: '.$this->dbField."
        var uploadPath = '".URL::to(config('form-tool.adminURL').'/form-tool/editor-upload').'?path='.$this->uploadPath."';
        var selector = document.querySelector('#".$this->dbField."');
        createCkEditor(selector, csrf_token, uploadPath);
        ");

        $input = '<textarea data-path="'.$this->uploadPath.'" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" placeholder="Type the content here!" placeholder="'.$this->placeholder.'">'.old($this->dbField, $this->decodeHTML($this->value)).'</textarea>';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        $this->setDependencies();

        if ($index != '{__index}') {
            Doc::addJs('
            // CkEditor config for field: '.$key.'-'.$this->dbField.'-'.$index."
            var uploadPath = '".URL::to(config('form-tool.adminURL').'/form-tool/editor-upload').'?path='.$this->uploadPath."';
            var selector = document.querySelector('#".$key.'-'.$this->dbField.'-'.$index."');
            createCkEditor(selector, csrf_token, uploadPath);
            ");
        }

        $value = old($key.'.'.$this->dbField);
        $value = $this->decodeHTML($value[$index] ?? $this->value);

        $input = '<textarea data-path="'.$this->uploadPath.'" class="'.\implode(' ', $this->classes).'" id="'.$key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" placeholder="Type the content here!" placeholder="'.$this->placeholder.'">'.$value.'</textarea>';

        return $input;
    }

    public function getMultipleScript()
    {
        // TODO:
    }

    private function setDependencies()
    {
        Doc::addJsLink('assets/form-tool/plugins/ckeditor5-35.1.0/build/ckeditor.js');
        Doc::addJs("
        let csrf_token = '".csrf_token()."';

        // CkEditor Script
        function createCkEditor(selector, csrf_token, uploadPath)
        {
            ClassicEditor
            .create(selector , {
                // https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html#extended-toolbar-configuration-format
                /*toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'removeFormat', '|',
                        'bulletedList', 'numberedList', '|',
                        'outdent', 'indent', '|',
                        
                        'alignment', '|',
                        'link', 'insertImage', 'blockQuote', 'insertTable', 'horizontalLine', '|',
                        'undo', 'redo', 'sourceEditing'
                    ],
                    //shouldNotGroupWhenFull: true
                },*/
                // https://ckeditor.com/docs/ckeditor5/latest/features/headings.html#configuration
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                        { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                        { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                        { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
                    ]
                },
                // Be careful with the setting below. It instructs CKEditor to accept ALL HTML markup.
                // https://ckeditor.com/docs/ckeditor5/latest/features/general-html-support.html#enabling-all-html-features
                htmlSupport: {
                    allow: [
                        {
                            name: /.*/,
                            attributes: true,
                            classes: true,
                            styles: true
                        }
                    ]
                },
                // https://ckeditor.com/docs/ckeditor5/latest/features/link.html#custom-link-attributes-decorators
                link: {
                    decorators: {
                        addTargetToExternalLinks: true,
                        defaultProtocol: 'https://',
                        toggleDownloadable: {
                            mode: 'manual',
                            label: 'Downloadable',
                            attributes: {
                                download: 'file'
                            }
                        }
                    }
                },
                // https://ckeditor.com/docs/ckeditor5/latest/features/mentions.html#configuration
                mention: {
                    feeds: [
                        {
                            marker: '@',
                            feed: [
                                '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                                '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                                '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                                '@sugar', '@sweet', '@topping', '@wafer'
                            ],
                            minimumCharacters: 1
                        }
                    ]
                },
                image: {
                    styles: [
                        'alignCenter',
                        'alignLeft',
                        'alignRight'
                    ],
                    resizeOptions: [
                        {
                            name: 'resizeImage:original',
                            label: 'Original',
                            value: null
                        },
                        {
                            name: 'resizeImage:50',
                            label: '50%',
                            value: '50'
                        },
                        {
                            name: 'resizeImage:75',
                            label: '75%',
                            value: '75'
                        }
                    ],
                    toolbar: [
                        'imageTextAlternative', '|',    //, 'toggleImageCaption'
                        'imageStyle:inline', 'imageStyle:wrapText', 'imageStyle:breakText', 'imageStyle:side', '|',
                        //'resizeImage'
                    ],
                    insert: {
                        integrations: [
                            'insertImageViaUrl'
                        ]
                    }
                },
                simpleUpload: {
                    // The URL that the images are uploaded to.
                    uploadUrl: uploadPath,
        
                    // Enable the XMLHttpRequest.withCredentials property.
                    withCredentials: true,
        
                    // Headers sent along with the XMLHttpRequest to the upload server.
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    }
                },
                allowedContent: true
            })
            .then( editor => {
                window.editor = editor;
                // Prevent showing a warning notification when user is pasting a content from MS Word or Google Docs.
                window.preventPasteFromOfficeNotification = true;

                //document.querySelector( '.ck.ck-editor__main' ).appendChild( editor.plugins.get( 'WordCount' ).wordCountContainer );
            })
            .catch( error => {
                console.error( 'Oops, something went wrong!' );
                console.error( 'Please, report the following error on https://github.com/ckeditor/ckeditor5/issues with the build id and the error stack trace:' );
                console.warn( 'Build id: 513xdav0owiz-cb6an6tupu7e' );
                console.error( error );
            });
        }
        ", 'ckeditor');

        Doc::addCss('
        .ck-editor__editable[role="textbox"] {
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
        ', 'ckeditor');
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
