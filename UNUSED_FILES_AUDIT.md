# دليل تدقيق وتنظيف الملفات غير المستخدمة في مشروع LUFLY
> **ملاحظة:** لم يتم حذف أي ملف إطلاقاً من المشروع؛ هذا الملف تم إنشاؤه ليوثق لك كل عنصر بالتفصيل ومساره وحجمه وسبب اعتباره غير مستخدم، وبانتظار مراجعتك وموافقتك على الحذف.

---

## 1. الفيديوهات غير المستخدمة (تستهلك أكثر من 14 ميجابايت)
الفيديوهات التالية موجودة في مجلد `public/videos/` ولكنها غير مربوطة بأي كود داخل المشروع (الـ Hero الحالي يستخدم سلايدر صور/سينما وخامات خفيفة بدلاً منها):

| # | مسار الملف (File Path) | الحجم (Size) | الحالة وسبب عدم الاستخدام |
|---|---|---|---|
| 1 | `public/videos/hero_scene_2.mp4` | **4.13 MB** | فيديو قديم لمشهد الـ Hero، غير مستدعى في أي فيو أو ستايل أو كود JS. |
| 2 | `public/videos/hero_scene_1.mp4` | **3.70 MB** | فيديو قديم إضافي، غير مستخدم في أي مكان في المشروع. |
| 3 | `public/videos/hero_scene_3.mp4` | **3.61 MB** | فيديو قديم، غير مربوط بأي مكون نهائياً. |
| 4 | `public/videos/lufly-hero-1080.mp4` | **2.58 MB** | فيديو بدقة 1080p ذو حجم كبير، معطل وغير مستخدم. |

**إجمالي المساحة الموفرة من الفيديوهات فقط: ~14.02 MB**

---

## 2. الصور وملفات الميديا المهملة (Unused Lifestyle & Projects Images)
صور عالية الدقة كانت تجارب سابقة أو تخص سكاشن تم استبدالها وتستهلك مساحات تفوق 800 كيلوبايت لكل صورة:

| # | مسار الملف (File Path) | الحجم (Size) | سبب عدم الاستخدام |
|---|---|---|---|
| 5 | `public/images/projects/hotels-resorts.jpg` | **997.7 KB** | صورة مشاريع قديمة غير مستخدمة داخل سكاشن المعرض الحالية. |
| 6 | `public/images/projects/commercial.jpg` | **894.7 KB** | صورة مشاريع غير مربوطة بأي كود في الواجهة. |
| 7 | `public/images/projects/residential.jpg` | **866.9 KB** | صورة مشاريع سكنية غير مستخدمة. |
| 8 | `public/images/projects/luxury-interiors.jpg` | **736.8 KB** | صورة ديكورات غير مستدعاة في أي فيو. |
| 9 | `public/images/lifestyle/factory-craft.jpg` | **850.8 KB** | كانت مخصصة لخلفية شاشة التحميل، وقد طلبت شاشة شفافة بالكامل فلم تعد مستخدمة. |
| 10 | `public/images/lifestyle/blueprint-drafting.jpg` | **789.9 KB** | صورة مخططات قديمة تم استبدالها ولا تظهر في أي صفحة. |
| 11 | `public/images/lifestyle/global-world-map.jpg` | **736.1 KB** | خريطة العالم غير مستخدمة في أي مكون. |
| 12 | `public/images/lifestyle/hero-slide-rain-shower.jpg` | **808.4 KB** | صور سلايدر قديمة تم استبدالها بنسخ WebP خفيفة (`heroc-*.webp`). |
| 13 | `public/images/lifestyle/hero-slide-basin-mixer.jpg` | **689.8 KB** | صورة سلايدر سابقة بدقة ثقيلة، السلايدر الحالي يستخدم WebP. |
| 14 | `public/images/lifestyle/hero-slide-smart-wc.jpg` | **683.1 KB** | صورة قديمة ثقيلة تم تحويلها واستبدالها. |
| 15 | `public/images/lifestyle/hero-slide-freestanding-tub.jpg` | **670.5 KB** | صورة بانيو قديمة ثقيلة غير مستخدمة. |
| 16 | `public/images/lifestyle/hero-slide-smart-wc-hd.png` | **364.0 KB** | نسخة PNG ثقيلة مكررة لمرحاض ذكي، لا يستدعيها الكود. |
| 17 | `public/images/lifestyle/hero-banner-reference.png` | **145.2 KB** | صورة مرجعية تجريبية (Reference) وضعت للتصميم فقط. |
| 18 | `public/images/lifestyle/hero-slide-faucets-hd.jpg` | **33.3 KB** | صورة غير مستخدمة. |
| 19 | `public/images/lifestyle/heroc-2.webp` | **195.7 KB** | شرائح hero بديلة غير مفعلة في مصفوفة السلايدر الحالية. |
| 20 | `public/images/lifestyle/heroc-5.webp` | **134.7 KB** | شريحة hero غير مفعلة. |
| 21 | `public/images/lifestyle/heroc-2-m.webp` | **110.5 KB** | شريحة موبايل غير مفعلة. |
| 22 | `public/images/lifestyle/heroc-5-m.webp` | **80.6 KB** | شريحة موبايل غير مفعلة. |
| 23 | `public/images/lifestyle/heroc-4.webp` | **71.4 KB** | شريحة غير مستخدمة. |
| 24 | `public/images/lifestyle/heroc-3.webp` | **44.5 KB** | شريحة غير مستخدمة. |
| 25 | `public/images/lifestyle/heroc-4-m.webp` | **42.3 KB** | شريحة موبايل غير مفعلة. |
| 26 | `public/images/lifestyle/heroc-3-m.webp` | **24.7 KB** | شريحة موبايل غير مفعلة. |

**إجمالي المساحة الموفرة من الصور غير المستخدمة: ~9.74 MB**

---

## 3. الملفات المكررة والخدمات المهملة في الباك إند (Duplicate PHP Files & Classes)

| # | مسار الملف الأول | مسار الملف المكرر | التحليل والتوصية |
|---|---|---|---|
| 27 | `app/Services/LocalizationService.php` | `modules/Languages/Services/LocalizationService.php` | **كود مكرر 100%**: تم نسخ خدمة اللغات بالكامل داخل الموديول وتوجد نسخة مطابقة لها في الـ `app/`. يمكن حذف نسخة `app/` والاعتماد على موديول اللغات فقط. |
| 28 | `frontend/design-system/logo.png` | `public/images/logo.png` | **صورة مكررة**: صورة اللوجو مكررة في مجلدين. يكفي الاحتفاظ بواحدة داخل `public/images/logo.png` لأنها الوحيدة التي تصل إليها المتصفحات. |
| 29 | `tools/navbar-preview/` (كامل المجلد) | — | أدوات مخصصة لتجربة الـ Navbar محلياً أثناء التطوير فقط (`build.mjs`, `serve.mjs`, `index.html`) وليست جزءاً من نظام الموقع ولا يحتاجها بيئة الإنتاج. |

---

## 4. الملخص الإجمالي للمساحة التي يمكن توفيرها فوراً:
- **إجمالي حجم الفيديوهات غير المستخدمة:** `~14.02 MB`
- **إجمالي حجم الصور غير المستخدمة:** `~9.74 MB`
- **الحجم الإجمالي الفائض القابل للحذف:** **`~23.8 MB`** (يقلل حجم المستودع والتحميل بشكل هائل).
