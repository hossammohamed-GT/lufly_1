<?php

declare(strict_types=1);

namespace Core\Validation;

use Core\Database\DatabaseManager;
use Throwable;

class Validator
{
    private array $errors = [];

    private array $validated = [];

    private bool $ran = false;

    public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $files = [],
        private readonly array $messages = [],
    ) {
    }

    public function passes(): bool
    {
        $this->run();

        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        $this->run();

        return $this->errors;
    }

    public function validated(): array
    {
        $this->run();

        return $this->validated;
    }

    private function run(): void
    {
        if ($this->ran) {
            return;
        }
        $this->ran = true;

        foreach ($this->rules as $field => $ruleString) {
            $this->validateField($field, (string) $ruleString);
        }
    }

    private function validateField(string $field, string $ruleString): void
    {
        $rules = array_filter(explode('|', $ruleString));
        $value = $this->data[$field] ?? null;
        $file = $this->files[$field] ?? null;
        $isFileField = $file !== null || $this->isUploaded($value);

        if (in_array('nullable', $rules, true) && ($value === null || $value === '')) {
            return;
        }

        foreach ($rules as $rule) {
            [$name, $parameter] = $this->parseRule($rule);

            $error = match ($name) {
                'required' => $this->checkRequired($value, $file),
                'nullable' => null,
                'email' => filter_var((string) $value, FILTER_VALIDATE_EMAIL) ? null : 'email',
                'string' => is_string($value) || $value === null ? null : 'string',
                'numeric' => is_numeric($value) || $value === null ? null : 'numeric',
                'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false || $value === null ? null : 'integer',
                'boolean' => in_array($value, [true, false, 0, 1, '0', '1', null], true) ? null : 'boolean',
                'min' => $this->checkSize($field, $value, (float) $parameter, '>='),
                'max' => $this->checkSize($field, $value, (float) $parameter, '<='),
                'in' => $value === null || in_array((string) $value, explode(',', (string) $parameter), true) ? null : 'in',
                'url' => filter_var((string) $value, FILTER_VALIDATE_URL) ? null : 'url',
                'date' => ($value === null || strtotime((string) $value) !== false) ? null : 'date',
                'array' => is_array($value) ? null : 'array',
                'confirmed' => ($this->data[$field . '_confirmation'] ?? null) === $value ? null : 'confirmed',
                'unique' => $this->checkUnique($field, $value, (string) $parameter),
                'image' => $this->checkImage($file, $value),
                'file' => $isFileField ? null : 'file',
                'mimes' => $this->checkMimes($file, $value, (string) $parameter),
                default => null,
            };

            if ($error !== null) {
                $this->addError($field, $name, $parameter, $error);
                return;
            }
        }

        $this->validated[$field] = $value;
    }

    private function parseRule(string $rule): array
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

        return [strtolower(trim($name)), $parameter];
    }

    private function checkRequired(mixed $value, mixed $file): ?string
    {
        if ($file !== null && is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($this->isUploaded($value)) {
            return null;
        }
        if ($value === null || $value === '' || $value === []) {
            return 'required';
        }

        return null;
    }

    private function isUploaded(mixed $value): bool
    {
        return is_array($value) && isset($value['tmp_name']) && is_uploaded_file($value['tmp_name']);
    }

    private function checkSize(string $field, mixed $value, float $limit, string $operator): ?string
    {
        $file = $this->files[$field] ?? null;
        if ($file !== null && is_array($file)) {
            $sizeKb = ((int) ($file['size'] ?? 0)) / 1024;
            $ok = $operator === '>=' ? $sizeKb >= $limit : $sizeKb <= $limit;
            return $ok ? null : ($operator === '>=' ? 'min' : 'max');
        }

        if ($value === null) {
            return null;
        }

        $size = is_numeric($value) && !is_string($value)
            ? (float) $value
            : mb_strlen((string) $value);

        $ok = $operator === '>=' ? $size >= $limit : $size <= $limit;

        return $ok ? null : ($operator === '>=' ? 'min' : 'max');
    }

    private function checkUnique(string $field, mixed $value, string $parameter): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parts = explode(',', $parameter);
        $table = $parts[0] ?? '';
        $column = $parts[1] ?? $field;
        $ignoreId = $parts[2] ?? null;

        if ($table === '') {
            return null;
        }

        try {
            $query = app(DatabaseManager::class)->connection()->table($table)->where($column, $value);
            if ($ignoreId !== null && $ignoreId !== '') {
                $query->where('id', '!=', $ignoreId);
            }
            return $query->exists() ? 'unique' : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function checkImage(mixed $file, mixed $value): ?string
    {
        $file = $file ?? $value;
        if (!is_array($file) || !isset($file['type'])) {
            return null;
        }

        return str_starts_with((string) $file['type'], 'image/') ? null : 'image';
    }

    private function checkMimes(mixed $file, mixed $value, string $parameter): ?string
    {
        $file = $file ?? $value;
        if (!is_array($file) || !isset($file['name'])) {
            return null;
        }

        $allowed = explode(',', $parameter);
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        return in_array($extension, $allowed, true) ? null : 'mimes';
    }

    private function addError(string $field, string $rule, ?string $parameter, string $errorKey): void
    {
        $custom = $this->messages[$field . '.' . $rule] ?? null;
        if ($custom !== null) {
            $this->errors[$field][] = $custom;
            return;
        }

        $attribute = trans('errors.attributes.' . $field);
        $attribute = $attribute === 'errors.attributes.' . $field ? $field : $attribute;

        $params = ['attribute' => $attribute, 'param' => (string) $parameter];
        $message = trans('errors.validation.' . $errorKey, $params);

        $this->errors[$field][] = $message === 'errors.validation.' . $errorKey
            ? sprintf('The %s field failed the %s rule.', $attribute, $rule)
            : $message;
    }
}
