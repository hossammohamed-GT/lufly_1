# مخطط قاعدة بيانات المنتجات

> هذه الرسمة تشرح الجزء الخاص بالكتالوج والمنتجات فقط.  
> أسماء الجداول والأعمدة مكتوبة بالإنجليزي حتى تكون مناسبة للبرمجة، بينما وصف كل جزء والعلاقات مكتوب بالعربي.

## الرسمة الرئيسية

```mermaid
flowchart LR
    products["products<br/><b>المنتج الأساسي / الموديل</b><br/>PK id - رقم المنتج<br/>model_code - كود الموديل<br/>category_id - التصنيف الرئيسي<br/>collection_id - المجموعة<br/>status - حالة الظهور"]
    product_translations["product_translations<br/><b>أسماء ووصف المنتج باللغات</b><br/>PK id<br/>product_id - المنتج<br/>locale - اللغة<br/>name - اسم المنتج<br/>description - الوصف"]
    product_variants["product_variants<br/><b>النسخ والتشطيبات</b><br/>PK id<br/>product_id - المنتج الأساسي<br/>sku - كود البيع<br/>variant_name - اللون أو التشطيب<br/>price - السعر<br/>stock_status - التوفر"]
    categories["categories<br/><b>التصنيفات</b><br/>PK id<br/>parent_id - التصنيف الأب<br/>slug - الرابط المختصر<br/>sort_order - ترتيب العرض"]
    category_translations["category_translations<br/><b>أسماء التصنيفات باللغات</b><br/>PK id<br/>category_id - التصنيف<br/>locale - اللغة<br/>name - اسم التصنيف"]
    collections["collections<br/><b>المجموعات أو خطوط المنتجات</b><br/>PK id<br/>slug - الرابط المختصر<br/>status - الحالة"]
    collection_translations["collection_translations<br/><b>أسماء المجموعات باللغات</b><br/>PK id<br/>collection_id - المجموعة<br/>locale - اللغة<br/>name - اسم المجموعة"]
    product_specs["product_specifications<br/><b>المواصفات المرنة</b><br/>PK id<br/>product_id - المنتج<br/>variant_id - النسخة إن وجدت<br/>key - اسم المواصفة<br/>value - قيمة المواصفة<br/>unit - وحدة القياس"]
    product_dimensions["product_dimensions<br/><b>الأبعاد والأوزان</b><br/>PK id<br/>product_id - المنتج<br/>variant_id - النسخة إن وجدت<br/>width_mm - العرض<br/>height_mm - الارتفاع<br/>depth_mm - العمق<br/>weight_kg - الوزن"]
    product_media["product_media<br/><b>الصور والوسائط</b><br/>PK id<br/>product_id - المنتج<br/>variant_id - النسخة إن وجدت<br/>type - نوع الملف<br/>file_path - مكان الملف<br/>sort_order - ترتيب العرض"]
    product_documents["product_documents<br/><b>الكتالوجات والمستندات</b><br/>PK id<br/>product_id - المنتج<br/>variant_id - النسخة إن وجدت<br/>doc_type - نوع المستند<br/>file_path - مكان الملف<br/>language - اللغة"]
    product_relations["product_relations<br/><b>العلاقات بين المنتجات</b><br/>PK id<br/>product_id - المنتج<br/>related_product_id - المنتج المرتبط<br/>relation_type - نوع العلاقة"]
    manufacturers["manufacturers<br/><b>الشركة أو العلامة التجارية</b><br/>PK id<br/>name - الاسم<br/>country - الدولة<br/>logo_path - الشعار"]
    product_import_logs["product_import_logs<br/><b>سجل استيراد البيانات</b><br/>PK id<br/>source_file - الملف المصدر<br/>source_page - رقم الصفحة<br/>detected_sku - الكود المكتشف<br/>status - نتيجة الاستيراد<br/>raw_data - البيانات الأصلية"]

    categories -->|"المنتج ينتمي إلى تصنيف رئيسي"| products
    categories -->|"تصنيفات فرعية داخل تصنيف أب"| categories
    collections -->|"المجموعة تحتوي منتجات"| products
    manufacturers -->|"الشركة تنتج منتجات"| products
    products -->|"للمنتج أسماء ووصف بعدة لغات"| product_translations
    categories -->|"للتصنيف أسماء بعدة لغات"| category_translations
    collections -->|"للمجموعة أسماء بعدة لغات"| collection_translations
    products -->|"المنتج له نسخ وتشطيبات متعددة"| product_variants
    products -->|"مواصفات عامة للمنتج"| product_specs
    product_variants -->|"مواصفات خاصة بنسخة معينة"| product_specs
    products -->|"أبعاد المنتج الأساسية"| product_dimensions
    product_variants -->|"أبعاد خاصة بنسخة معينة"| product_dimensions
    products -->|"صورة رئيسية ورسومات وصور إضافية"| product_media
    product_variants -->|"وسائط خاصة بلون أو تشطيب"| product_media
    products -->|"كتالوجات وشهادات وملفات PDF"| product_documents
    product_variants -->|"مستند خاص بنسخة معينة"| product_documents
    products -->|"إكسسوارات وبدائل وقطع غيار"| product_relations
    products -->|"مصدر البيانات المستوردة"| product_import_logs
```

