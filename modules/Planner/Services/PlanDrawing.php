<?php

declare(strict_types=1);

namespace Modules\Planner\Services;

class PlanDrawing
{
    private const PADDING = 46;

    private const DOOR = 80;

    private const WINDOW = 70;
    private const WINDOW_FROM_DOOR = 40;

    public function room(array $room, array $blocks, string $label = ''): string
    {
        $w = max(1, (int) $room['w']);
        $l = max(1, (int) $room['l']);

        $unit = min(560 / $w, 380 / $l);
        $pxW = (int) round($w * $unit);
        $pxL = (int) round($l * $unit);
        $width = $pxW + self::PADDING * 2;
        $height = $pxL + self::PADDING * 2 + 26;

        $sheetY = static fn (float $planY): float => self::PADDING + ($l - $planY) * $unit;

        $svg = [];
        $svg[] = sprintf(
            '<svg viewBox="0 0 %d %d" role="img" aria-label="%s" xmlns="http://www.w3.org/2000/svg" class="plan-svg">',
            $width,
            $height,
            $this->escape($label),
        );
        $svg[] = '<rect class="plan-bg" x="0" y="0" width="' . $width . '" height="' . $height . '" rx="14"/>';

        $svg[] = sprintf(
            '<rect class="plan-room" x="%d" y="%d" width="%d" height="%d"/>',
            self::PADDING,
            self::PADDING,
            $pxW,
            $pxL,
        );

        $svg[] = $this->door($pxW, $pxL, $unit);

        if (($room['window'] ?? '') !== '') {
            $svg[] = $this->window($pxW, $unit, $sheetY);
        }

        foreach ($blocks as $block) {
            $bw = max(4, (int) round($block['w'] * $unit));
            $bl = max(4, (int) round($block['l'] * $unit));
            $x = (int) round(self::PADDING + $block['x'] * $unit);
            $y = (int) round($sheetY($block['y'] + $block['l']));

            $textY = $bl < 18 ? $y + $bl + 12 : (int) round($y + $bl / 2);

            $svg[] = sprintf(
                '<g class="plan-block"><rect x="%d" y="%d" width="%d" height="%d" rx="3"/>'
                . '<text x="%d" y="%d">%s</text></g>',
                $x,
                $y,
                $bw,
                $bl,
                (int) round($x + $bw / 2),
                $textY,
                $this->escape((string) ($block['label'] ?? '')),
            );
        }

        $svg[] = sprintf(
            '<g class="plan-dim">'
            . '<line x1="%d" y1="%d" x2="%d" y2="%d"/><line x1="%d" y1="%d" x2="%d" y2="%d"/>'
            . '<line x1="%d" y1="%d" x2="%d" y2="%d"/><line x1="%d" y1="%d" x2="%d" y2="%d"/>'
            . '<text x="%d" y="%d">%d cm</text><text x="%d" y="%d">%d cm</text></g>',
            self::PADDING,
            self::PADDING - 14,
            self::PADDING + $pxW,
            self::PADDING - 14,
            self::PADDING,
            self::PADDING - 20,
            self::PADDING,
            self::PADDING,
            self::PADDING + $pxW + 14,
            self::PADDING,
            self::PADDING + $pxW + 14,
            self::PADDING + $pxL,
            self::PADDING + $pxW + 20,
            self::PADDING + $pxL,
            self::PADDING + $pxW + 20,
            self::PADDING,
            (int) round(self::PADDING + $pxW / 2),
            self::PADDING - 20,
            $w,
            (int) round(self::PADDING + $pxL / 2),
            self::PADDING + $pxW + 34,
            $l,
        );

        $svg[] = sprintf(
            '<g class="plan-scale"><line x1="%d" y1="%d" x2="%d" y2="%d"/>'
            . '<line x1="%d" y1="%d" x2="%d" y2="%d"/><line x1="%d" y1="%d" x2="%d" y2="%d"/>'
            . '<text x="%d" y="%d">100 cm</text><text x="%d" y="%d">%d × %d cm</text></g>',
            self::PADDING,
            $height - 16,
            (int) round(self::PADDING + 100 * $unit),
            $height - 16,
            self::PADDING,
            $height - 16,
            self::PADDING,
            $height - 10,
            (int) round(self::PADDING + 100 * $unit),
            $height - 16,
            (int) round(self::PADDING + 100 * $unit),
            $height - 10,
            (int) round(self::PADDING + 100 * $unit),
            $height - 22,
            self::PADDING,
            $height - 4,
            $w,
            $l,
        );

        $svg[] = '</svg>';

        return implode('', $svg);
    }

    private function door(int $pxW, int $pxL, float $unit): string
    {
        $opening = (int) round(self::DOOR * $unit);

        return sprintf(
            '<line class="plan-door" x1="%d" y1="%d" x2="%d" y2="%d"/>'
            . '<path class="plan-swing" d="M %d %d A %d %d 0 0 1 %d %d"/>'
            . '<path class="plan-leaf" d="M %d %d L %d %d"/>',
            self::PADDING + 10,
            self::PADDING + $pxL,
            self::PADDING + 10 + $opening,
            self::PADDING + $pxL,
            self::PADDING + 10 + $opening,
            self::PADDING + $pxL,
            $opening,
            $opening,
            self::PADDING + 10,
            self::PADDING + $pxL - $opening,
            self::PADDING + 10,
            self::PADDING + $pxL,
            self::PADDING + 10,
            self::PADDING + $pxL - $opening,
        );
    }

    private function window(int $pxW, float $unit, callable $sheetY): string
    {
        $from = self::PADDING + $pxW;

        return sprintf(
            '<line class="plan-window" x1="%d" y1="%d" x2="%d" y2="%d"/>',
            $from,
            (int) round($sheetY(self::WINDOW_FROM_DOOR)),
            $from,
            (int) round($sheetY(self::WINDOW_FROM_DOOR + self::WINDOW)),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
