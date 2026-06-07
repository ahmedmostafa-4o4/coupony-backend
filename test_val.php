<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$v1 = \Illuminate\Support\Facades\Validator::make(
    ['file' => 'my_import.zip'],
    ['file' => ['required', 'file', 'mimes:zip', 'max:51200']],
    [
        'file.required' => 'Please upload a ZIP file.',
        'file.mimes' => 'The file must be a ZIP archive.',
        'file.max' => 'The file must not exceed 50MB.',
    ]
);

var_dump('String input:', $v1->errors()->toArray());

$v2 = \Illuminate\Support\Facades\Validator::make(
    [],
    ['file' => ['required', 'file', 'mimes:zip', 'max:51200']],
    [
        'file.required' => 'Please upload a ZIP file.',
        'file.mimes' => 'The file must be a ZIP archive.',
        'file.max' => 'The file must not exceed 50MB.',
    ]
);

var_dump('Missing input:', $v2->errors()->toArray());
