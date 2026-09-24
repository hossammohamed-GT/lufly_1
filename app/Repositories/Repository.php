<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\AuditService;
use Core\Contracts\RepositoryInterface;
use Core\Database\Model;
use Core\Database\Paginator;

abstract class Repository implements RepositoryInterface
{
    protected string $model;

    protected bool $auditing = false;

    protected string $auditEntity = '';

    public function find(int|string $id): ?object
    {
        $class = $this->model;

        return $class::find($id);
    }

    public function all(array $orderBy = ['id' => 'desc'], int $limit = 0): array
    {
        $class = $this->model;

        return $class::all($orderBy, $limit);
    }

    public function create(array $data): object
    {
        $class = $this->model;
        $entity = $class::create($data);

        if ($this->auditing) {
            app(AuditService::class)->record($this->auditEntity, $entity->getKey(), 'create', null, $data);
        }

        return $entity;
    }

    public function update(int|string $id, array $data): bool
    {
        $entity = $this->find($id);
        if ($entity === null) {
            return false;
        }

        $before = $this->auditing ? $entity->attributes() : null;
        $result = $entity->update($data);

        if ($this->auditing) {
            app(AuditService::class)->record($this->auditEntity, $id, 'update', $before, $data);
        }

        return $result;
    }

    public function delete(int|string $id): bool
    {
        $entity = $this->find($id);
        if ($entity === null) {
            return false;
        }

        $before = $this->auditing ? $entity->attributes() : null;
        $result = $entity->delete();

        if ($this->auditing) {
            app(AuditService::class)->record($this->auditEntity, $id, 'delete', $before, null);
        }

        return $result;
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 10): Paginator
    {
        $class = $this->model;
        $query = $class::query()->latest('id');

        foreach ($filters as $column => $value) {
            if ($value !== null && $value !== '') {
                $query->where((string) $column, $value);
            }
        }

        return $query->paginate($page, $perPage);
    }

    public function count(): int
    {
        $class = $this->model;

        return $class::query()->count();
    }
}
