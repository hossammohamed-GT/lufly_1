<?php

declare(strict_types=1);

return [
    'server_error' => 'حدث خطأ في النظام. يرجى المحاولة مرة أخرى لاحقاً.',
    'product_not_found' => 'المنتج المطلوب غير موجود أو تم نقله.',
    'user_not_found' => 'المستخدم المطلوب غير متوفر.',
    'media_not_found' => 'ملف الوسائط المطلوب غير متوفر.',

    'upload_missing' => 'لم يتم رفع أي ملف.',
    'upload_too_large' => 'حجم الملف يتجاوز الحد الأقصى المسموح به :max كيلوبايت.',
    'upload_directory_failed' => 'تعذر إنشاء مجلد حفظ الملفات.',
    'upload_move_failed' => 'تعذر حفظ الملف المرفوع في المكان المخصص.',
    'upload_type_not_allowed' => 'الملفات ذات الامتداد ":extension" غير مسموح بها.',
    'upload_type_blocked' => 'تم حظر هذا النوع من الملفات لدواعي الأمان والحماية.',
    'upload_php_error' => 'فشلت عملية الرفع مع كود الخطأ :code.',

    'validation' => [
        'required' => 'حقل :attribute مطلوب ولا يمكن تركه فارغاً.',
        'email' => 'حقل :attribute يجب أن يحتوي على عنوان بريد إلكتروني صحيح.',
        'string' => 'حقل :attribute يجب أن يكون نصياً.',
        'numeric' => 'حقل :attribute يجب أن يكون رقماً.',
        'integer' => 'حقل :attribute يجب أن يكون عدداً صحيحاً.',
        'boolean' => 'حقل :attribute يجب أن يكون إما صواب أو خطأ.',
        'min' => 'حقل :attribute يجب ألا يقل عن :param.',
        'max' => 'حقل :attribute يجب ألا يزيد عن :param.',
        'in' => 'القيمة المحددة في :attribute غير مقبولة.',
        'url' => 'حقل :attribute يجب أن يكون رابطاً صحيحاً.',
        'date' => 'حقل :attribute يجب أن يكون تاريخاً صحيحاً.',
        'array' => 'حقل :attribute يجب أن يكون مصفوفة.',
        'confirmed' => 'تأكيد :attribute غير متطابق.',
        'unique' => 'قيمة :attribute مستخدمة مسبقاً في النظام.',
        'image' => 'حقل :attribute يجب أن يكون صورة معتمدة.',
        'file' => 'حقل :attribute يجب أن يكون ملفاً صالحاً.',
        'mimes' => 'حقل :attribute يجب أن يكون ملفاً من نوع :param.',
    ],

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'price' => 'السعر',
        'sku' => 'كود المنتج',
        'status' => 'الحالة',
        'file' => 'الملف',
        'code' => 'الرمز',
        'title' => 'العنوان',
    ],
];
