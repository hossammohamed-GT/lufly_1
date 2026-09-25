# تقرير أداء الصفحة الرئيسية (Home Page) — LUFLY

**تاريخ الفحص:** 2026-09-25
**الفرع:** `arena/01a0dad4-lufly-1`
**الهدف:** معرفة كل أسباب بطء الموقع، وخصوصاً الصفحة الرئيسية.

---

## 1. ملخص تنفيذي — الخلاصة في 6 سطور

الصفحة الرئيسية **لا تعاني من مشكلة في قاعدة البيانات ولا في PHP**. المشكلة كلها في
**الواجهة (Front-end) + إعدادات السيرفر**، وأكبر سبب منفرد لإحساس الزائر بالبطء هو
**شاشة التحميل (Preloader) التي تُجبر الزائر على الانتظار ~3 ثوانٍ** في كل زيارة للهوم.

| المقياس | القيمة الحالية | ملاحظة |
|---|---|---|
| **زمن ظهور المحتوى للمستخدم (بسبب الـ Preloader)** | **~2.95 ثانية** | 🔴 الكارثة الحقيقية |
| حجم الصفحة كاملة | **~2.56 ميجابايت** | 🔴 ثقيلة جداً |
| عدد الطلبات (requests) | **~73 طلب** | 🔴 |
| HTML | 123 KB | 🟠 ضخم |
| CSS | 19 ملف = 243 KB (كلها render-blocking) | 🔴 |
| JS | 10 ملفات = 111 KB | 🟠 |
| الصور | 41 صورة = 2,083 KB | 🔴 |
| استعلامات قاعدة البيانات | 10 استعلامات = **~1–2 مللي ثانية** | ✅ ممتاز |
| ضغط (gzip/brotli) | **غير مُفعّل** | 🔴 |
| Headers كاش للمتصفح | **غير موجودة** | 🔴 |

**التوفير المتاح فوراً (بدون إعادة كتابة كود):**
- تفعيل gzip وحده: **477 KB → 111 KB** في HTML+CSS+JS (**توفير 77%**).
- إلغاء/تقصير الـ Preloader: **−2.5 ثانية** من زمن الإحساس بالبطء.
- تحويل الصور لـ WebP + ضغطها: **~2.0 MB → ~400 KB**.

---

## 2. منهجية القياس (الأرقام أعلاه حقيقية، مش تقدير)

تم تشغيل الموقع فعلياً في هذه البيئة وقياسه:

1. تم تشغيل PHP 8.3.33 عبر `@php-wasm/node` مع تحميل مجلد المشروع كـ filesystem،
   وقاعدة البيانات `database/lufly.sqlite` الموجودة في المستودع.
2. تم تنفيذ الطلب `GET /en` (الصفحة الرئيسية) فعلياً عبر `Core\Http\Kernel`،
   وتم استخراج الـ HTML الناتج (126,043 بايت) وتحليله.
3. تم قياس كل استعلام SQL منفرداً بـ Python/sqlite3 مع `EXPLAIN QUERY PLAN`.
4. تم حساب أحجام كل ملف CSS/JS/صورة من القرص، وحساب نسبة ضغط gzip فعلياً.

> ⚠️ **ملاحظة على أزمنة PHP:** أرقام زمن التنفيذ المقاسة هنا (boot ~100ms، render
> ~50–90ms دافئ) مأخوذة على **WebAssembly**، وهو أبطأ من PHP العادي بعدة مرات.
> على سيرفر حقيقي الأرقام ستكون أفضل بكثير. **لكن** أرقام الأحجام وعدد الطلبات
> وعدد الاستعلامات دقيقة 100% ولا تتأثر ببيئة القياس.

---

## 3. الأسباب بالترتيب (الأعلى تأثيراً أولاً)

### 🔴 السبب #1 — شاشة التحميل (Preloader) تحجب الصفحة ~3 ثوانٍ

**الملف:** `frontend/components/loader/loader.js`

