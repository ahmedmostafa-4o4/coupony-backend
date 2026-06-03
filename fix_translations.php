<?php

$path = __DIR__ . '/app/Application/Http/Controllers/API/V1/Admin/UserManagementController.php';
$content = file_get_contents($path);

// Replace __('key', [], 'en') with __('key')
$content = preg_replace("/__\('([^']+)', \[\], 'en'\)/", "__('$1')", $content);

file_put_contents($path, $content);

echo "Replaced successfully!";
