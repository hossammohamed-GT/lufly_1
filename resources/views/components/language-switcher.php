<?php
/** @var Core\Localization\Translator $translator */
$current = $translator->getLocale();
$supported = $translator->supported();
$route = request()->route();
$params = request()->params();
?>
<div class="lang-switcher" role="group" aria-label="<?= e(trans('common.language')) ?>">
    <?php foreach ($supported as $code => $name): ?>
        <?php
        if ($code === $current) {
            $target = request()->url();
        } elseif ($route !== null && $route->localized) {
            $translated = $translator->trans('routes.' . $route->localizedKey, [], $code);
            $path = '/' . $code . '/' . ltrim($translated, '/');
            foreach ($params as $key => $value) {
                if ($key !== 'locale') {
                    $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
                }
            }
            $target = url($path);
        } else {
            $target = route('lang.switch', ['code' => $code]);
        }
        ?>
        <a class="lang-link<?= $code === $current ? ' is-active' : '' ?>"
           href="<?= e($target) ?>"
           lang="<?= e($code) ?>"
           title="<?= e($name) ?>"><?= e(strtoupper($code)) ?></a>
    <?php endforeach; ?>
</div>