```js
var T_SHOW    = 2500;   // مدة الأنيميشن الأساسية
var T_SEAL    = 450;    // وميض الشعار قبل الستار
var T_CURTAIN = 900;    // مدة خروج الستار
// exitLoader() يُستدعى عند: 2500 + 450 = 2950ms
var HARD_STOP = 6000;
```

وفي `loader.css`:

```css
body.ld-loading { overflow: hidden !important; }
.ld-loader { animation: ldFailsafe 0.4s ease 8s forwards; }
```

**التحليل:**
- الزائر يدخل الهوم → الصفحة تُبنى بالكامل في السيرفر في أجزاء من الثانية،
  لكن `body.ld-loading` يمنع التمرير، والشاشة مغطاة بطبقة `position:fixed`.
- المستخدم **لا يستطيع رؤية ولا التفاعل مع أي محتوى قبل 2.95 ثانية**.
- هذا يحدث في **كل زيارة** للهوم وصفحة التواصل (`isLoaderPath()` تسمح بالهوم و contact فقط).
- والأسوأ: الأنيميشن ليس مرتبطاً بتحميل حقيقي — هو "الانتظار هو التصميم" حسب تعليق الكود:
  `No progress gating - the animation IS the wait, by design.`

**هذا هو أكبر سبب لشكوى "بتاخد فترة طويلة لحد متحمل".**

**الحلول (اختر واحدة):**
| الحل | التأثير | المخاطرة |
|---|---|---|
| **أ)** إلغاء الـ Preloader على الهوم تماماً (مثل باقي الصفحات اللي بتستخدم skeletons) | **−2.95 ثانية** | يفقد إحساس البراند — لكنها أسرع نتيجة |
| **ب)** عرضه **فقط لأول زيارة** (sessionStorage/localStorage) وعدم تكراره | −2.95 ثانية من الزيارة الثانية | ممتاز كحل وسط |
| **ج)** تقصير المدة: `T_SHOW = 600` بدل 2500 | −1.9 ثانية | أقل حل جذرياً |
| **د)** جعله `opacity` overlay شفاف + السماح بالتمرير فوراً | تحسين الإحساس | متوسط |

**التوصية:** **(ب) + (ج)** — أول زيارة 800ms، وأي زيارة تالية بدون شاشة تحميل.

---

### 🔴 السبب #2 — الصور: 2.0 ميجابايت في 41 صورة، بدون webp ولا srcset

**القياس الفعلي:**

| | العدد | الحجم |
|---|---|---|
| صور `eager` (تُحمّل فوراً) | 4 | 246 KB |
| صور `lazy` | 37 | 1,464 KB |
| **الإجمالي** | **41** | **2,083 KB** |

أكبر الصور:
```
188.3 KB  /images/finishes/swatch-rose-gold.jpg      ← eager!
184.0 KB  /images/lifestyle/rituals-backdrop.jpg
169.2 KB  /images/lifestyle/kitchen-suite.jpg
155.6 KB  /images/lifestyle/corporate-backdrop.jpg
149.4 KB  /images/lifestyle/ritual-thermostat.jpg
140.7 KB  /images/lifestyle/minimal-basin.jpg
140.5 KB  /images/lifestyle/ritual-flow.jpg
131.6 KB  /images/lifestyle/modern-bathroom.jpg
121.8 KB  /images/lifestyle/spa-suite.jpg
```

**المشاكل المحددة:**
1. **لا يوجد WebP إلا لـ 15 صورة فقط** من أصل 626 صورة في `public/images`
   (404 PNG + 206 JPG + 15 webp). صور المنتجات كلها PNG/JPG.
2. **لا يوجد `srcset` ولا `sizes` نهائياً** (0 مرة في الـ HTML) — الموبايل ينزّل
   نفس صورة الديسكتوب بالحجم الكامل.
3. `swatch-rose-gold.jpg` (188 KB) **محمّلة eager** — صورة swatch صغيرة الحجم عرضها
   34px في مكان آخر، لكنها هنا 188 KB.
