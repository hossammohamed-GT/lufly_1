<?php
$flagCode = $props['code'] ?? 'en';
$flagStyle = 'border-radius: 2px; display: inline-block; vertical-align: middle;';

$flagUid = substr(bin2hex(random_bytes(4)), 0, 6);
$ukClipId = 'flag-uk-clip-' . $flagUid;
$ukDiagId = 'flag-uk-diag-' . $flagUid;
?>
<?php if ($flagCode === 'tr'): ?>
<svg width="20" height="14" viewBox="0 0 1200 800" style="<?= e($flagStyle) ?>" role="img" aria-label="Turkish">
    <rect width="1200" height="800" fill="#E30A17"/>
    <circle cx="425" cy="400" r="200" fill="#ffffff"/>
    <circle cx="475" cy="400" r="160" fill="#E30A17"/>
    <polygon points="583.33,400 700.82,438.19 628.2,338.2 628.2,461.8 700.82,361.81" fill="#ffffff"/>
</svg>
<?php elseif ($flagCode === 'cs'): ?>
<svg width="20" height="14" viewBox="0 0 900 600" style="<?= e($flagStyle) ?>" role="img" aria-label="Czech">
    <rect width="900" height="300" fill="#ffffff"/>
    <rect y="300" width="900" height="300" fill="#D7141A"/>
    <polygon points="0,0 450,300 0,600" fill="#11457E"/>
</svg>
<?php else: ?>
<svg width="20" height="14" viewBox="0 0 60 30" style="<?= e($flagStyle) ?>" role="img" aria-label="English">
    <clipPath id="<?= e($ukClipId) ?>"><path d="M0,0 v30 h60 v-30 z"/></clipPath>
    <clipPath id="<?= e($ukDiagId) ?>"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath>
    <g clip-path="url(#<?= e($ukClipId) ?>)">
        <path d="M0,0 v30 h60 v-30 z" fill="#012169"/>
        <path d="M0,0 L60,30 M60,0 L0,30" stroke="#ffffff" stroke-width="6"/>
        <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#<?= e($ukDiagId) ?>)" stroke="#C8102E" stroke-width="4"/>
        <path d="M30,0 v30 M0,15 h60" stroke="#ffffff" stroke-width="10"/>
        <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/>
    </g>
</svg>
<?php endif; ?>
