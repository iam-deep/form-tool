<?php

return [
    // Do not put any slash in the end
    'adminURL' => '/admin',

    // Set root upload directory inside public folder
    // Let's assume we named it 'storage'
    'uploadPath' => 'storage',

    // Upload files in date directories. Like month-year directory
    // It will create one more sub directory under the root directory, uploadPath or sub directory
    // Then our full upload path will be storage/07-2022/ or storage/sub-path/07-2022
    // Leave blank if you don't want to use
    // Possible values: date time format or blank
    'uploadSubDirFormat' => 'm-Y',

    // Allowed types for file upload
    'allowedTypes' => 'jpg,jpeg,png,webp,gif,svg,bmp,tif,pdf,docx,doc,xls,xlsx,rtf,txt,ppt,csv,pptx,webm,mkv,flv,vob,'.
        'avi,mov,mp3,mp4,m4p,mpg,mpeg,mp2,svi,3gp,rar,zip,psd,dwg,eps,xlr,db,dbf,mdb,html,tar.gz,zipx',

    // Allowed types for image upload
    'imageTypes' => 'jpg,jpeg,png,webp,gif,svg,bmp,tif',

    // Max file upload size in KB, Default is 5MB
    'maxFileUploadSize' => 1024 * 5,

    // Memory Limit is used for resizing or generating cache images (needed for of high resolution or larger size)
    // If you see any white screen or half text screen after image upload then try to increase the memoryLimit
    // Default keep it 512M (M = MB), no worries will only use at the time of image caching
    // Make sure your server have more than 512MB of RAM
    'memoryLimit' => '512M',

    // Set image cache directory under public folder
    'imageCachePath' => 'cache',

    // Set cache image size in px
    'imageCacheWidth' => 100,
    'imageCacheHeight' => 100,

    // Human Date and Time format, will be overridden by db settings
    'formatDateTime' => 'd-m-Y h:i A',
    'formatDate' => 'd-m-Y',
    'formatTime' => 'h:i A',

    // Date and Time picker format, these formats should match with the above formats otherwise validation will fail
    'pickerFormatDateTime' => 'DD-MM-YYYY hh:mm A',
    'pickerFormatDate' => 'DD-MM-YYYY',
    'pickerFormatTime' => 'hh:mm A',

    // Enable User Permission for View, Create, Edit and Delete
    'isGuarded' => true,

    // If you are using your own custom auth (Not Laravel Auth) then you need to give the user model class
    // that have a static user() method to get user detail
    'auth' => [
        'isCustomAuth' => false,
        'userModel' => \App\Models\User::class,
        'middleware' => ['web', 'auth'],
    ],

    // You can set your own default model for CRUD operations (Methods should be similar to BaseModel)
    'defaultModel' => null,

    // Enable duplicating feature for CRUD
    'isDuplicate' => true,

    // Prevent direct deletion of data from database
    // This will mark the data/row as deleted and then you can delete it permanently
    'isSoftDelete' => true,

    // This will prevent deletion of any data that have been used as foreign key in other tables
    // You need first delete all the data linked with the foreign key id
    'isPreventForeignKeyDelete' => true,
    'commonDeleteRestricted' => [
        [
            'table' => 'users',
            'column' => 'createdBy',
            'label' => 'Created By',
        ],
    ],

    // This will log actions for create, edit, duplicate, delete, restore, destroy
    'isLogActions' => true,

    // Table meta columns
    'table_meta_columns' => [
        'updatedBy' => 'updatedBy', // Default value must be NULL in MySQL
        'updatedAt' => 'updatedAt', // Default value must be NULL in MySQL
        'createdBy' => 'createdBy', // Default value must be NULL in MySQL
        'createdAt' => 'createdAt',
        'deletedBy' => 'deletedBy', // Default value must be NULL in MySQL
        'deletedAt' => 'deletedAt', // Default value must be NULL in MySQL
    ],

    // Styles classes
    'styleClass' => [
        // Global class for most of the input fields
        'input-field' => 'form-control',

        // Filter
        'filter-form-group' => 'form-group',
        'filter-label' => '',

        // Alignments
        'text-left' => 'text-left',
        'text-center' => 'text-center',
        'text-right' => 'text-right',
    ],

    'icons' => [
        // Action Icons
        'view' => 'fa fa-eye',
        'edit' => 'fa fa-pencil',
        'delete' => 'fa fa-trash',

        // File Types Icons
        // PDF
        'pdf' => 'fa fa-file-pdf fa-5x',

        // Archives
        'zip' => 'fa fa-file-archive fa-5x',
        'rar' => 'fa fa-file-archive fa-5x',
        'tar.gz' => 'fa fa-file-archive fa-5x',

        // Codes
        'php' => 'fa fa-file-code fa-5x',
        'html' => 'fa fa-file-code fa-5x',
        'css' => 'fa fa-file-code fa-5x',
        'js' => 'fa fa-file-code fa-5x',

        // Videos
        'mkv' => 'fa fa-file-video fa-5x',
        'flv' => 'fa fa-file-video fa-5x',
        'avi' => 'fa fa-file-video fa-5x',
        '3gp' => 'fa fa-file-video fa-5x',

        // Audios
        'mp3' => 'fa fa-file-audio fa-5x',
        'wv' => 'fa fa-file-audio fa-5x',

        // Images
        'jpg' => 'fa fa-file-image fa-5x',
        'jpeg' => 'fa fa-file-image fa-5x',
        'png' => 'fa fa-file-image fa-5x',
        'webp' => 'fa fa-file-image fa-5x',
        'gif' => 'fa fa-file-image fa-5x',
        'svg' => 'fa fa-file-image fa-5x',
        'bmp' => 'fa fa-file-image fa-5x',

        // Power Points
        'ppt' => 'fa fa-file-powerpoint fa-5x',
        'pptx' => 'fa fa-file-powerpoint fa-5x',

        // Excels
        'csv' => 'fa fa-file-excel fa-5x',
        'xsl' => 'fa fa-file-excel fa-5x',
        'xslx' => 'fa fa-file-excel fa-5x',

        // Words
        'doc' => 'fa fa-file-word fa-5x',
        'docx' => 'fa fa-file-word fa-5x',

        // Others Files
        '*' => 'fa fa-file-text fa-5x',
    ],
];
