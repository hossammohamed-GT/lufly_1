<?php

declare(strict_types=1);

namespace Core\Database;

class Paginator
{
    public function __construct(
        private readonly array $items,
        private readonly int $total,
        private readonly int $page,
        private readonly int $perPage,
    ) {
    }

    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    public function hasMorePages(): bool
    {
        return $this->page < $this->lastPage();
    }
}
