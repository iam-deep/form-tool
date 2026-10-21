# File and image paths

File and image fields default to a maximum stored path length of **250 characters**, including `storage/` and every directory segment. The same default applies to `FileManager::uploadFile()` and `copyFile()`.

```php
$input->file('document')->maxUploadSize(20480); // 20 MB upload, 250-character path.
$input->image('photo')->maxPathLength(300);    // Explicit path override for a suitable column.
```

Path length and upload size are separate. Laravel's `max:` validation on an uploaded file measures kilobytes, not path characters. Use `maxPathLength()` to change the path limit; upload-size rules remain unchanged.

Retained paths are validated as strings. Generated upload/copy paths are checked after choosing a unique filename and before writing. Partial files are removed if a write or visibility operation fails.

Form Tool tracks files uploaded by file/image fields until the corresponding save succeeds. If another upload or the database save fails, it cleans up those new files. Existing files are preserved on failed updates. Replaced files in multiple rows are removed only after the new paths are saved. A later logging or hook failure does not delete files already referenced by saved records.

Calls to FileManager outside Form Tool's field persistence flow must still clean up successful uploads if their own later database operation fails.

For direct FileManager calls, an explicit override can be passed as `maxPathLength: 300`. Match any override to the database column capacity.
