<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Route Optimizer Interface
 *
 * Defines route optimization for technician daily schedules.
 * Tier 1: No-op (returns jobs in original order)
 * Tier 3: VRP optimization via Nexus\Routing
 */
interface RouteOptimizerInterface
{
    /**
     * Optimize the sequence of work orders for a technician's daily route.
     *
     * Respects:
     * - Service time windows (BUS-FIE-0122)
     * - Travel time between locations
     * - Priority ordering
     *
     * @param array<WorkOrderInterface> $workOrders
     * @return array<WorkOrderInterface> Optimized sequence
     */
    public function optimizeRoute(
        string $technicianId,
        array $workOrders,
        \DateTimeImmutable $date
    ): array;

    /**
     * Calculate estimated completion time for a route.
     */
    public function estimateCompletionTime(
        string $technicianId,
        array $workOrders
    ): \DateTimeImmutable;
}
