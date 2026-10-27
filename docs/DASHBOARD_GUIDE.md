# دليل تنفيذ لوحة التحكم القادمة (LUFLY Admin Dashboard Implementation Guide)

توثّق هذه الوثيقة البنية الجاهزة في قاعدة البيانات والـ Services الخاصة بثلاث ركائز أساسية تم تأسيسها بالكامل في النظام، لكي يتم ربط واجهة لوحة التحكم (Dashboard UI) بها مباشرة عند بنائها دون الحاجة لأي تعديل هيكلي في الجداول أو الواجهة الأمامية.

---

## 1. نظام مميزات البطاقات (4 إلى 6 مميزات لكل منتج) + القوالب الجاهزة + منتقي الأيقونات

### الهدف التصميمي والتجاري
- ضمان ظهور **4 إلى 6 مميزات/مواصفات مختصرة** (`product_card_features`) في كل بطاقة منتج وفي صفحة تفاصيل المنتج، بحيث تتساوى البطاقات بصرياً ولا تظهر بطاقة شبه فارغة بجانب بطاقة ممتلئة.
- تمكين مدير النظام في الداشبورد من طريقتين مرنتين عند إضافة أو تعديل أي منتج:
  1. **اختيار ميزة جاهزة (Preset)** من مكتبة القوالب المحفوظة مسبقاً (حيث يتم تعبئة العنوان المترجم والأيقونة تلقائياً مع إمكانية كتابة القيمة فقط مثل `520 × 360 × 350 mm`).
  2. **إضافة ميزة مخصصة (Custom Feature)** بكتابة عنوان حر وقيمة حرة لكل لغة (`en`, `tr`, `cs`) مع اختيار أيقونة من كتالوج الأيقونات المدمج.

### الجداول المؤسسة في قاعدة البيانات
1. **`product_feature_presets`** (مكتبة القوالب الجاهزة — يحتوي حالياً على 20 قالباً جاهزاً):
   - `id` (`BIGINT PK`)
   - `preset_key` (`VARCHAR(80) UNIQUE`) — مثل: `dimensions`, `material`, `finish`, `mounting`, `cartridge`, `water_flow`, `hygiene`, `soft_close`, `sensor`, `power`, `thermostat`, `spray`, `drain`, `connection`, `capacity`, `shape`, `accessibility`, `lighting`, `standard`, `warranty`.
   - `icon_key` (`VARCHAR(50)`) — مفتاح الأيقونة الافتراضي من `ProductFeatureIconCatalog`.
   - `category_hint` (`VARCHAR(150) NULL`) — بادئة القسم المقترح لفرز القوالب الأكثر صلة بنوع المنتج أولاً (مثلاً `bathroom.mixers` أو `smart`).
   - `sort_order` (`INT`), `is_active` (`TINYINT(1)`).
2. **`product_feature_preset_translations`** (ترجمات عناوين القوالب الجاهزة بـ `en`, `tr`, `cs`):
   - `preset_id`, `locale`, `label` (العنوان الجاهز), `placeholder_value` (مثال إرشادي للقيمة داخل حقل الإدخال في الداشبورد).
3. **`product_card_features`** (المميزات المرتبطة بكل منتج — **4 إلى 6 صفوف لكل منتج**):
   - `id`, `product_id` (`FK -> products.id ON DELETE CASCADE`), `preset_id` (`FK -> product_feature_presets.id ON DELETE SET NULL`, يكون `NULL` عند إدخال ميزة مخصصة بالكامل), `icon_key` (`VARCHAR(50)`), `sort_order` (`1..6`).
4. **`product_card_feature_translations`** (ترجمات الميزة وقيمتها لكل منتج بـ `en`, `tr`, `cs`):
   - `feature_id` (`FK -> product_card_features.id ON DELETE CASCADE`), `locale`, `label`, `feature_value`.

### كتالوج الأيقونات الجاهز (`ProductFeatureIconCatalog`)
- المسار: `modules/Products/Services/ProductFeatureIconCatalog.php`
- يوفّر **24 أيقونة SVG هندسية موحّدة** مع أسمائها المترجمة باللغات الثلاث (`ProductFeatureIconCatalog::options($locale)`):
  - `dimensions`, `material`, `finish`, `mounting`, `cartridge`, `water`, `rimless`, `soft-close`, `sensor`, `power`, `thermostat`, `spray`, `drain`, `connection`, `capacity`, `shape`, `accessibility`, `warranty`, `standard`, `weight`, `sound`, `light`, `lock`, `spark`.