4. `logo.png` (27.6 KB) مكرّرة في `<img>` مرتين.
5. صور الخلفيات في الـ CSS (`rituals-backdrop` 184KB، `corporate-backdrop` 155KB،
   `categories-backdrop` 82KB) = 421 KB تُحمّل بدون أي lazy loading.

**الحلول:**
- [ ] تحويل كل الصور إلى **WebP** (وسيلة ضغط ~70–80% للصور الفوتوغرافية).
  `app/Services/UploadService.php` و `MediaService.php` موجودان بالفعل — أضف
  توليد WebP عند الرفع.
- [ ] ضغط الصور الموجودة (أداة مثل `cwebp` / `sharp`) — الهدف: لا صورة فوق 100 KB.
- [ ] إضافة `srcset`/`sizes` للصور الكبيرة (hero، lifestyle، product cards).
  الهيرو فيه بالفعل نسخ `-m` و `-p` — استخدمهم في `srcset`.
- [ ] نقل `swatch-rose-gold.jpg` من eager إلى lazy أو تصغيرها.
- [ ] تأجيل صور الخلفيات في CSS (أو استخدام `loading=lazy` عبر `<img>` overlay).

**التأثير المتوقع:** 2,083 KB → ~400–500 KB.

---

### 🔴 السبب #3 — 19 ملف CSS = 243 KB، كلها render-blocking في `<head>`

**الملف:** `resources/views/layouts/frontend.php` يطبع كل ستايل كـ `<link rel="stylesheet">` عادي.

الملفات المحمّلة على الهوم:

| الحجم | الملف | هل هو لازم على الهوم؟ |
|---|---|---|
| 50.1 KB | `components/navbar/navbar.css` | ✅ نعم (لكن ضخم جداً) |
| 42.9 KB | `design-system/style.css` | ✅ نعم |
| **32.9 KB** | **`planner/planner.css`** | ❌ **لا** — صفحة المخطط، مش الهوم |
| 19.7 KB | `home/hero-cinema/hero-cinema.css` | ✅ نعم (فوق الطية) |
| **17.2 KB** | **`assistant/chat.css`** | 🟠 الشات العائم — مش محتاج لحد التفاعل |
| 12.2 KB | `home/finishes/finishes.css` | ✅ |
| 11.2 KB | `components/announcement/announcement.css` | 🟠 شريط إعلاني (فارغ حالياً: 0 صفوف) |
| 9.6 KB | `components/loader/loader.css` | 🟠 يكفي inline للـ critical فقط |
| 6.6 / 6.4 / 5.5 / 5.5 / 4.2 / 3.8 / 3.1 / 1.7 KB | باقي أقسام الهوم + product-card + skeleton | ✅ |
| **4.7 KB** | **`box/box.css`** | 🟠 صندوق عرض السعر — مش محتاج لحد التفاعل |
| 5.7 KB | `css/app.css` | ✅ |
| **الإجمالي** | **242.9 KB** | |

**المشاكل:**
1. **19 طلب HTTP متسلسل** على HTTP/1.1 كل واحد فيهم blockers للرسم الأول.
2. **~60 KB من CSS لخصائص لا تظهر على الهوم إلا بعد تفاعل المستخدم**
   (planner 32.9 + chat 17.2 + box 4.7 = 54.8 KB) — تُحمّل على كل صفحة في الموقع
   لأن `modules/Assistant/Views/partials/widget.php` يعمل `pushStyle` لها دائماً.
3. **لا يوجد minification ولا bundling** — الملفات كما هي (لا يوجد build pipeline).

**الحلول:**
- [ ] تحميل CSS الأقسام السفلية بشكل **غير مانع للرسم**:
  `<link rel="stylesheet" media="print" onload="this.media='all'">`
  (نفس الأسلوب المستخدم بالفعل للـ CSS الخارجي في الملف).
