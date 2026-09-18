<?php
/**
 * Storefront announcement bar ("the word").
 *
 * Live announcements come from the Announcements module (admin writable,
 * one message per language). The bar renders nothing when the module is
 * disabled, the table is missing, or nothing is currently live.
 *
 * @var Core\View\View $view
 * @var Core\Localization\Translator|null $translator
 */

$view->pushStyle('frontend/components/announcement/announcement.css');

$translator = $translator ?? app('Core\Localization\Translator');
$locale = $translator->getLocale();
$fallback = (string) config('localization.fallback', 'en');

$items = [];

try {
    $service = app(Modules\Announcements\Services\AnnouncementService::class);
    $items = $service->activeFor('topbar', 3);
} catch (\Throwable) {
    /* table missing or module unavailable - the bar simply stays hidden */
    $items = [];
}

$messages = [];

foreach ($items as $announcement) {
    $payload = $announcement->translate($locale, $fallback);
    $message = trim((string) ($payload['message'] ?? ''));

    if ($message === '') {
        continue;
    }

    $messages[] = [
        'id' => (int) $announcement->id,
        'stamp' => (string) ($announcement->updated_at ?? ''),
        'message' => $message,
        'cta' => $payload['cta_label'] ?? null,
        'link' => $announcement->link_url,
        'style' => (string) ($announcement->style ?? 'promo'),
    ];
}

if ($messages === []) {
    return;
}
?>
<aside class="lufly-announcements" data-announcements aria-label="<?= e(trans('common.announcements')) ?>">
    <?php foreach ($messages as $index => $item): ?>
        <?php $dismissKey = 'ann-' . $item['id'] . '-' . substr(md5($item['stamp'] . '|' . $item['message']), 0, 8); ?>
        <div class="luann-item luann-<?= e($item['style']) ?><?= $index > 0 ? ' is-hidden' : '' ?>"
             data-luann-item data-luann-dismiss-key="<?= e($dismissKey) ?>"
             <?= $index > 0 ? 'hidden' : '' ?>>
            <div class="luann-track" aria-live="polite">
                <div class="luann-marquee" data-luann-marquee>
                    <span class="luann-text" data-luann-text>
                        <?php if (!empty($item['link'])): ?>
                            <a href="<?= e((string) $item['link']) ?>" class="luann-link"><?= e($item['message']) ?></a>
                        <?php else: ?>
                            <?= e($item['message']) ?>
                        <?php endif; ?>
                        <?php if (!empty($item['cta'])): ?>
                            <?php if (!empty($item['link'])): ?>
                                <a href="<?= e((string) $item['link']) ?>" class="luann-cta"><?= e((string) $item['cta']) ?> →</a>
                            <?php else: ?>
                                <span class="luann-cta"><?= e((string) $item['cta']) ?> →</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </span><span class="luann-text" data-luann-clone aria-hidden="true">
                        <?php if (!empty($item['link'])): ?>
                            <a href="<?= e((string) $item['link']) ?>" tabindex="-1" class="luann-link"><?= e($item['message']) ?></a>
                        <?php else: ?>
                            <?= e($item['message']) ?>
                        <?php endif; ?>
                        <?php if (!empty($item['cta'])): ?>
                            <?php if (!empty($item['link'])): ?>
                                <a href="<?= e((string) $item['link']) ?>" tabindex="-1" class="luann-cta"><?= e((string) $item['cta']) ?> →</a>
                            <?php else: ?>
                                <span class="luann-cta"><?= e((string) $item['cta']) ?> →</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <button type="button" class="luann-close" data-luann-close aria-label="<?= e(trans('common.delete')) ?>">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    <?php endforeach; ?>
    <?php if (count($messages) > 1): ?>
        <div class="luann-dots" data-luann-dots>
            <?php foreach ($messages as $dotIndex => $dot): ?>
                <button type="button" class="luann-dot<?= $dotIndex === 0 ? ' is-active' : '' ?>"
                        data-luann-dot="<?= (int) $dotIndex ?>"
                        aria-label="<?= e(trans('common.announcements')) . ' ' . ((int) $dotIndex + 1) ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</aside>
<script>
/* Announcement bar: dismiss per message + cycle multiple live messages. */
(function () {
    'use strict';

    var root = document.querySelector('[data-announcements]');
    if (!root) {
        return;
    }

    function collect() {
        return Array.prototype.slice.call(root.querySelectorAll('[data-luann-item]'));
    }

    var items = collect();

    function store(key) {
        try {
            sessionStorage.setItem(key, '1');
        } catch (error) { /* storage unavailable */ }
    }

    function dismissed(key) {
        try {
            return sessionStorage.getItem(key) === '1';
        } catch (error) {
            return false;
        }
    }

    function removeRootIfEmpty() {
        if (items.length === 0 && root.parentNode) {
            root.parentNode.removeChild(root);
        }
    }

    function show(index) {
        if (items.length === 0) {
            removeRootIfEmpty();
            return;
        }
        active = ((index % items.length) + items.length) % items.length;
        items.forEach(function (item, i) {
            item.classList.toggle('is-hidden', i !== active);
            if (i === active) {
                item.removeAttribute('hidden');
            } else {
                item.setAttribute('hidden', '');
            }
        });
        Array.prototype.forEach.call(root.querySelectorAll('[data-luann-dot]'), function (dot) {
            dot.classList.toggle('is-active', Number(dot.getAttribute('data-luann-dot')) === active);
        });
    }

    function schedule() {
        if (timer || items.length < 2) {
            return;
        }
        timer = window.setInterval(function () {
            show(active + 1);
        }, 7000);
    }

    function unschedule() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    var active = 0;
    var timer = null;

    /* 1. drop messages the visitor already dismissed */
    items.forEach(function (item) {
        var key = item.getAttribute('data-luann-dismiss-key');
        if (key && dismissed(key) && item.parentNode) {
            item.parentNode.removeChild(item);
        }
    });
    items = collect();

    /* 2. wire close buttons + dots */
    items.forEach(function (item) {
        var close = item.querySelector('[data-luann-close]');
        if (close) {
            close.addEventListener('click', function () {
                store(item.getAttribute('data-luann-dismiss-key'));
                item.parentNode && item.parentNode.removeChild(item);
                items = collect();
                show(active);
            });
        }
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-luann-dot]'), function (dot) {
        dot.addEventListener('click', function () {
            show(Number(dot.getAttribute('data-luann-dot')));
        });
    });

    /* 3. marquee only travels when the text really overflows */
    function fitMarquees() {
        items.forEach(function (item) {
            var marquee = item.querySelector('[data-luann-marquee]');
            var track = item.querySelector('.luann-track');
            if (!marquee || !track) {
                return;
            }
            var half = marquee.scrollWidth / 2;
            if (half <= track.clientWidth + 4) {
                item.classList.add('is-static');
            } else {
                item.classList.remove('is-static');
            }
        });
    }

    if (items.length > 0) {
        show(0);
        fitMarquees();
        window.addEventListener('resize', fitMarquees);
        schedule();
        root.addEventListener('mouseenter', unschedule);
        root.addEventListener('mouseleave', schedule);
    } else {
        removeRootIfEmpty();
    }
})();
</script>
