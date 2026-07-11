# Image Input

Use `image()` for image uploads. `ImageType` extends `FileType`.

```php
$input->image('image', 'Photo')
    ->path('students')
    ->profile()
    ->crop(1000, 1000)
    ->dimensions(1000, 1000)
    ->ratio('1/1');
```

## Important Methods

```php
->path('students')
->profile()
->profile('assets/custom/user.png')
->crop(1000, 1000)
->dimensions(1000, 1000)
->ratio('1/1')
```

## Behavior

- `image()` defaults accepted file types to images.
- `profile()` changes the placeholder image to the FormTool user placeholder or a custom path.
- `dimensions()` adds Laravel image dimension validation.
- `ratio()` adds ratio validation.
- Image uploads share file cleanup behavior with `file()`.

Example from `StudentsController`:

```php
$input->image('image', 'Photo')
    ->path('students')
    ->profile()
    ->crop(1000, 1000);
```
