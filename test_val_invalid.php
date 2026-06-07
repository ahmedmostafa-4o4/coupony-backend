<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$file = new \Illuminate\Http\UploadedFile('e:/coupony-backend/my_import.zip', 'my_import.zip', null, UPLOAD_ERR_INI_SIZE, true);

$v = \Illuminate\Support\Facades\Validator::make(
    ['file' => $file],
    ['file' => ['required', 'file', 'mimes:zip', 'max:51200']],
    [
        'file.required' => 'Please upload a ZIP file.',
        'file.mimes' => 'The file must be a ZIP archive.',
        'file.max' => 'The file must not exceed 50MB.',
    ]
);

var_dump('Invalid upload input:', $v->errors()->toArray());
