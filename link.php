<?php

$target = $_SERVER['DOCUMENT_ROOT'].'/api/storage/app/public';
$link = $_SERVER['DOCUMENT_ROOT'].'/api/public/storage';

if (symlink($target, $link)) {
    echo 'Symlink Created Successfully';
} else {
    echo 'Symlink Failed';
}
