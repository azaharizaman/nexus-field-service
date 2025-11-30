<?php

declare(strict_types=1);

namespace Nexus\FieldService\Core\Routing;

use Nexus\FieldService\Contracts\RouteOptimizerInterface;
use Nexus\FieldService\Contracts\WorkOrderInterface;

/**
 * Default Route Optimizer (Tier 1 - No-op Implementation)
 *
 * Returns work orders in their original order without optimization.
 * Tier 3 implementation will use Nexus\Routing VRP solver.
 */
final readonly class DefaultRouteOptimizer implements RouteOptimizerInterface
{
    public function optimizeRoute(
        string $technicianId,
        array $workOrders,
        \DateTimeImmutable $date
    ): array {
        // Sort by scheduled start time (if available)
        usort($workOrders, function (WorkOrderInterface $a, WorkOrderInterface $b) {
            $aStart = $a->getScheduledStart();
            $bStart = $b->getScheduledStart();
            
            if ($aStart === null && $bStart === null) {
                return 0;
            }
            
            if ($aStart === null) {
                return 1;
            }
            
            if ($bStart === null) {
                return -1;
            }
            
            return $aStart <=> $bStart;
        });

        return $workOrders;
    }

    public function estimateCompletionTime(
        string $technicianId,
        array $workOrders
    ): \DateTimeImmutable {
        if (empty($workOrders)) {
            return new \DateTimeImmutable();
        }

        // Simple estimation: add 2 hours per work order
        $hoursNeeded = count($workOrders) * 2;
        
        $firstStart = null;
        foreach ($workOrders as $workOrder) {
            if ($workOrder->getScheduledStart() !== null) {
                $firstStart = $workOrder->getScheduledStart();
                break;
            }
        }

        $startTime = $firstStart ?? new \DateTimeImmutable();
        
        return $startTime->modify("+{$hoursNeeded} hours");
    }
}
