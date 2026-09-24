<?php

declare(strict_types=1);

namespace Core\Contracts;

use Core\Database\Paginator;

interface RepositoryInterface
{
    public function find(int|string $id): ?object;

    public function all(array $orderBy = ['id' => 'desc'], int $limit = 0): array;

    public function create(array $data): object;

    public function update(int|string $id, array $data): bool;

    public function delete(int|string $id): bool;

    public function paginate(array $filters = [], int $page = 1, int $perPage = 10): Paginator;
}
