# Form Tool Package for Laravel 10+

![StyleCI](https://github.styleci.io/repos/510375806/shield?branch=main) ![GitHub](https://img.shields.io/github/license/iam-deep/form-tool)

A lightweight Laravel form tool to create your web app or admin panel easily!

Create complex CRUDs easily and save time. Create bug-free and deliver confidently.

## I love examples, let's get into it directly:

```
$model = new DataModel();
$model->db('products', 'productId');

$this->crud = Doc::create($this, $model, function (BluePrint $input) {
    $input->text('productName', 'Product Name')->required();

    $input->select('categoryId', 'Category')->options('categories.categoryId.categoryName')->required();

    $input->number('price', 'Price')->required();

    $input->image('image', 'Image');

    $input->editor('description', 'Description');
});
```
#### Let me explain

Let's assume we have a database with a product table named `products` and with columns `productId, productName, categoryId, price, image, description`. And we have category table named `categories` with columns `categoryId, categoryName`

First, we have created a `$model = new DataModel();` and on the 2nd line we have passed the table name and primary id of the product table. This is the simple way we tell the form tool that this is our CRUD table.

Then we create a CRUD by calling `Doc::create()` and the parameters are:

1. The Laravel's controller class as $this
2. Passing the $model we created above 
3. And the last parameter is the *Closure* which will provide us with a *BluePrint* as parameter.

<br />
Now let's understand our fields inside the Closure. For that let me tell you one thing for the most methods inside closure have 1st parameter as the database column name and 2nd as an optional label of that field. So here are the input fields:

1. We have created a text field with the column `productName` and labelled as `Product Name` and applied the required validation.
2. Then a dropdown with the column `categoryId` and labelled as `Category` instructing form tool to get the options from another table `categories` and then `categoryId` & `categoryName` separated by dots(.) to show as value and text of the &lt;option&gt; tag respectively  i.e. &lt;option value="`categoryId`"&gt;`categoryName`&lt;/option&gt;. Finally, we have added the required validation.
3. Then we have a price field with automatic digit validation as we have specified that it's a number field and then applied the required.
4. Then we have created an image field which will automatically apply validation for the image file and upload the image under the sub-directory `public/storage/`. More on the file uploading later.
5. And at last, we have created an editor by default `CKEditor` for our description column.

---
**Full Code:**

````
<?php

namespace App\Http\Controllers\Admin;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\DataModel;

class ProductsController extends AdminController
{
    // Required for FormTool
    public $title = 'Products';
    public $route = 'products';
    public $singularTitle = 'Product';

    protected $crud = null;

    protected function setup()
    {
        $model = new DataModel();
        $model->db('products', 'productId');

        $this->crud = Doc::create($this, $model, function (BluePrint $input) {
            $input->text('productName', 'Product Name')->required();

            $input->select('categoryId', 'Category')->options('categories.categoryId.categoryName')->required();

            $input->number('price', 'Price')->required();

            $input->image('image', 'Image');

            $input->editor('description', 'Description');
        });

        return $this->crud;
    }
}
````

---
**This will give us:**

1. Products listing page with Bulk Action (Duplicate, Delete), Search, Pagination, Sorting by Columns, Actions (Edit, Delete)

![Screenshot of list page](https://res.cloudinary.com/dpfaxke5x/image/upload/v1719395777/form-tool-crud-list_kndoa5.jpg)

2. Product create page with all the fields mentioned above

![Screenshot of create page](https://res.cloudinary.com/dpfaxke5x/image/upload/v1719395778/form-tool-crud-create_a3l9hj.jpg)

3. Product edit page with all the fields mentioned above

![Screenshot of edit page](https://res.cloudinary.com/dpfaxke5x/image/upload/v1719395777/form-tool-crud-edit_ypcxxk.jpg)

4. Products trash page: This page will contain deleted items. You can now delete them from trash permanently.

![Screenshot of CRUD trash](https://res.cloudinary.com/dpfaxke5x/image/upload/v1719395777/form-tool-crud-trash_plcpcc.jpg)

2. FormTool Activities: Will store all the activities like what item was created, updated, duplicated, deleted, restored and destroyed permanently.

![Screenshot of activites](https://res.cloudinary.com/dpfaxke5x/image/upload/v1719395776/form-tool-activities_ieqwoc.jpg)

![Screenshot of sctivities updated field](https://res.cloudinary.com/dpfaxke5x/image/upload/v1719396962/form-tool-activities-updated-fields_a8qyh9.jpg)


If you have understood this much then you are good to go to start with the package and come back to get help from the below documentation *(I prefer this way of learning new things :wink:)*. If you prefer to read the documentation first then read it below.

> We have many cool features like this. This is just a start. If you like this package please show it by giving it a star in <a href="https://github.com/iam-deep/form-tool">github</a>.

---

> *This package is still under active development.*

You can boost start the setup with this skeleton package: <a href="https://github.com/iam-deep/form-tool-skeleton">form-tool-skeleton</a>

Thanks