- [ ] تحميل `planner.css` / `chat.css` / `box.css` **عند فتح الودجت فقط** (dynamic import).
- [ ] استخراج **Critical CSS** للهيرو + النافبار (inline في `<head>`)، والباقي `defer`.
- [ ] بناء pipeline دمج + ضغط (سكربت بسيط ينتج `frontend/dist/home.css`).
  الـ 19 ملف → ملف واحد مضغوط ≈ **30 KB بدل 243 KB**.

---

### 🟠 السبب #4 — 10 ملفات JS = 111 KB، نصفها لودجت غير مستخدم

| الحجم | الملف | الملاحظة |
|---|---|---|
| **28.5 KB** | `assistant/assistant.js` | الشات العائم — لا يعمل إلا بالضغط عليه |
| 22.5 KB | `components/navbar/navbar.js` | مطلوب |
| **13.1 KB** | `box/box.js` | صندوق عرض السعر — لا يعمل إلا بالتفاعل |
| 17.1 KB | `home/hero-cinema/hero-cinema.js` | مطلوب (الهيرو) |
| 8.1 KB | `home/finishes/finishes.js` | مطلوب |
| 7.6 KB | `components/loader/loader.js` | مطلوب (لكن يمكن تقليله لو قلّلنا الأنيميشن) |
| 5.3 KB | `js/app.js` | مطلوب |
| 4.1 KB | `products/catalog/catalog.js` | 🟠 تُحمّل من `masterpieces.php` — للـ product cards |
| 2.4 + 2.3 KB | skeleton + categories | مطلوبة |

**الحلول:**
- [ ] `defer` موجود بالفعل ✅ — لكن أضف تحميل **كسول** لـ `assistant.js` و `box.js`
  (41.6 KB) عند أول تفاعل مع الودجت.
- [ ] دمج وضغط كل ملفات الهوم في ملف واحد.

---

### 🔴 السبب #5 — لا يوجد ضغط gzip / brotli على السيرفر أبداً

**الملف:** `.htaccess` — لا يحتوي على `mod_deflate` ولا `mod_brotli` ولا أي ضغط.

**القياس الفعلي (gzip level 9):**

| | الحجم الخام | بعد gzip |
|---|---|---|
| HTML الهوم | 123.1 KB | **18.6 KB** |
| كل الـ CSS (19 ملف) | 242.9 KB (إجمالي منفرد 241.9) | **~58 KB** |
| كل الـ JS (10 ملفات) | 110.9 KB | **~33 KB** |
| **الإجمالي** | **476.9 KB** | **110.7 KB** |

**التوفير: 366 KB = 77% أصغر.**

**الحل:** إضافة إلى `.htaccess`:

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript \
        application/javascript application/json application/xml image/svg+xml
</IfModule>
<IfModule mod_brotli.c>
    AddOutputFilterByType BROTLI_COMPRESS text/html text/css application/javascript \
        application/json image/svg+xml
</IfModule>
```

⚠️ **لا تضغط الصور** (هي مضغوطة أصلاً) — ركّز على النصوص.

**هذا أسرع إصلاح وأكبر عائد في التقرير كله: سطر واحد في `.htaccess`.**

---

### 🔴 السبب #6 — لا توجد أي headers كاش للمتصفح

**الملف:** `.htaccess` — لا يوجد `Expires` ولا `Cache-Control`.
وكذلك `Core\Http\Response` لا يرسل `Cache-Control`/`ETag`/`Last-Modified` لأي صفحة.

**النتيجة:** كل زيارة وكل تنقّل يعيد تنزيل نفس الـ 243 KB CSS و 111 KB JS و 2 MB صور،
حتى لو لم يتغير شيء.

**الخبر الجيد:** دالة `asset()` تستخدم `filemtime` كـ version stamp،
فالروابط من الشكل `?v=1790377758` **مستقرة** وتتغير فقط عند تعديل الملف.
يعني نقدر نعمل كاش بأمان تام لمدة سنة.

**الحل:** إضافة إلى `.htaccess`:

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css        "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/webp      "access plus 1 year"
    ExpiresByType image/jpeg      "access plus 1 year"
    ExpiresByType image/png       "access plus 1 year"
    ExpiresByType image/svg+xml   "access plus 1 year"
    ExpiresByType font/woff2      "access plus 1 year"
    ExpiresByType text/html       "access plus 0 seconds"
</IfModule>

<IfModule mod_headers.c>
    <FilesMatch "\.(css|js|webp|jpg|jpeg|png|svg|woff2)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
    <FilesMatch "\.(html|php)$">
        Header set Cache-Control "no-cache, must-revalidate"
    </FilesMatch>
</IfModule>
```