## كيف نقرأ التصميم؟

### 1. المنتج الأساسي والنسخ

جدول `products` يمثل **الموديل نفسه**، مثل: خلاط حوض موديل 1610.

جدول `product_variants` يمثل الاختلافات التي يتم بيعها أو عرضها بشكل منفصل، مثل:

- Chrome
- Gold
- Black
- مقاس 60 سم أو 80 سم

وبالتالي يمكن أن يكون عندنا منتج واحد له أكثر من `sku`، بدل تكرار بيانات المنتج والوصف والصور العامة.

### 2. التصنيفات والمجموعات

- `categories`: التصنيفات التي يتصفحها العميل، مثل خلاطات أو مرايا أو وحدات حمام.
- `collections`: مجموعة أو خط إنتاج أكبر، مثل Art أو Kids.
- `parent_id` داخل `categories`: يسمح بعمل تصنيف رئيسي وتصنيفات فرعية.
- جداول الترجمة تحفظ الاسم العربي والإنجليزي وأي لغة أخرى بدون تكرار سجل التصنيف نفسه.

### 3. اللغات

الجداول التالية تفصل المحتوى القابل للترجمة عن البيانات الفنية:

- `product_translations`: اسم المنتج ووصفه.
- `category_translations`: اسم التصنيف ووصفه إن احتجنا.
- `collection_translations`: اسم المجموعة ووصفها.

مثال: نفس المنتج له سجل `locale = ar` وسجل آخر `locale = en`.

### 4. المواصفات والأبعاد

- `product_specifications` للمواصفات التي تختلف من نوع منتج إلى آخر، مثل:
  - الخامة
  - نوع الكارتريدج
  - اللون
  - طريقة التركيب
  - عدد الفتحات
- `product_dimensions` للأرقام التي نحتاج البحث أو المقارنة بها:
  - العرض
  - الارتفاع
  - العمق
  - الوزن

وجود `variant_id` اختياري؛ فإذا كانت المواصفة عامة نربطها بالمنتج، وإذا كانت خاصة بلون أو مقاس معين نربطها بالنسخة.

### 5. الصور والملفات

كل الصور والوسائط تكون في `product_media`، ويحدد العمود `type` وظيفة الملف:

| `type` | الاستخدام |
|---|---|
| `main_image` | الصورة الأساسية للمنتج |
| `drawing` | الرسم الفني الذي يوضح الأبعاد والمقاسات |
| `installation` | صورة المنتج أثناء التركيب |
| `lifestyle` | صورة المنتج داخل حمام أو مكان جاهز |
| `gallery` | صور إضافية |
| `video` | فيديو أو صورة متحركة إن وجدت |
| `thumbnail` | صورة مصغرة للعرض السريع |

أما ملفات PDF والكتالوجات والشهادات فتكون في `product_documents`، مثل:

- `datasheet`: ورقة مواصفات المنتج.
- `installation_manual`: دليل التركيب.
- `certificate`: شهادة أو اعتماد.
- `cad`: ملف رسم هندسي.
- `catalogue`: كتالوج يغطي منتجًا أو مجموعة منتجات.

### 6. المنتجات المرتبطة

جدول `product_relations` يربط المنتجات ببعضها، مثل:

- إكسسوار مناسب للمنتج.
- قطعة غيار.
- منتج بديل.
- منتج يمكن عرضه معه.
- جزء من مجموعة أو طقم.

### 7. استيراد البيانات من الملفات القديمة

جدول `product_import_logs` ليس لعرض المنتجات للعميل. هو سجل داخلي يساعدنا أثناء نقل البيانات من:

- قاعدة بيانات الموقع القديم.
- ملفات PDF.
- صور المصنع.

يسجل الملف ورقم الصفحة والكود الذي تم اكتشافه ونتيجة المعالجة، حتى نعرف ما تم استيراده وما يحتاج مراجعة.

## أهم قاعدة في التصميم

الصورة الأساسية والرسم الفني وصورة التركيب ليست أعمدة ثابتة داخل جدول `products`. يتم تخزينها كلها في `product_media` مع تحديد نوع كل ملف في `type`.

بهذا الشكل:

- كل منتج يمكن أن يكون له صورة أساسية واحدة.
- يمكن أن يكون له أكثر من رسم فني.
- يمكن إضافة صور تركيب أو صور متحركة لاحقًا.
- يمكن إضافة أنواع جديدة من الوسائط بدون تعديل جدول المنتجات.

## الجداول الأساسية للنسخة الأولى

لو أردنا بدء التنفيذ بأقل عدد ضروري من الجداول، نبدأ بهذه الجداول:

1. `products`
2. `product_variants`
3. `categories`
4. `product_translations`
5. `product_specifications`
6. `product_dimensions`
7. `product_media`

ثم نضيف `collections` و`product_documents` و`product_relations` عندما نبدأ في إدخال بيانات الكتالوجات كاملة.
