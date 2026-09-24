<?php

declare(strict_types=1);

namespace Core\Http;

class JsonResponse extends Response
{
    public function __construct(array $data, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        parent::__construct(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}',
            $status,
            $headers,
        );
    }
}
