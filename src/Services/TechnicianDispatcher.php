<?php

declare(strict_types=1);

namespace Nexus\FieldService\Services;

use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\Backoffice\Contracts\StaffRepositoryInterface;
use Nexus\FieldService\Contracts\WorkOrderInterface;
use Nexus\FieldService\Contracts\TechnicianAssignmentStrategyInterface;
use Nexus\FieldService\Contracts\RouteOptimizerInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\Exceptions\TechnicianNotAvailableException;
use Psr\Log\LoggerInterface;

/**
 * Technician Dispatcher Service
 *
 * Handles technician assignment and route optimization.
 * Uses injected strategies for assignment algorithm (Tier 1 vs Tier 3).
 */
final readonly class TechnicianDispatcher
{
    public function __construct(
        private StaffRepositoryInterface $staffRepository,
        private WorkOrderRepositoryInterface $workOrderRepository,
        private TechnicianAssignmentStrategyInterface $assignmentStrategy,
        private RouteOptimizerInterface $routeOptimizer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Find the best available technician for a work order.
     *
     * @param array<string> $technicianIds Optional: limit search to specific technicians
     */
    public function findBestTechnician(
        WorkOrderInterface $workOrder,
        ?array $technicianIds = null
    ): ?StaffInterface {
        // Get available technicians
        $availableTechnicians = $this->getAvailableTechnicians($technicianIds);

        if (empty($availableTechnicians)) {
            $this->logger->warning('No available technicians found', [
                'work_order_id' => $workOrder->getId(),
            ]);
            return null;
        }

        // Use assignment strategy to find best match
        $bestTechnician = $this->assignmentStrategy->findBestTechnician(
            $workOrder,
            $availableTechnicians
        );

        if ($bestTechnician === null) {
            $this->logger->warning('No suitable technician found', [
                'work_order_id' => $workOrder->getId(),
                'available_count' => count($availableTechnicians),
            ]);
            throw new TechnicianNotAvailableException(
                'No suitable technician available for this work order'
            );
        }

        $this->logger->info('Best technician found', [
            'work_order_id' => $workOrder->getId(),
            'technician_id' => $bestTechnician->getId(),
        ]);

        return $bestTechnician;
    }

    /**
     * Get optimized daily route for a technician.
     *
     * @return array<WorkOrderInterface>
     */
    public function getOptimizedRoute(
        string $technicianId,
        \DateTimeImmutable $date
    ): array {
        // Get all work orders for this technician on this date
        $workOrders = $this->workOrderRepository->findByTechnicianAndDate(
            $technicianId,
            $date
        );

        if (empty($workOrders)) {
            return [];
        }

        // Optimize route
        $optimizedRoute = $this->routeOptimizer->optimizeRoute(
            $technicianId,
            $workOrders,
            $date
        );

        $this->logger->info('Route optimized', [
            'technician_id' => $technicianId,
            'date' => $date->format('Y-m-d'),
            'work_order_count' => count($optimizedRoute),
        ]);

        return $optimizedRoute;
    }

    /**
     * Check if a technician is available for a work order.
     */
    public function isTechnicianAvailable(
        string $technicianId,
        WorkOrderInterface $workOrder
    ): bool {
        if ($workOrder->getScheduledStart() === null) {
            return true; // No specific date requirement
        }

        $scheduledHours = $this->workOrderRepository->getTechnicianScheduledHours(
            $technicianId,
            $workOrder->getScheduledStart()
        );

        // Assume 2 hours per work order
        $estimatedDuration = 2.0;
        $maxDailyHours = 8.0;

        return ($scheduledHours + $estimatedDuration) <= $maxDailyHours;
    }

    /**
     * Get all available technicians.
     *
     * @param array<string>|null $technicianIds
     * @return array<StaffInterface>
     */
    private function getAvailableTechnicians(?array $technicianIds = null): array
    {
        // TODO: Filter by active technicians only
        // For now, return empty array - implementation in Atomy
        return [];
    }
}