- لعرض منتقي الأيقونات (Icon Picker) في نموذج الداشبورد:
  ```php
  $icons = \Modules\Products\Services\ProductFeatureIconCatalog::options($currentLocale);
  // يعيد مصفوفة تحتوي لكل أيقونة على: ['key' => 'dimensions', 'label' => '...', 'svg' => '<svg ...>']
  ```

### قواعد التحقق (Validation Rules) المطلوب تطبيقها في الداشبورد عند الحفظ
1. **الحد الأدنى 4 والحد الأقصى 6 مميزات** لكل منتج (`count($features) >= 4 && count($features) <= 6`).
2. التحقق من صحة `icon_key` عبر `ProductFeatureIconCatalog::has($iconKey)`.
3. عند اختيار `preset_id` وترك حقل `label` فارغاً في إحدى اللغات، يُنسخ `label` تلقائياً من `product_feature_preset_translations` لتلك اللغة، بينما يسمح بتخصيص `label` إذا عدّله المستخدم يدوياً.
4. حفظ الترتيب `sort_order` (`1..6`) حسب السحب والإفلات (Drag & Drop) في واجهة الداشبورد.
   - **ملاحظة عرض الواجهة الأمامية**: عند وجود عدد فردي من المميزات (**5 مميزات**)، تمتد الميزة الأولى (`sort_order = 1`، وعادةً `Dimensions`) تلقائياً بعرض العمودين في أعلى البطاقة تليها شبكتان متوازنتان (`2 + 2`) حتى لا يبقى فراغ بصري أبداً.

---

## 2. نظام ربط أغطية الكراسي (Toilet Seat Covers) بمنتجات الـ WC

### الهدف التصميمي والتجاري
- **منع ظهور أغطية الكراسي (`Toilet Seat Covers`) نهائياً كبطاقات أو صفحات مستقلة** في شبكات الكتالوج أو البحث المباشر أو قوائم الأقسام.
- إظهار الغطاء (أو الأغطية المتوافقة مثل خيار `UF Soft-Close` وخيار `ABS Soft-Close`) **داخل صفحة منتج الـ WC نفسه** تحت قسم `#wc-seat-options`، مع إمكانية:
  - استعراض صورة الغطاء ومخططه الهندسي وأبعاده ومادته وكوده (`SKU`).
  - إضافة الغطاء منفرداً إلى صندوق عرض السعر (`Quotation Box`) أو **إضافة الـ WC مع الغطاء معاً بضغطة واحدة**.
  - البحث بكود الغطاء (مثل `1685-2030`) ليقود المشتري مباشرة إلى منتج الـ WC المتوافق معه.

### آلية التمثيل في قاعدة البيانات (بدون أي عمود إضافي في `products`)
يُستخدم جدول العلاقات المعياري `product_relations`:
1. **غطاء مرتبط بكرسي WC واحد أو أكثر** (الحالة المعتادة، مثل ربط كرسي أطفال `#010` و`#038` و`#039` بالغطاء `#011`، أو ربط الكرسي `#023` بالغطاءين `#022` و`#024`):
   ```sql
   INSERT INTO product_relations (product_id, related_product_id, relation_type)
   VALUES (:wc_product_id, :seat_cover_product_id, 'seat_cover');
   ```
2. **غطاء مسجّل في المستودع قبل ربطه بكرسي WC مستقبلي** (لكي يبقى مخفياً من الظهور كمنتج مستقل في المتجر إلى حين ربطه):
   ```sql
   INSERT INTO product_relations (product_id, related_product_id, relation_type)
   VALUES (:seat_cover_product_id, :seat_cover_product_id, 'seat_cover_only');
   ```
   وعند ربطه لاحقاً بأي WC من الداشبورد، يُحذف صف `seat_cover_only` ويُستبدل بصف/صفوف `seat_cover`.

### كيف يتعامل النظام تلقائياً مع هذه العلاقات حالياً
- `ProductVisibility::notSeatCoverSql('p')` و`ProductVisibility::isPublic($product)`: يستبعدان تلقائياً أي منتج يظهر كـ `related_product_id` بنوع `seat_cover` أو `seat_cover_only` من جميع صفحات الكتالوج والـ API وعدادات الأقسام.
- `ProductVisibility::isSellable($product)`: يسمح بإضافة الغطاء لصندوق التسعير (`Box`) وقائمة المفضلة (`Favorites`) طالما أنه يملك صورة منتج حقيقية صالحة.
- `ProductController::show($slug)`: إذا فُتح رابط غطاء مباشرةً، يقوم بعمل تحويل `302 Redirect` نظيف إلى صفحة الـ WC الأب `#wc-seat-options`.

