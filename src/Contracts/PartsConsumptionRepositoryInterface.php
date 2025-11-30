<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Parts Consumption Repository Interface
 *
 * Defines persistence operations for parts consumption records.
 */
interface PartsConsumptionRepositoryInterface
{
    /**
     * Find parts consumption records for a work order.
     *
     * @return array<PartsConsumptionInterface>
     */
    public function findByWorkOrder(string $workOrderId): array;

    /**
     * Get total parts cost for a work order.
     */
    public function getTotalCost(string $workOrderId): float;

    /**
     * Save parts consumption record.
     */
    public function save(PartsConsumptionInterface $consumption): void;
}
