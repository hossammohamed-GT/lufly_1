<?php
/** @var array{name?: string} $props */
$name = (string) ($props['name'] ?? 'spec');
$paths = match ($name) {
    'installation' => '<path d="M5 4h14v16H5z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
    'rimless' => '<ellipse cx="12" cy="9" rx="8" ry="4.5"/><path d="M4 9v4c0 3.2 3.2 5.5 8 5.5s8-2.3 8-5.5V9M8 21h8"/>',
    'soft-close' => '<path d="M4 7h16M4 12h16M4 17h16"/><path d="m8 4 8 16"/>',
    'water-saving' => '<path d="M12 3S6.5 9.2 6.5 13.4a5.5 5.5 0 0 0 11 0C17.5 9.2 12 3 12 3Z"/><path d="m9.5 13.5 1.6 1.6 3.4-3.6"/>',
    'easy-clean', 'scratch-resistant' => '<path d="m12 3 1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z"/><path d="m18 16 .7 2.3L21 19l-2.3.7L18 22l-.7-2.3L15 19l2.3-.7L18 16Z"/>',
    'cartridge', 'single-lever', 'thermostatic' => '<path d="M6 20V9a6 6 0 0 1 12 0v3"/><path d="M12 12v8M4 20h16M15 6h5"/>',
    'shower' => '<path d="M12 3v5M6 12a6 6 0 0 1 12 0M5 12h14"/><path d="M8 16v1M12 16v1M16 16v1M8 20v1M12 20v1M16 20v1"/>',
    'high-flow' => '<path d="M4 8h12M4 12h16M4 16h12"/><path d="m14 5 3 3-3 3M14 13l3 3-3 3"/>',
    'anti-odor' => '<path d="M12 3 20 6v5c0 4.8-3.3 8.3-8 10-4.7-1.7-8-5.2-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/>',
    'material' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
    'dimensions' => '<path d="M4 5h16v14H4z"/><path d="M8 5v4M12 5v3M16 5v4M8 19v-4M12 19v-3M16 19v-4"/>',
    default => '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12 2.2 2.2 4.8-5"/>',
};
?>
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.45" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $paths ?></svg>
