<?php

declare(strict_types=1);

namespace Core\Logging;

use Core\Contracts\LoggerInterface;
use Throwable;

class Logger implements LoggerInterface
{
    private const LEVEL_WEIGHT = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7,
    ];

    public function __construct(
        private readonly string $channel,
        private readonly string $filePath,
        private readonly string $minLevel = 'debug',
    ) {
    }

    public function channel(): string
    {
        return $this->channel;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        if ((self::LEVEL_WEIGHT[$level] ?? 0) < (self::LEVEL_WEIGHT[$this->minLevel] ?? 0)) {
            return;
        }

        $line = sprintf(
            "[%s] %s.%s: %s%s\n",
            date('Y-m-d H:i:s'),
            $this->channel,
            strtoupper($level),
            $this->interpolate($message, $context),
            $this->formatContext($context),
        );

        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents($this->filePath, $line, FILE_APPEND | LOCK_EX);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replace);
    }

    private function formatContext(array $context): string
    {
        if ($context === []) {
            return '';
        }

        try {
            return ' ' . json_encode($this->normalize($context), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Throwable) {
            return '';
        }
    }

    private function normalize(array $value, int $depth = 0): array
    {
        if ($depth > 3) {
            return ['…' => 'truncated'];
        }

        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = match (true) {
                is_array($v) => $this->normalize($v, $depth + 1),
                is_scalar($v), $v === null => $v,
                $v instanceof Throwable => $v::class . ': ' . $v->getMessage(),
                default => get_debug_type($v),
            };
        }

        return $out;
    }
}
