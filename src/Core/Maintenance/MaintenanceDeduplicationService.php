<?php

declare(strict_types=1);

namespace Nexus\FieldService\Core\Maintenance;

use Nexus\FieldService\Contracts\MaintenanceDeduplicationInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Maintenance Deduplication Service (Tier 2/3)
 *
 * Prevents duplicate preventive maintenance work orders.
 * Checks for existing work orders within ±3 days of planned date.
 */
final readonly class MaintenanceDeduplicationService implements MaintenanceDeduplicationInterface
{
    private const int DEFAULT_TOLERANCE_DAYS = 3;

    public function __construct(
        private WorkOrderRepositoryInterface $workOrderRepository,
        private LoggerInterface $logger
    ) {
    }

    public function shouldCreateWorkOrder(
        string $assetId,
        string $serviceType,
        \DateTimeImmutable $plannedDate
    ): bool {
        $conflicts = $this->findConflicts($assetId, $serviceType, $plannedDate);
        
        if (!empty($conflicts)) {
            $this->logger->warning('Preventive maintenance work order creation skipped due to conflicts', [
                'asset_id' => $assetId,
                'service_type' => $serviceType,
                'planned_date' => $plannedDate->format('Y-m-d'),
                'conflict_count' => count($conflicts),
            ]);
            
            return false;
        }

        return true;
    }

    public function findConflicts(
        string $assetId,
        string $serviceType,
        \DateTimeImmutable $plannedDate,
        int $toleranceDays = self::DEFAULT_TOLERANCE_DAYS
    ): array {
        // Calculate date range
        $startDate = $plannedDate->modify("-{$toleranceDays} days");
        $endDate = $plannedDate->modify("+{$toleranceDays} days");

        // Find existing work orders for this asset in the date range
        $existingWorkOrders = $this->workOrderRepository->findByAssetAndDateRange(
            $assetId,
            $startDate,
            $endDate
        );

        // Filter by matching service type
        $conflicts = array_filter(
            $existingWorkOrders,
            fn($wo) => $wo->getServiceType()->value === $serviceType
        );

        return array_values($conflicts);
    }
}