---

### 🟠 السبب #7 — حجم HTML نفسه 123 KB

**تحليل المحتوى:**
- 59 بلوك `<svg>` داخلي (inline) = **19.9 KB**
- 2 بلوك JSON-LD = 2.1 KB
- باقي الـ HTML (أقسام الهوم + النافبار + الفوتر + الودجت) ≈ 100 KB

**الحلول:**
- [ ] تحويل الـ 59 SVG إلى `<use href="/images/icons.svg#id">` من sprite واحد
  (يُكاش للمتصفح، ويوفر ~18 KB في كل صفحة).
- [ ] بعد تفعيل gzip الـ HTML ينزل من 123 KB إلى **18.6 KB** — فده بيخلي المشكلة
  ثانوية جداً. أولوية أقل.

---

### 🟠 السبب #8 — Google Fonts: ملف CSS خارجي يمنع الرسم

**الملف:** `resources/views/layouts/frontend.php`

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
```

- `preconnect` موجود ✅ لكن الـ stylesheet نفسه **مانع للرسم** ويضيف رحلة شبكة
  كاملة (DNS + TCP + TLS + طلب) قبل أول رسم.
- الخط يُطلب بـ **5 أوزان** (`400;500;600;700;800`) — الاستخدام الفعلي غالباً أقل.

**الحلول:**
- [ ] استضافة الخط محلياً (`self-host`) كـ `woff2` — يلغي رحلة الشبكة الخارجية
  ويسمح بالكاش + `preload`. (الأفضل للأداء وللـ GDPR أيضاً.)
- [ ] أو تقليل الأوزان المطلوبة (مثلاً 400/600/700 فقط).
- [ ] استخدام `font-display: swap` (موجود بالفعل عبر `&display=swap` ✅).

---

### 🟡 السبب #9 — تسجيل كل استعلام SQL في ملف على كل طلب

**الملف:** `config/database.php` → `'log' => true`
**الملف:** `core/Database/Connection.php` → `log()` تستدعي `Log::channel('database')->debug()`
**الملف:** `core/Logging/Logger.php`:

```php
@file_put_contents($this->filePath, $line, FILE_APPEND | LOCK_EX);
$dir = dirname($this->filePath);
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }   // stat كل مرة!
```

**القياس:** الهوم ينفّذ **10 استعلامات** → 10 عمليات كتابة متزامنة على القرص
مع `LOCK_EX` + 10 نداءات `is_dir()` في كل طلب.

| | زمن الـ render (دافئ) |
|---|---|
| `database.log = true` (الحالي) | **85–91 ms** |
| `database.log = false` | **46–54 ms** |

**≈ الضعف.** (الأرقام على WASM؛ على سيرفر حقيقي الفرق المطلق أصغر لكنه موجود،
وهو I/O على القرص في المسار الحرج لكل طلب.)

**الحل:** تعطيله في الإنتاج:
```php
// config/database.php
'log' => env('DB_LOG', false),
```
مع إبقائه `true` في التطوير فقط.

---

### 🟡 السبب #10 — لا يوجد كاش لصفحة الهوم (Page Cache) + العشوائية تمنعه

**الملف:** `app/Http/Controllers/HomeController.php`

```php
shuffle($shots);                 /* موزاييك مختلف كل زيارة */
shuffle($ids);                   /* ترتيب عشوائي للتصنيفات */
$ids[] = (int) $bucket[array_rand($bucket)]['id'];   /* منتج عشوائي */
```

- كل طلب يختار 4 منتجات عشوائية ويبدّل ترتيب الصور → **الـ HTML يختلف في كل طلب**.
- النتيجة: **يستحيل عمل full-page cache** (لا `Cache-Control` ولا كاش في السيرفر).
- وخدمة `CacheService` موجودة وجاهزة لكنها **تُستخدم فقط في `SettingsService`** —
  صفر استخدام على الهوم.

**الحلول:**
- [ ] **الأفضل:** كاش لنتائج الاستعلامات (categories + shots + featured pool) لمدة
  5–15 دقيقة عبر `CacheService`، مع الاحتفاظ بالعشوائية في الـ PHP (رخيصة).
  الاستعلامات حالياً ~1–2 ms، فالعائد هنا صغير *الآن* لكنه **يحمي من التضخم**
  لما يكبر الكتالوج.
- [ ] **الأقوى:** الـ 10 استعلامات تعمل في كل طلب — يمكن تخزين الهوم كاملاً
  في كاش مع `Vary: Accept-Language` وتدوير العشوائية في JS بدل PHP.

---

### 🟡 السبب #11 — استعلام #2 يجلب كل صور كل المنتجات ليختار 24 صورة

**الملف:** `app/Http/Controllers/HomeController.php`

```sql
SELECT p.category_id, m.path
  FROM products p
  JOIN product_media pm ON pm.product_id = p.id
  JOIN media m ON m.id = pm.media_id
 WHERE p.status='active' AND p.deleted_at IS NULL
   AND m.deleted_at IS NULL AND m.status='active'
   AND m.mime_type LIKE 'image/%'
   AND pm.type IN ('main','gallery')
 ORDER BY p.category_id ASC, (pm.type='main') DESC, ...
