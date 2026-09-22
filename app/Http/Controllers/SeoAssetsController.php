<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Core\Http\RedirectResponse;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Machine-facing SEO assets: robots.txt, the sitemap index family and a
 * dynamic Open Graph image card. All routes are plain (no session, no
 * locale prefix) and safe to deploy under a sub-directory, because every
 * emitted absolute URL goes through url()/asset().
 */
class SeoAssetsController extends Controller
{
    public function __construct(private readonly SitemapService $sitemaps)
    {
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# back-office & machine APIs are not crawl targets',
            'Disallow: /admin/',
            'Disallow: /api/',
            '',
            '# faceted catalogue states: crawlable (follow) but never indexed twice',
            'Disallow: /*?*sort=',
            'Disallow: /*?*page=',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
        ];

        return Response::make(implode("\n", $lines) . "\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    public function sitemapIndex(): Response
    {
        return $this->xml($this->sitemaps->renderIndex());
    }

    public function sitemapPages(): Response
    {
        return $this->xml($this->sitemaps->renderUrlset($this->sitemaps->pageEntries()));
    }

    public function sitemapProducts(): Response
    {
        /* products.xml carries every URL in every locale: one <url> block
           per localized variant, all linked through hreflang alternates. */
        $expanded = [];
        foreach ($this->sitemaps->productEntries() as $entry) {
            foreach ((array) ($entry['locales'] ?? []) as $loc) {
                $item = $entry;
                $item['loc'] = (string) $loc;
                $expanded[] = $item;
            }
        }

        return $this->xml($this->sitemaps->renderUrlset($expanded));
    }

    public function sitemapImages(): Response
    {
        return $this->xml($this->sitemaps->renderImageSitemap($this->sitemaps->productEntries()));
    }

    private function xml(string $body): Response
    {
        return Response::make($body)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-Robots-Tag', 'noindex');
    }

    /* ------------------------------------------------ OG image card -- */

    /**
     * /og-image?title=...&subtitle=... — renders a 1200x630 PNG social card.
     * Degrades gracefully: without GD (or a usable TTF) it redirects to the
     * static hero OG image, so meta tags never point at a broken endpoint.
     */
    public function ogImage(Request $request): Response
    {
        $title = trim((string) $request->query('title', ''));
        $subtitle = trim((string) $request->query('subtitle', ''));
        $title = mb_substr($title !== '' ? $title : 'LUFLY Architectural Sanitary Ware', 0, 90);
        $subtitle = mb_substr($subtitle, 0, 120);

        if (!function_exists('imagecreatetruecolor')) {
            return new RedirectResponse(asset('/images/lifestyle/heroc-1.webp'));
        }

        $font = $this->findFont();
        if ($font === null || !function_exists('imagettftext')) {
            return new RedirectResponse(asset('/images/lifestyle/heroc-1.webp'));
        }

        $w = 1200;
        $h = 630;
        $img = imagecreatetruecolor($w, $h);
        if ($img === false) {
            return new RedirectResponse(asset('/images/lifestyle/heroc-1.webp'));
        }

        /* brand gradient: deep teal -> teal */
        $top = [12, 67, 71];
        $bottom = [28, 139, 139];
        for ($y = 0; $y < $h; $y++) {
            $t = $y / max(1, $h - 1);
            $r = (int) round($top[0] + ($bottom[0] - $top[0]) * $t);
            $g = (int) round($top[1] + ($bottom[1] - $top[1]) * $t);
            $b = (int) round($top[2] + ($bottom[2] - $top[2]) * $t);
            $color = imagecolorallocate($img, $r, $g, $b);
            if ($color !== false) {
                imagefilledrectangle($img, 0, $y, $w, $y, $color);
            }
        }

        $mint = (int) imagecolorallocate($img, 127, 226, 208);
        $white = (int) imagecolorallocate($img, 255, 255, 255);
        $soft = (int) imagecolorallocate($img, 210, 226, 224);

        /* mint accent bar */
        imagefilledrectangle($img, 90, 150, 100, 480, $mint);

        /* logo, bottom-right */
        $logoPath = base_path('public/images/logo.png');
        if (is_file($logoPath) && function_exists('imagecreatefrompng')) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo !== false) {
                $lw = imagesx($logo);
                $lh = imagesy($logo);
                $targetW = 190;
                $targetH = (int) round($lh * ($targetW / max(1, $lw)));
                imagecopyresampled($img, $logo, $w - $targetW - 70, $h - $targetH - 60, 0, 0, $targetW, $targetH, $lw, $lh);
                imagedestroy($logo);
            }
        }

        /* word-wrapped title */
        $x = 135;
        $y = 210;
        $fontSize = 46;
        foreach ($this->wrap($title, $font, $fontSize, 720) as $line) {
            imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $line);
            $y += (int) round($fontSize * 1.35);
        }

        if ($subtitle !== '') {
            $y += 14;
            $small = 24;
            foreach ($this->wrap($subtitle, $font, $small, 720) as $line) {
                imagettftext($img, $small, 0, $x, $y, $soft, $font, $line);
                $y += (int) round($small * 1.4);
            }
        }

        /* footer strip with domain */
        imagettftext($img, 20, 0, $x, $h - 70, $mint, $font, (string) parse_url((string) config('app.url'), PHP_URL_HOST));

        $png = $this->capturePng($img);
        imagedestroy($img);

        if ($png === null) {
            return new RedirectResponse(asset('/images/lifestyle/heroc-1.webp'));
        }

        return Response::make($png)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=604800, immutable');
    }

    /** @return list<string> */
    private function wrap(string $text, string $font, int $size, int $maxWidth): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/', $text) ?: [] as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            $box = imagettfbbox($size, 0, $font, $candidate);
            $width = is_array($box) ? abs($box[2] - $box[0]) : 0;
            if ($width > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        return $lines;
    }

    private function findFont(): ?string
    {
        $candidates = [
            'C:\\Windows\\Fonts\\arial.ttf',            // Windows / XAMPP
            'C:\\Windows\\Fonts\\calibri.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial.ttf', // macOS
            '/Library/Fonts/Arial.ttf',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    private function capturePng(\GdImage $img): ?string
    {
        ob_start();
        $ok = imagepng($img);
        $data = ob_get_clean();
        if (!$ok || !is_string($data) || $data === '') {
            return null;
        }
        return $data;
    }
}
