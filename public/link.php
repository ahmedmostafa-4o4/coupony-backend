<?php
// تحديد المسار المستهدف (مكان الصور الفعلي)
$target = __DIR__ . '/../storage/app/public';

// تحديد اسم الرابط المختصر
$link = __DIR__ . '/storage';

if (file_exists($link)) {
    echo "Symlink Already Exists (الرابط موجود مسبقاً)";
} elseif(symlink($target, $link)){
    echo "Symlink Created Successfully (تم إنشاء الرابط بنجاح)";
} else {
    echo "Symlink Failed (فشل الإنشاء - قد تكون الدالة محظورة)";
}
?>