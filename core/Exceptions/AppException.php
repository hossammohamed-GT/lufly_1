<?php

declare(strict_types=1);

namespace Core\Exceptions;

use Exception;

class AppException extends Exception
{
    protected int $httpStatus = 500;

    protected string $errorCode = 'server_error';

    protected array $extra = [];

    public function __construct(string $message = '', ?int $httpStatus = null, ?string $errorCode = null)
    {
        parent::__construct($message !== '' ? $message : static::defaultMessage());
        if ($httpStatus !== null) {
            $this->httpStatus = $httpStatus;
        }
        if ($errorCode !== null) {
            $this->errorCode = $errorCode;
        }
    }

    protected static function defaultMessage(): string
    {
        return 'Application error';
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function withExtra(array $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    public function extra(): array
    {
        return $this->extra;
    }
}
