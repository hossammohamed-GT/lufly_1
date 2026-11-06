# مُراجعة `lufly-database.sql` (2026-11-06)

## الخلاصة في سطرين

* **الداتا نفسها مطابقة**: الملف بعد ما بيتنفّذ بيطلّع 44,094 صف بنفس القيم اللي في
  `database/lufly.sqlite` — 48 جدول من 50 متطابقين حرف بحرف، والباقي فروق مش مؤثرة.
* **الـ DDL كان فيه فخّان حقيقيان** ممكن يخلّوا الـ import نفسه يفشل أو المنتجات تظهر
  غلط — وده على الأغلب سبب الإيرور اللي شفته. الاتنين اتصلّحوا.

## 1) الداتا: مطابقة

| البند | النتيجة |
|---|---|
| صفوف اللوكال | 44,094 / 36 جدول فيه داتا / 50 جدول |
| صفوف الملف بعد الـ replay | 44,094 — نفس التوزيع |
| جداول متطابقة 100% | 48 من 50 |
| فروق حقيقية في القيم | **0** |
| أخطاء SQL وقت الـ replay | 0 |

الفروق الباقية:
* `media` (62 صف): `meta` نفس الـ JSON بس نسخة sqlite متخزّنة بـ escaping زيادة،
  18 `uuid` في sqlite placeholders والملف فيه UUID حقيقي، و2 `updated_at`.
* `product_media` (15 صف): `updated_at` بس.
* ونسخة sqlite نفسها هي القديمة في `media 217` (الـ collection القديم).

## 2) الـ DDL: ده اللي كان هيبوّظ الـ import

### أ) `CHECK (json_valid(column))` على أعمدة JSON — اتشال

10 أعمدة (`media.meta`, `settings.value`, `activity_logs.data`, `audits.before/after`,
`notifications.data`, `pages.seo`, `blogs.seo`, `categories.seo`, `ai_cache.payload`)
كانوا معرّفين كـ:

```sql
`meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
```

في MySQL ده معناه إن أي قيمة مش JSON صالح **هتفشل الـ INSERT بالكامل**، وفي
phpMyAdmin/إصدارات MySQL أقدم الـ CHECK ممكن كمان يقطع الـ import نفسه. والدليل إن
المشكلة دي حصلت قبل كده: PR #16 (2026-11-05) كان «Fix the lufly-database.sql import
(empty product_media INSERT → #1064)».

دلوقتي كلهم `longtext DEFAULT NULL` عادي، والكود لسه بيخزّن JSON عادي فيهم
(`Media::$casts['meta'] = 'json'` …). يعني مش خسرنا حاجة.

### ب) `CHARACTER SET utf8mb4 COLLATE utf8mb4_bin` على مستوى العمود — اتشال

`utf8mb4_bin` collation قديم مش موجود في MySQL 8 (بقى `utf8mb4_0900_bin`)، ومش موجود
في MariaDB بنفس الاسم في كل الإصدارات — يعني `Unknown collation` وقت الـ import.
الجدول أصلاً `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` فمفيش داعي لتكراره
لكل عمود.

## 3) حاجات تانية اتصلّحت في الملف (من الجولة الأولى)

1. **AUTO_INCREMENT** كان أقل من أعلى `id` في 10 جداول (مثال: `media` 1501 وأعلى id
   1660) → أول إضافة/رفع صورة على MySQL كان هيضرب `Duplicate entry`.
2. **`media` 217 / 221**: بلوك 2026-11-07 أعاد إدخالهم بـ `meta = '[]'` وضيّع بيانات
   المصدر → رجّعت القيم الأصلية.
3. الهيدر كان بيقول 44,108 صف / verified 2026-11-05 → اتظبط.

## 4) لو المنتجات لسه بتظهر غلط على السيرفر بعد الـ import

الملف مش بيرفع صور — بيرفع صفوف. الصور لازم تكون مرفوعة في `public/images/...` على
السيرفر، ودي الحاجات اللي في الداتا نفسها ومش هتتصلح بالـ SQL:

* **25 منتج active** عنده **صور متحررة (gallery) بس ومفيش drawing** — والكود
  (`ProductVisibility::mediaReadySql`) بيشترط **صورة + drawing** مع بعض عشان المنتج
  يظهر في الكتالوج. أسماءهم: `LXD-3111`, `SFD-018`, `SFD-130`, `SFD-039`, `SFD-021`,
  `1685-2300SEN`, `1681-066`, `1681-067`, `1120-0071`, `1120-0223`, `1300-066`,
  `1300-029`, `1300-07408GB`, `1300-07606GB`, `1300-07913GB`, `1300-1109GB`,
  `1300-1109RGB`, `1300-1110-AGB`, `1300-1110-ARGB`, `1300-1110-BGB`,
  `1300-1110-BRGB`, `1300-708BL`, `1300-2197`, `1755-4949`, `1755-020`.
* **منتجين active بدون أي ميديا**: `1300-1111RGB` (id 514) و `1300-1112RGB` (id 516)
  — ممكن يظهروا كـ placeholder.
* **3 منتجات `model_code` فاضي**: ids 129 / 130 / 131 (أسماء: Bath-Tub Flexible Drain
  ×2 و Shower tray waste kit) — بتظهر بكود فاضي في الكارت/الـ SEO.
* **27 منتج** مفيهمش `is_primary = 1`؛ الكود بيتعامل مع ده (بيرتّب بـ `sort_order`)
  فمش مشكلة، بس لو الـ admin بيدوّر على «الصورة الأساسية» هيلاقيها فاضية.
* **5 صفوف media** مساراتها مش موجودة على الديسك (4 منهم لمنتج draft `V-0726C-2`،
  وواحد `1300-066C`). لو الصور مش مرفوعة على السيرفر هتظهر مكسورة.

## 5) إزاي تتحقق بنفسك

```bash
python3 tools/audit/replay_mysql_dump.py
```

بيعمل replay للملف كله على سكيما فاضية ويقارنه بالـ sqlite، وكمان بيفحص فخاخ الـ DDL
(`json_valid`, `CHARACTER SET`, `VALUES;` فاضي, أعمدة مش موجودة, قيم JSON غلط) وبيرجع
كود 0 لو كله تمام.

فحوصات إضافية اتعملت أثناء المراجعة (كلها نضيفة):
* `sqlglot` strict parse لكل الـ 44,828 جملة بـ MySQL dialect → 0 أخطاء.
* كل INSERT/UPDATE بيذكر أعمدة موجودة فعلاً وعدد القيم = عدد الأعمدة → 0 مشاكل.
* FK (29 علاقة) و unique keys (38) → 0 مخالفة على الداتا النهائية.
* FK references كلها لجداول موجودة، مفيش اسم index متكرر جوه نفس الجدول.
* أطوال varchar و InnoDB row size (أكبر جدول 8.2KB من 65KB) → سليم.
* ما فيش `auto_increment` ولا `DEFINER` ولا `information_schema` ولا أي جملة محتاجة
  صلاحيات خاصة.

## 6) ملاحظة مهمة

**متعملش regenerate بالـ `php cli db:export-mysql`** — الأمر ده بيصدّر السكيما من الـ
Blueprint من غير indexes/FKs ومن غير بلوكات تصحيح الصور اللي في آخر الملف. الملف
الحالي هو الـ dump الكامل الصح.
