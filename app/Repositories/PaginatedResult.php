<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Generic paginated result wrapper.
 *
 * Returned by any repository method that supports pagination.
 */
final class PaginatedResult
{
    /**
     * @param  array  $items    The items on the current page.
     * @param  int    $total    Total number of matching records.
     * @param  int    $page     Current (1-based) page number.
     * @param  int    $perPage  Number of items per page.
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    /**
     * Total number of pages.
     */
    public function lastPage(): int
    {
        if ($this->perPage <= 0) {
            return 1;
        }
        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * Returns true when there is a next page.
     */
    public function hasMorePages(): bool
    {
        return $this->page < $this->lastPage();
    }

    /**
     * Returns true when this is the first page.
     */
    public function onFirstPage(): bool
    {
        return $this->page <= 1;
    }

    /**
     * Convert to a plain array suitable for JSON serialisation.
     */
    public function toArray(): array
    {
        return [
            'data'         => $this->items,
            'total'        => $this->total,
            'per_page'     => $this->perPage,
            'current_page' => $this->page,
            'last_page'    => $this->lastPage(),
            'has_more'     => $this->hasMorePages(),
        ];
    }
}
