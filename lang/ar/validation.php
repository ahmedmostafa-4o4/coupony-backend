<?php

return [
    'accepted' => 'يجب قبول حقل :attribute.',
    'array' => 'يجب أن يكون حقل :attribute مصفوفة.',
    'between' => [
        'array' => 'يجب أن يحتوي حقل :attribute على بين :min و :max عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يكون عدد أحرف :attribute بين :min و :max.',
    ],
    'boolean' => 'يجب أن تكون قيمة حقل :attribute صحيحة أو خاطئة.',
    'confirmed' => 'تأكيد حقل :attribute غير متطابق.',
    'digits' => 'يجب أن يتكون حقل :attribute من :digits أرقام.',
    'email' => 'يجب أن يكون حقل :attribute عنوان بريد إلكتروني صالحًا.',
    'exists' => 'حقل :attribute المحدد غير صالح.',
    'file' => 'يجب أن يكون حقل :attribute ملفًا.',
    'image' => 'يجب أن يكون حقل :attribute صورة.',
    'in' => 'حقل :attribute المحدد غير صالح.',
    'max' => [
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يزيد حجم ملف :attribute عن :max كيلوبايت.',
        'numeric' => 'يجب ألا تكون قيمة :attribute أكبر من :max.',
        'string' => 'يجب ألا يزيد عدد أحرف :attribute عن :max.',
    ],
    'mimes' => 'يجب أن يكون حقل :attribute ملفًا من النوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي حقل :attribute على الأقل على :min عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute على الأقل :min كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute على الأقل :min.',
        'string' => 'يجب أن يكون عدد أحرف :attribute على الأقل :min.',
    ],
    'numeric' => 'يجب أن يكون حقل :attribute رقمًا.',
    'password' => [
        'letters' => 'يجب أن يحتوي حقل :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن يحتوي حقل :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن يحتوي حقل :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن يحتوي حقل :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهر حقل :attribute في تسريب بيانات. يرجى اختيار :attribute مختلف.',
    ],
    'required' => 'حقل :attribute مطلوب.',
    'required_without' => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'string' => 'يجب أن يكون حقل :attribute نصًا.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'uploaded' => 'فشل رفع حقل :attribute.',

    'custom' => [
        'email' => [
            'required' => 'عنوان البريد الإلكتروني مطلوب.',
            'email' => 'يرجى إدخال عنوان بريد إلكتروني صالح.',
            'already_registered' => 'هذا البريد الإلكتروني مسجل بالفعل. يرجى تسجيل الدخول.',
            'already_registered_seller_onboarding' => 'هذا البريد الإلكتروني مسجل بالفعل. يرجى تسجيل الدخول لمتابعة إعداد حساب التاجر.',
            'already_registered_verify' => 'هذا البريد الإلكتروني مسجل بالفعل. يرجى تسجيل الدخول لتأكيد بريدك الإلكتروني.',
        ],
        'current_password' => [
            'required' => 'كلمة المرور الحالية مطلوبة.',
        ],
        'phone_number' => [
            'unique' => 'رقم الهاتف هذا مسجل بالفعل.',
        ],
        'password' => [
            'required' => 'كلمة المرور مطلوبة.',
            'confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'different' => 'يجب أن تكون كلمة المرور الجديدة مختلفة عن كلمة المرور الحالية.',
        ],
        'role' => [
            'in' => 'الدور المحدد غير صالح.',
        ],
        'language' => [
            'in' => 'اللغة المحددة غير صالحة.',
        ],
        'reset_token' => [
            'required' => 'رمز إعادة التعيين مطلوب.',
        ],
        'code' => [
            'required' => 'رمز التحقق مطلوب.',
            'digits' => 'يجب أن يتكون رمز التحقق من 6 أرقام.',
        ],
        'store_name' => [
            'required' => 'اسم المتجر مطلوب.',
        ],
        'categories' => [
            'required' => 'يجب اختيار تصنيف واحد على الأقل.',
        ],
        'verification_docs' => [
            'required' => 'جميع مستندات التحقق مطلوبة.',
        ],
        'document_type' => [
            'required' => 'نوع المستند مطلوب.',
            'in' => 'نوع المستند غير صالح.',
        ],
        'document' => [
            'required' => 'ملف المستند مطلوب.',
            'mimes' => 'يجب أن يكون المستند ملف PDF أو صورة.',
            'max' => 'يجب ألا يتجاوز حجم المستند 5 ميجابايت.',
        ],
        'product' => [
            'offer_duration_required' => 'يجب إدخال مدة العرض بالأيام أو بالساعات على الأقل.',
            'variant_stock_required_when_tracked' => 'حقل كمية المخزون مطلوب عندما يكون وضع المخزون متتبعًا.',
            'variant_stock_empty_when_unlimited' => 'يجب أن يكون حقل كمية المخزون فارغًا عندما يكون وضع المخزون غير محدود.',
            'variant_low_stock_threshold_empty_when_unlimited' => 'يجب أن يكون حقل حد انخفاض المخزون فارغًا عندما يكون وضع المخزون غير محدود.',
            'offer_required_when_variants_replaced' => 'حقل العرض مطلوب عند استبدال المتغيرات.',
            'offer_fixed_amount_required' => 'حقل المبلغ الثابت مطلوب عندما يكون نوع العرض ثابتًا.',
            'offer_percentage_value_required' => 'حقل قيمة النسبة مطلوب عندما يكون نوع العرض نسبة مئوية.',
            'offer_buy_qty_required' => 'حقل كمية الشراء مطلوب عندما يكون نوع العرض اشترِ X واحصل على Y.',
            'offer_get_qty_required' => 'حقل كمية المكافأة مطلوب عندما يكون نوع العرض اشترِ X واحصل على Y.',
            'offer_buy_variant_skus_required' => 'حقل رموز متغيرات الشراء مطلوب عندما يكون نوع العرض اشترِ X واحصل على Y.',
            'offer_reward_variant_skus_required' => 'حقل رموز متغيرات المكافأة مطلوب عندما يكون نوع العرض اشترِ X واحصل على Y.',
            'offer_buy_variant_skus_exist' => 'يجب أن تكون رموز متغيرات الشراء المحددة موجودة ضمن متغيرات المنتج.',
            'offer_reward_variant_skus_exist' => 'يجب أن تكون رموز متغيرات المكافأة المحددة موجودة ضمن متغيرات المنتج.',
            'claim_buy_variant_ids_quantity' => 'يجب أن يحتوي حقل معرفات متغيرات الشراء على عدد يطابق كمية الشراء المحددة.',
            'claim_reward_variant_ids_quantity' => 'يجب أن يحتوي حقل معرفات متغيرات المكافأة على عدد يطابق كمية المكافأة المحددة.',
            'claim_variant_ids_required' => 'حقل معرفات المتغيرات مطلوب عندما يحتوي المنتج على متغيرات.',
        ],
        'variants' => [
            'single_default' => 'يسمح بمتغير افتراضي واحد فقط لكل منتج.',
            'unique_sku' => 'يجب أن يكون رمز المتغير فريدًا لكل منتج.',
        ],
        'attributes' => [
            'unique_name' => 'يجب أن تكون أسماء الخصائص فريدة لكل متغير.',
        ],
        'import_products' => [
            'file_required' => 'يرجى رفع ملف ZIP.',
            'file_mimes' => 'يجب أن يكون الملف أرشيف ZIP.',
            'file_max' => 'يجب ألا يتجاوز حجم الملف 50 ميجابايت.',
            'store_required' => 'معرف المتجر مطلوب لربط المنتجات المستوردة.',
            'store_exists' => 'المتجر المحدد غير موجود.',
        ],
    ],

    'attributes' => [
        'first_name' => 'الاسم الأول',
        'last_name' => 'اسم العائلة',
        'phone_number' => 'رقم الهاتف',
        'language' => 'اللغة',
        'reset_token' => 'رمز إعادة التعيين',
        'code' => 'رمز التحقق',
        'document_type' => 'نوع المستند',
        'document' => 'المستند',
        'name' => 'الاسم',
    ],
];
