# نشر الصور مع الريبو (public/images)

## المشكلة

الصور موجودة ومتسجلة فى git، لكن لما النشر بياخد الريبو (git pull / clone / deploy)
بيوصل الكود والصور لأ. الداتابيز بتفضل بتشاور على `/images/...`، فالمنتجات
بتظهر من غير صورة، أو المنتج كله بيتخفى لأن الصورة "مش موجودة".

الأرقام على الريبو الحالى:

| الحاجة | الحجم / العدد |
|---|---|
| `public/images` | 284 MB — 1,462 ملف |
| الريبو كله (مع الـ history) | ~850 MB |
| أكبر ملف | ~7 MB (`hero/hero-handicap.webp`) |

## ليه بيحصل

1. **حجم الريبو:** 850 MB على استضافة مشتركة → الـ clone/pull بيقطع (timeout
   أو نفاد مساحة/عدد inodes) فالكود الصغير بيوصل والصور لأ.
2. **رفع يدوى بيعمل تعارض:** لما اترفع فولدر `public/images` بالـ FTP، الملفات
   دى بقت موجودة على السيرفر بشكل مش مطابق لـ git، فـ `git pull` بعد كده
   بيرفض أو بيتوقف ("local changes would be overwritten") — وده يفسّر إن أى
   تحديث جديد (منتجات/كود) مش بيوصل برضه.
3. أى `git pull` فاشل بيسيب الموقع على نسخة قديمة، فبتشوف "منتجات موجودة
   لوكال ومش موجودة على الموقع".

## الحل: التأكد والتكميل بأمر واحد

الأمر الجديد `media:sync` بيقرأ كل مسار صورة من الداتابيز (منتجات + أقسام +
كولكشنز) ويختبره على ديسك السيرفر، وبعدين ينزّل اللى ناقص.

```bash
php cli media:sync --dry-run     # تقرير بس: كام صورة ناقصة وإيه هى
php cli media:sync               # ينزّل الناقص من GitHub (نفس الـ ref)
php cli media:sync --limit=300   # على دفعات لو النت بطيء
```

لو السيرفر مش قادر يوصل GitHub، ارفع الفولدر بـ FTP وشغّل:

```bash
php cli media:sync --from-dir=/home/USER/uploads/public/images
```

`--from-dir` بيقبل الفولدر اللى جواه `products/`, `categories/`, ... أو فولدر
فيه `public/images`.

خيارات إضافية:

```bash
php cli media:sync --repo=owner/repo --ref=main   # ريبو/فرع تانى
php cli media:sync --include-hidden               # صفوف hidden كمان (رسومات EMF)
```

الكود بيرجع `0` لما كل الصور تكون موجودة و`1` لو فيه ناقص، فممكن تحطه فى
سكربت النشر:

```bash
git fetch origin main && git reset --hard origin/main && php cli media:sync
```

## بعد النشر

```bash
php cli media:sync --dry-run    # لازم يطلع: missing here: 0
php cli products:doctor         # يشرح أى منتج مش ظاهر وليه
```

## نصايح تمنع تكرار المشكلة

- **متعملش FTP لملفات متتبعة فى git** (`public/images`, `public/frontend`, …).
  لو محتاج ترفع صور، شيلها من git أو ارفعها فى مكان برّه المستودع.
- بعد أى رفع يدوى على السيرفر، ظبّط الشجرة:
  `git status` → `git checkout -- public/images` (أو `git stash`) → `git pull`.
- الأفضل: النشر يتعمل بـ `git fetch && git reset --hard origin/main` من
  سكربت واحد + `php cli media:sync` بعده، بدل خطوات يدوية.
- على المدى الطويل، الريبو 850 MB تقيل على أى استضافة: الأفضل إخراج
  `public/images` من git (ريبو ميديا منفصل أو CDN) والنشر يبقى خفيف.
