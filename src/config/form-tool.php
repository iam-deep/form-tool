<?php

return [
    // Do not put any slash in the end
    'adminURL' => '/admin',

    // Set route to redirect after login
    'loginRedirect' => '/dashboard',

    // Set root upload directory inside public folder
    // Let's assume we named it 'uploads'
    'uploadPath' => 'uploads',

    // Upload files in date directories. Like month-year directory
    // It will create one more sub directory under the root directory, uploadPath or sub directory
    // Then our full upload path will be uploads/07-2022/ or uploads/sub-path/07-2022
    // Leave blank if you don't want to use
    // Possible values: date time format or blank
    'uploadSubDirFormat' => 'm-Y',

    // Allowed types for file upload
    'allowedTypes' => 'jpg,jpeg,png,webp,gif,svg,bmp,tif,pdf,docx,doc,xls,xlsx,rtf,txt,ppt,csv,pptx,webm,mkv,flv,vob,avi,mov,mp3,mp4,m4p,mpg,mpeg,mp2,svi,3gp,rar,zip,psd,dwg,eps,xlr,db,dbf,mdb,html,tar.gz,zipx',

    // Allowed types for image upload
    'imageTypes' => 'jpg,jpeg,png,webp,gif,svg,bmp,tif',

    // Human Date and Time format
    'formatDateTime' => 'd-m-Y h:i A',
    'formatDate'     => 'd-m-Y',
    'formatTime'     => 'h:i A',

    // Date and Time picker format, these formats should match with the above formats otherwise validation will fail
    'pickerFormatDateTime' => 'DD-MM-YYYY hh:mm A',
    'pickerFormatDate'     => 'DD-MM-YYYY',
    'pickerFormatTime'     => 'hh:mm A',

    // Enable User Permission for View, Create, Edit and Delete
    'isGuarded' => true,

    // Prevent direct deletion of data from database
    // This will mark the data/row as deleted and then you can delete it permanently
    'isSoftDelete' => true,

    // This will prevent deletion of any data that have been used as foreign key in other tables
    // You need first delete all the data linked with the foreign key id
    'isPreventForeignKeyDelete' => true,

    // This will log actions for create, edit, delete
    'isLogActions' => true,
];
