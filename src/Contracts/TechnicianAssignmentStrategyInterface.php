<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\Backoffice\Contracts\StaffInterface;

/**
 * Technician Assignment Strategy Interface
 *
 * Defines the algorithm for assigning technicians to work orders.
 * Tier 1: Default strategy (skill match + proximity + capacity)
 * Tier 3: ML-powered strategy (predictive scoring + VRP optimization)
 */
interface TechnicianAssignmentStrategyInterface
{
    /**
     * Find the best technician for a work order.
     *
     * Considers:
     * - Required skills match
     * - Current location proximity
     * - Daily capacity availability
     * - Existing route optimization (Tier 3)
     *
     * @param array<StaffInterface> $availableTechnicians
     * @return StaffInterface|null Returns null if no suitable technician found
     */
    public function findBestTechnician(
        WorkOrderInterface $workOrder,
        array $availableTechnicians
    ): ?StaffInterface;

    /**
     * Score a technician's suitability for a work order (0-100).
     */
    public function scoreTechnician(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): float;
}
