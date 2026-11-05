# تشغيل المشروع كامل بدون Apache / PHP مثبّت

المشروع PHP خالص من غير composer، لكن بعض البيئات (الـ sandbox هنا مثلاً)
مفيهاش مفسّر PHP. الملف `tools/preview_server.mjs` بيشغّل المشروع **بالكود
بتاعه زي ما هو** على PHP 8.3 مترجم لـ WebAssembly، و Node بيقدّم الـ HTTP.

```bash
node tools/preview_server.mjs          # ثم افتح http://localhost:8080
PORT=3000 node tools/preview_server.mjs
```

## إيه اللي بيحصل بصراحة
كل طلب غير ثابت (صفحة، POST، AJAX، sitemap) بيدخل على
`public/index.php` → `bootstrap.php`، يعني نفس مسار Apache بالحرف:
الراوتنج، الجلسات، CSRF، sqlite، رفع الصور والكاش كله بيشتغل عادي.
الملفات الثابتة (`/images`, `/lufly-central.css`, `/frontend/`, …) بيقدّمها
Node مباشرة للسرعة.

## المتطلبات
- `node_modules` فيه `@php-wasm/node` و `@php-wasm/universal`:
  ```bash
  npm install @php-wasm/universal @php-wasm/node
  ```
- `.env` في جذر المشروع (انسخه من `.env.example` وحط `APP_KEY`).

## ممنوع من التقديم
`.env` وأي ملف نقطي، `.git`، ملفات `.sqlite` / `.db`، الـ dumps `.sql`،
اللوجات، والسكربتات التنفيذية. جذر المشروع مش بيتقدّم كله — بس
`frontend/`, `lufly-central.*`, `images/`, `uploads/`.
