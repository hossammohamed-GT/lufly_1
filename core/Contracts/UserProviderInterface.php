<?php

declare(strict_types=1);

namespace Core\Contracts;

interface UserProviderInterface
{
    public function findByEmail(string $email): ?object;

    public function findById(int|string $id): ?object;

    public function permissionsFor(int|string $userId): array;
}