```

**القياس الحالي:** 284 صف، **0.46 ms** — ✅ لا مشكلة الآن.
**لكن:** الاستعلام يجلب كل صور كل المنتجات النشطة (280 منتج × 560 media)
ثم يرميها في مصفوفة PHP ويحتفظ بـ 8 فقط لكل تصنيف.

**المشكلة المستقبلية:** مع 5,000 منتج سيصبح 10,000 صف و ~50 ms+.
**الحل:** كاش للنتيجة (انظر #10)، أو تحديد النتيجة بـ SQL أذكى
(`ROW_NUMBER()` على MySQL 8 / `LIMIT` لكل تصنيف).

---

### ✅ السبب #12 (ليس مشكلة) — قاعدة البيانات سريعة جداً

**للتأكيد: قاعدة البيانات ليست السبب.**

| الاستعلام | الصفوف | الزمن |
|---|---|---|
| التصنيفات + الترجمات | 8 | 0.06 ms |
| **صور الموزاييك (الأثقل)** | 284 | **0.46 ms** |
| منتجات العرض | 280 | 0.16 ms |
| المنتجات المميزة + eager loads (6 استعلامات) | — | ~0.2 ms |
| **الإجمالي (10 استعلامات)** | | **~1–2 ms** |

كل الفهارس (indexes) المطلوبة موجودة بالفعل ✅
(`products_status_index`, `product_media_product_id_index`, `media_status_index`, …).

---

### 🟡 السبب #13 — لا يوجد OPcache مذكور ولا أي build/minify pipeline

- لا يوجد `package.json` في الجذر → لا bundling ولا minification.
- كل ملفات CSS/JS تُقدَّم خام (غير مضغوطة) — واضح من الأحجام.
- لا يوجد أي إعداد لـ OPcache في المستودع.

**الحل:**
- [ ] تأكد أن `opcache.enable=1` و `opcache.validate_timestamps` مضبوط على السيرفر
  (أكبر توفير في زمن PHP نفسه).
- [ ] سكربت build بسيط يدمج + يضغط ملفات الهوم.

---

## 4. خطة العمل المرتبة (Quick Wins أولاً)

### ⚡ المرحلة 1 — ساعة واحدة، توفير هائل، بدون مخاطرة

| # | الإجراء | الملف | التوفير |
|---|---|---|---|
| 1 | تفعيل gzip/brotli | `.htaccess` | **366 KB (77%)** |
| 2 | إضافة `Cache-Control` + `Expires` | `.htaccess` | **~2.5 MB في كل زيارة متكررة** |
| 3 | تعطيل `database.log` في الإنتاج | `config/database.php` | **~40 ms/طلب** |
| 4 | تقصير/إلغاء الـ Preloader | `frontend/components/loader/loader.js` | **حتى −2.95 ثانية** |

**بعد المرحلة 1 فقط:** الصفحة الرئيسية ستبدأ بالظهور خلال أقل من ثانية،
والزيارات المتكررة ستكون شبه فورية.

### 🔧 المرحلة 2 — يوم عمل، تحسين جذري للوزن

| # | الإجراء | التوفير المتوقع |
|---|---|---|
| 5 | تحويل كل الصور إلى WebP + ضغطها | 2,083 KB → ~450 KB |
| 6 | `srcset`/`sizes` للصور الكبيرة | −60% على الموبايل |
| 7 | تحميل CSS الودجت (planner/chat/box) عند التفاعل فقط | −54.8 KB CSS |
| 8 | تحميل `assistant.js` + `box.js` كسول | −41.6 KB JS |
| 9 | CSS غير الحرج بـ `media="print" onload` | رسم أول أسرع بكثير |
| 10 | استضافة الخط محلياً | −رحلة شبكة كاملة |

### 🏗️ المرحلة 3 — بنية تحتية

| # | الإجراء |
|---|---|
| 11 | Critical CSS inline للهيرو + النافبار |
| 12 | Build pipeline (دمج + ضغط) → ملف CSS واحد وملف JS واحد للهوم |
| 13 | SVG sprite بدل 59 SVG inline |
| 14 | كاش لاستعلامات الهوم عبر `CacheService` |
| 15 | تأكيد إعدادات OPcache على السيرفر |

---

## 5. الملفات المعنية (للرجوع السريع)

| الملف | المشكلة |
|---|---|
| `frontend/components/loader/loader.js` | 🔴 Preloader ~2.95s |
| `.htaccess` | 🔴 لا ضغط، لا كاش headers |
| `resources/views/layouts/frontend.php` | 🔴 كل CSS render-blocking، Google Fonts blocking |
| `app/Http/Controllers/HomeController.php` | 🟡 استعلام الصور الثقيل، عشوائية تمنع الكاش |
| `config/database.php` | 🟡 `'log' => true` |
| `modules/Assistant/Views/partials/widget.php` | 🟠 96 KB CSS+JS وِدجِت على كل صفحة |
| `resources/views/home/masterpieces/masterpieces.php` | 🟠 `catalog.js` + `product-card.css` |
| `public/images/` | 🔴 49 MB، 404 PNG، 15 WebP فقط |
| `core/Logging/Logger.php` | 🟡 `file_put_contents(LOCK_EX)` لكل استعلام |
| `app/Services/CacheService.php` | 🟡 موجود وغير مستخدم على الهوم |

---

## 6. ملاحظات إضافية (خارج نطاق الأداء لكنها ظهرت في الفحص)

1. **`resources/lang/en/routes.php`** فيه تعريفات مكررة — قسم
   `/* bathroom planner */` متبوع بقسم `/* quotation box */` يعيد تعريف
   نفس مفاتيح `box.*`. (لا يؤثر على الأداء، لكنه تكرار يجب تنظيفه.)
2. **`.htaccess`** يمنع الوصول للملفات بامتداد `json` — تأكد أن هذا لا يكسر
   أي أصل (asset) يستخدم JSON.
3. جدول `announcements` فارغ (0 صف) لكن `announcement.css` (11.2 KB)
   يُحمّل على كل صفحة، واستعلامه يُنفّذ في كل طلب.
