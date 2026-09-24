<?php

declare(strict_types=1);

namespace Core\Http;

class RedirectResponse extends Response
{
    public function __construct(string $to, int $status = 302)
    {
        parent::__construct('', $status, ['Location' => $to]);
    }

    public function with(string $key, mixed $value): static
    {
        app(Session::class)->flash($key, $value);
        return $this;
    }

    public function withErrors(array $errors): static
    {
        return $this->with('_errors', $errors);
    }

    public function withInput(array $input = []): static
    {
        if ($input === []) {
            $input = app(Request::class)->all();
        }

        return $this->with('_old_input', $input);
    }
}
