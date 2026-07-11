# Installation

Use these checks when adding FormTool to another Laravel project or verifying an existing setup.

## Composer Package

FormTool is used through the `Deep\FormTool` namespace. Confirm the package is present in `vendor/iam-deep/form-tool` and autoloaded by Composer.

```powershell
composer dump-autoload
```

## Service Provider And Assets

The package provides Blade views, assets, config, and support classes. A working project should be able to render:

```php
form-tool::list.index
form-tool::form.index
```

Input types can also register CSS and JavaScript through `Doc::addCssLink()`, `Doc::addJsLink()`, and `Doc::addJs()`. For example, chosen selects add:

```text
assets/form-tool/plugins/chosen_v1.8.7/chosen.min.css
assets/form-tool/plugins/chosen_v1.8.7/chosen.jquery.min.js
```

## Config Values Used By Inputs

Common config keys used by the input classes:

| Key                                    | Used For                                |
| -------------------------------------- | --------------------------------------- |
| `form-tool.styleClass.input-field`     | Default input CSS class.                |
| `form-tool.maxFileUploadSize`          | Default file max size in KB.            |
| `form-tool.imageThumb.table.maxWidth`  | Table thumbnail max width.              |
| `form-tool.imageThumb.table.maxHeight` | Table thumbnail max height.             |
| `isSoftDelete`                         | Select option trash filtering behavior. |

## Minimum Working Setup

A working module needs:

1. A controller with `setup()`.
2. A model class or `DataModel`.
3. At least one BluePrint field.
4. Routes registered with `CrudRoute::resource()`.
5. Views/assets available under the `form-tool` namespace.

See [Getting Started](getting-started.md) for the smallest complete controller.
