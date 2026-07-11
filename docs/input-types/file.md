# File Input

Use `file()` for documents and non-image uploads.

```php
$input->file('docTransferCertificate', 'Transfer Certificate')
    ->path('students')
    ->accept('pdf,jpg,jpeg,png')
    ->maxUploadSize(2048);
```

## Important Methods

```php
->path('students')
->accept('pdf,jpg,jpeg,png')
->maxUploadSize(2048)
->crop(1000, 1000)
->imagePlaceholder('assets/form-tool/images/placeholder.png')
```

## Behavior

- `path()` stores the upload under the configured FormTool upload path plus the given folder.
- `accept()` controls mime validation and the browser accept list.
- `maxUploadSize()` uses KB.
- `crop($width, $height, $position)` passes crop settings to `FileManager`.
- On update, if the stored value changes, FormTool deletes the old file after the new data is saved.
- On destroy, FormTool deletes uploaded files for the field.
- Table values render as image thumbnails for image files and file icons for other documents.

## Validation

File validation is applied when a file is present. On create, `required()` makes the file mandatory. On update, a required file only requires a new upload when no existing value is posted.
