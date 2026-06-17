<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * CatalogRepository
 *
 * Handles all CRUD operations for categories, brands, and units.
 * No business logic — pure data access only.
 */
final class CatalogRepository extends BaseRepository
{
    // =========================================================================
    // Categories
    // =========================================================================

    /**
     * Return all non-deleted categories, optionally filtered by name search.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allCategories(string $search = ''): array
    {
        if ($search !== '') {
            return $this->fetchAll(
                "SELECT c.*, p.name AS parent_name
                 FROM categories c
                 LEFT JOIN categories p ON p.id = c.parent_id AND p.deleted_at IS NULL
                 WHERE c.deleted_at IS NULL
                   AND c.name LIKE :search
                 ORDER BY c.name ASC",
                [':search' => '%' . $search . '%'],
            );
        }

        return $this->fetchAll(
            "SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id AND p.deleted_at IS NULL
             WHERE c.deleted_at IS NULL
             ORDER BY c.name ASC",
        );
    }

    /**
     * Find a single category by primary key (excluding soft-deleted).
     *
     * @return array<string, mixed>|null
     */
    public function findCategory(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT c.*, p.name AS parent_name
             FROM categories c
             LEFT JOIN categories p ON p.id = c.parent_id AND p.deleted_at IS NULL
             WHERE c.id = :id AND c.deleted_at IS NULL
             LIMIT 1",
            [':id' => $id],
        );
    }

    /**
     * Insert a new category and return its generated ID.
     *
     * @param  array<string, mixed> $data
     */
    public function createCategory(array $data): int
    {
        $this->execute(
            "INSERT INTO categories
                (name, slug, description, parent_id, status, created_at, updated_at)
             VALUES
                (:name, :slug, :description, :parent_id, :status, NOW(), NOW())",
            [
                ':name'        => $data['name'],
                ':slug'        => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':parent_id'   => $data['parent_id']   ?? null,
                ':status'      => $data['status']       ?? 'active',
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing category row.
     *
     * @param  array<string, mixed> $data
     */
    public function updateCategory(int $id, array $data): void
    {
        $this->execute(
            "UPDATE categories
             SET name        = :name,
                 slug        = :slug,
                 description = :description,
                 parent_id   = :parent_id,
                 status      = :status,
                 updated_at  = NOW()
             WHERE id = :id AND deleted_at IS NULL",
            [
                ':name'        => $data['name'],
                ':slug'        => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':parent_id'   => $data['parent_id']   ?? null,
                ':status'      => $data['status']       ?? 'active',
                ':id'          => $id,
            ],
        );
    }

    /**
     * Soft-delete a category by setting deleted_at.
     */
    public function softDeleteCategory(int $id): void
    {
        $this->execute(
            "UPDATE categories
             SET deleted_at = NOW(), updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL",
            [':id' => $id],
        );
    }

    // =========================================================================
    // Brands
    // =========================================================================

    /**
     * Return all non-deleted brands, optionally filtered by name search.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allBrands(string $search = ''): array
    {
        if ($search !== '') {
            return $this->fetchAll(
                "SELECT * FROM brands WHERE name LIKE :search ORDER BY name ASC",
                [':search' => '%' . $search . '%'],
            );
        }

        return $this->fetchAll("SELECT * FROM brands ORDER BY name ASC");
    }

    /**
     * Find a single brand by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findBrand(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM brands WHERE id = :id LIMIT 1",
            [':id' => $id],
        );
    }

    /**
     * Insert a new brand and return its generated ID.
     *
     * @param  array<string, mixed> $data
     */
    public function createBrand(array $data): int
    {
        $this->execute(
            "INSERT INTO brands
                (name, slug, description, logo, status, created_at, updated_at)
             VALUES
                (:name, :slug, :description, :logo, :status, NOW(), NOW())",
            [
                ':name'        => $data['name'],
                ':slug'        => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':logo'        => $data['logo'] ?? $data['image'] ?? null,
                ':status'      => $data['status'] ?? 'active',
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing brand row.
     *
     * @param  array<string, mixed> $data
     */
    public function updateBrand(int $id, array $data): void
    {
        $this->execute(
            "UPDATE brands
             SET name        = :name,
                 slug        = :slug,
                 description = :description,
                 logo        = :logo,
                 status      = :status,
                 updated_at  = NOW()
             WHERE id = :id",
            [
                ':name'        => $data['name'],
                ':slug'        => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':logo'        => $data['logo'] ?? $data['image'] ?? null,
                ':status'      => $data['status'] ?? 'active',
                ':id'          => $id,
            ],
        );
    }

    /**
     * Delete a brand row.
     */
    public function softDeleteBrand(int $id): void
    {
        $this->execute("DELETE FROM brands WHERE id = :id", [':id' => $id]);
    }

    // =========================================================================
    // Units
    // =========================================================================

    /**
     * Return all non-deleted units, optionally filtered by name search.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allUnits(string $search = ''): array
    {
        if ($search !== '') {
            return $this->fetchAll(
                "SELECT * FROM units
                 WHERE (name LIKE :search OR abbreviation LIKE :search)
                 ORDER BY name ASC",
                [':search' => '%' . $search . '%'],
            );
        }

        return $this->fetchAll("SELECT * FROM units ORDER BY name ASC");
    }

    /**
     * Find a single unit by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function findUnit(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM units WHERE id = :id LIMIT 1",
            [':id' => $id],
        );
    }

    /**
     * Insert a new unit and return its generated ID.
     *
     * @param  array<string, mixed> $data
     */
    public function createUnit(array $data): int
    {
        $this->execute(
            "INSERT INTO units
                (name, abbreviation, created_at, updated_at)
             VALUES
                (:name, :abbreviation, NOW(), NOW())",
            [
                ':name'         => $data['name'],
                ':abbreviation' => $data['abbreviation'] ?? $data['symbol'] ?? '',
            ],
        );

        return $this->lastInsertId();
    }

    /**
     * Update an existing unit row.
     *
     * @param  array<string, mixed> $data
     */
    public function updateUnit(int $id, array $data): void
    {
        $this->execute(
            "UPDATE units
             SET name         = :name,
                 abbreviation = :abbreviation,
                 updated_at   = NOW()
             WHERE id = :id",
            [
                ':name'         => $data['name'],
                ':abbreviation' => $data['abbreviation'] ?? $data['symbol'] ?? '',
                ':id'           => $id,
            ],
        );
    }

    /**
     * Delete a unit row.
     */
    public function softDeleteUnit(int $id): void
    {
        $this->execute("DELETE FROM units WHERE id = :id", [':id' => $id]);
    }
}