### تصميم واجهة الربط المقترحة في الداشبورد
1. **في صفحة إنشاء/تعديل منتج من نوع غطاء كرسي (`Product Type: WC Seat Cover Companion`)**:
   - خيار Toggle واضح: **"منتج تابع (غطاء كرسي WC — لا يظهر كبطاقة مستقلة في المتجر)"**.
   - حقل اختيار متعدد (`Multi-Select Searchable Dropdown`) لاختيار منتجات الـ WC المتوافقة معه بالبحث بالكود أو الاسم، مع حفظ صفوف `product_relations (product_id = wc_id, related_product_id = seat_id, relation_type = 'seat_cover')` ومزامنة كود الغطاء في `product_search_keywords` للـ WC الأب.
2. **في صفحة إنشاء/تعديل منتج WC (في قسم `toilets` / `kids-toilets` / `accessible-toilets`)**:
   - تبويب **"أغطية الكرسي المتوافقة (Compatible Seat Covers)"** يتيح:
     - ربط غطاء موجود مسبقاً بنقرة واحدة، **أو**
     - زر **"إنشاء غطاء متوافق سريع (Quick-Create Companion Seat Cover)"** داخل Modal يطلب (الكود `model_code`، الصورة، الرسم الهندسي، المادة `UF / ABS / PP`، والمميزات) ويربطه فوراً بالـ WC الحالي.

---

## 3. نظام اللغات الثلاث الإلزامي (`en` / `tr` / `cs`) وقواعد سلامة الكتالوج

عند بناء نماذج الإدخال والتعديل في الداشبورد، يجب الالتزام بالقواعد التالية لضمان استمرار التطابق التام بنسبة 100%:

1. **الترجمة الثلاثية الكاملة لكل سجل (`en`, `tr`, `cs`)**:
   - **`product_translations`**: حفظ `name`, `short_description`, `description` للغات الثلاث (`en`, `tr`, `cs`). ملاحظة: الحقل `short_description` مخصص للوصف المختصر النظيف بدون كتلة أبعاد الرسم الهندسي.
   - **`product_specification_translations`**: لكل صف في `product_specifications` (`specification_id`)، تُحفظ ترجمات `spec_key` و`spec_value` للغات الثلاث (`en`, `tr`, `cs`).
   - **`product_card_feature_translations`**: لكل ميزة بطاقة (`feature_id`)، تُحفظ `label` و`feature_value` للغات الثلاث.
   - **`seo_meta`**: توليد/تحديث `meta_title` و`meta_description` لكل لغة من اللغات الثلاث.
   - استدعاء `Product::clearStaticCaches()` بعد أي عملية تحديث جماعية في الداشبورد.
2. **قاعدة الأقسام الورقية الصارمة (Leaf-Only Category Rule)**:
   - يُمنع إسناد أي منتج إلى قسم أب يملك أقساماً فرعية؛ استخدم `CategoryService::leafIds()` لتعطيل الأقسام غير الورقية في قوائم الاختيار.
3. **قاعدة فصل الصور الحقيقية عن الرسومات الهندسية (CAD Drawings Separation)**:
   - صور المنتج الحقيقية تأخذ `product_media.type = 'gallery'` (وصورة الغلاف الرئيسية فقط تأخذ `is_primary = 1`).
   - المخططات الهندسية والأبعاد تأخذ دائماً `product_media.type = 'drawing'` و`is_primary = 0` حتى لا تظهر أبداً كصورة غلاف للبطاقة.
   - صور المشاريع/البيئة المحيطة تأخذ `product_media.type = 'situ'` و`is_primary = 0`.
4. **عائلات الألوان (`color_variant`)**:
   - عند وجود نفس الموديل بعدة ألوان/تشطيبات ضمن نفس القسم الورقي، تُربط عبر `product_relations` (`relation_type = 'color_variant'`) وتُسجّل قيمة التشطيب الدقيقة في `product_specifications` (`spec_key = 'Finish'`) ليتم دمجها تلقائياً في بطاقة موحّدة متعددة الألوان في المتجر.
