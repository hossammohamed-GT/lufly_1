<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Exceptions\AuthorizationException;

abstract class FormRequest
{
    abstract public function rules(): array;

    public function authorize(Request $request): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [];
    }

    public function handle(Request $request): array
    {
        if (!$this->authorize($request)) {
            throw new AuthorizationException();
        }

        return $request->validate($this->rules());
    }
}
