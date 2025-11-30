<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Maintenance Deduplication Interface
 *
 * Prevents duplicate preventive maintenance work orders.
 * Checks for existing work orders within ±3 days of planned date.
 */
interface MaintenanceDeduplicationInterface
{
    /**
     * Check if a preventive maintenance work order should be created.
     *
     * Returns true if no conflicting work orders found.
     */
    public function shouldCreateWorkOrder(
        string $assetId,
        string $serviceType,
        \DateTimeImmutable $plannedDate
    ): bool;

    /**
     * Find conflicting work orders for the given criteria.
     *
     * @return array<WorkOrderInterface>
     */
    public function findConflicts(
        string $assetId,
        string $serviceType,
        \DateTimeImmutable $plannedDate,
        int $toleranceDays = 3
    ): array;
}
