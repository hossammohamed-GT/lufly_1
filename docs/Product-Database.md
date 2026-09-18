# Product Database Architecture

> نظام المنتجات مبني على فصل المنتج الأساسي عن النسخ المختلفة الخاصة به، مع دعم
> كامل للغات، الوسائط، المستندات الفنية، العلاقات بين المنتجات، والمواصفات
> التقنية القابلة للتوسع. الجداول والأعمدة بالإنجليزية، والشرح بالعربي.

## الفايل الجاهز للاستيراد

كل حاجة في ملف واحد: **`lufly-database.sql`** في جذر المشروع.

- مستهدف: MySQL 5.7+ / MySQL 8 / MariaDB 10.4+ (XAMPP و phpMyAdmin).
- بيعمل `CREATE DATABASE IF NOT EXISTS lufly` تلقائيًا، فمش محتاج تعمل قاعدة يدويًا.
- فيه **كل الجداول + كل بيانات البداية** (الأدمن، اللغات، الصلاحيات، الإعدادات،
  التصنيفات، 278 منتج بالبنية الجديدة، وإعلان الموقع الترويجي بثلاث لغات).
- لتحديثه بعد أي تعديل على السكيما أو البيانات:

```bash
php cli db:export-mysql
```

وللتحقق من السكيما على sqlite أثناء التطوير:

```bash
php cli migrate --fresh
php cli seed
```

## الخريطة الكاملة

```text
Brands ──────────┐
Categories ──────┤
Collections ─────┼──> products
                      ├── product_translations        (الاسم والوصف لكل لغة)
                      ├── product_variants            (النسخ: كروم / أسود / 60سم + SKU + السعر + المخزون)
                      ├── product_specifications      (مواصفات مرنة بدون تعديل قاعدة البيانات)
                      ├── product_dimensions          (الأبعاد والأوزان)
                      ├── product_media ──> media     (مكتبة وسائط مركزية + نوع الصورة وترتيبها)
                      ├── product_documents           (كتالوج / تكنيكال شيت / ضمان...)
                      ├── product_relations           (إكسسوار / بديل / قطعة غيار)
                      ├── seo_meta                    (SEO لكل لغة)
                      ├── product_search_keywords     (كلمات البحث)
                      └── product_import_logs         (سجل الاستيراد من PDF/Excel/AI)

Attributes ──> attribute_options ──> product_attribute_values
Announcements ──> announcement_translations   (كلمة الموقع الترويجية - ثلاث لغات)
```

## الجداول بالتفصيل

| الجدول | المسؤولية | أهم الحقول |
| --- | --- | --- |
| `products` | الموديل الأساسي - يُنشأ مرة واحدة | `model_code`, `slug`, `category_id`, `collection_id`, `brand_id`, `status`, `is_featured`, `sort_order` |
| `product_translations` | نصوص المنتج لكل لغة | `product_id`, `locale`, `name`, `short_description`, `description` |
| `product_variants` | النسخ القابلة للبيع | `sku` (unique), `variant_name`, `price`, `stock_status`, `sort_order`, `status` |
| `categories` | شجرة تصنيفات غير محدودة | `parent_id`, `slug`, `image`, `is_featured`, `sort_order`, `status` |
| `category_translations` | اسم/وصف التصنيف لكل لغة | `category_id`, `locale`, `name`, `description` |
| `collections` | المجموعات التسويقية | `slug`, `image`, `status`, `sort_order` |
| `collection_translations` | اسم/وصف المجموعة لكل لغة | `collection_id`, `locale`, `name`, `description` |
| `brands` | العلامة التجارية | `name`, `slug`, `logo`, `country`, `website`, `status` |
| `attributes` | قاموس الخصائص | `code`, `name`, `type`, `is_filterable`, `is_sortable` |
| `attribute_options` | قيم الخصائص (أبيض/أسود/ذهبي) | `attribute_id`, `value`, `color_hex`, `sort_order` |
| `product_attribute_values` | ربط خاصية بمنتج أو نسخة | `product_id`, `variant_id`, `attribute_id`, `attribute_option_id`, `custom_value` |
| `product_specifications` | مواصفات فنية مرنة | `spec_key`, `spec_value`, `unit`, `sort_order` |
| `product_dimensions` | أبعاد ووزن | `width_mm`, `height_mm`, `depth_mm`, `weight_kg` |
| `media` | مكتبة الوسائط المركزية | `uuid`, `collection`, `filename`, `path`, `mime_type`, `size`, `width`, `height` |
| `product_media` | ربط الوسائط بالمنتج | `type` (`thumbnail`/`gallery`/`technical_drawing`/`lifestyle`/`installation`/`video`), `sort_order`, `is_primary` |
| `product_documents` | مستندات المنتج | `document_type`, `language`, `media_id`, `sort_order` |
| `product_relations` | علاقات المنتجات | `relation_type` (`accessory`/`replacement`/`alternative`/`spare_part`/`compatible`) |
| `seo_meta` | SEO لكل لغة | `meta_title`, `meta_description`, `og_title`, `og_description`, `canonical_url` |
| `product_search_keywords` | كلمات مفتاحية للبحث | `keyword` |
| `product_import_logs` | سجل استيراد المنتجات | `source_file`, `source_page`, `detected_sku`, `status`, `raw_data` |
| `announcements` | كلمة الموقع (إعلان الأدمن) | `placement`, `style`, `link_url`, `is_active`, `starts_at`, `ends_at` |
| `announcement_translations` | نص الإعلان لكل لغة | `announcement_id`, `locale`, `message`, `cta_label` |

ملاحظتان:

- أسماء أعمدة المواصفات هي `spec_key` / `spec_value` بدل `key` / `value` لأن
  `KEY` كلمة محجوزة في MySQL وتكسر الاستعلامات.
- `status` للمنتج يدعم: `draft`, `active`, `hidden`, `discontinued`, `coming_soon`.

## كلمة الموقع (الإعلانات)

الأدمن يكتب رسالة تسويقية (مثل «خصم 50% النهاردة») وتظهر في شريط متحرك أعلى كل
صفحة، ويقدر يعدلها وقت ما يحب من:

**Admin → Announcements** (`/admin/announcements`)

- الرسالة تُدخل بثلاث لغات (EN / TR / CS) وكل زائر يشوف لغته.
- `Style`: ترويجي (تركواز) / معلومات / تحذير.
- `Link` اختياري: الشريط كله أو زر الـ CTA يبقوا لينكات.
- `Show from / until` اختياري: جدولة تلقائية للإظهار والإخفاء.
- الزائر يقدر يقفل الشريط (X) ويتم حفظ اختياره في الجلسة.
- أكتر من إعلان نشط؟ بيتعرضوا بالتبادل مع نقاط تنقل، والزائر يوقفه بتمرير الماوس.

## الصلاحيات

`announcements.view` للعرض و `announcements.manage` للإدارة - ممنوحة للأدمن
والمحرر من `PermissionSeeder`.
