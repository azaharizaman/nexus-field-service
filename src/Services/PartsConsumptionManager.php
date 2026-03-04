<?php

declare(strict_types=1);

namespace Nexus\FieldService\Services;

use Nexus\FieldService\Contracts\PartsConsumptionRepositoryInterface;
use Nexus\FieldService\Contracts\StockManagerInterface;
use Nexus\FieldService\Contracts\LocationManagerInterface;
use Nexus\FieldService\Events\PartsConsumedEvent;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Parts Consumption Manager Service
 *
 * Handles parts/materials consumption with van stock waterfall logic.
 * Per BUS-FIE-0066: Deduct from technician van first, then warehouse.
 */
final readonly class PartsConsumptionManager
{
    public function __construct(
        private PartsConsumptionRepositoryInterface $consumptionRepository,
        private StockManagerInterface $stockManager,
        private LocationManagerInterface $warehouseManager,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Record parts consumption for a work order.
     *
     * Executes waterfall logic:
     * 1. Check technician van stock
     * 2. Deduct available quantity from van
     * 3. Deduct remainder from primary warehouse
     */
    public function recordConsumption(
        string $workOrderId,
        string $productVariantId,
        float $quantity,
        string $technicianId
    ): void {
        $this->logger->info('Recording parts consumption', [
            'work_order_id' => $workOrderId,
            'product_variant_id' => $productVariantId,
            'quantity' => $quantity,
            'technician_id' => $technicianId,
        ]);

        // Get technician van warehouse ID
        $vanWarehouseId = $this->warehouseManager->getTechnicianVanWarehouseId($technicianId);

        // Check van stock level
        $vanStockLevel = $this->stockManager->getAvailableQuantity($vanWarehouseId, $productVariantId);

        $consumedFromVan = 0.0;
        $consumedFromWarehouse = 0.0;

        if ($vanStockLevel >= $quantity) {
            // Sufficient stock in van - deduct from van only
            $this->stockManager->issueStock($vanWarehouseId, $productVariantId, $quantity);
            $consumedFromVan = $quantity;
            
            $this->logger->info('Parts deducted from technician van', [
                'van_warehouse_id' => $vanWarehouseId,
                'quantity' => $quantity,
            ]);
        } else {
            // Partial or no stock in van - use waterfall
            if ($vanStockLevel > 0) {
                $this->stockManager->issueStock($vanWarehouseId, $productVariantId, $vanStockLevel);
                $consumedFromVan = $vanStockLevel;
                
                $this->logger->info('Partial stock deducted from technician van', [
                    'van_warehouse_id' => $vanWarehouseId,
                    'quantity' => $vanStockLevel,
                ]);
            }

            // Deduct remainder from primary warehouse
            $remainingQuantity = $quantity - $vanStockLevel;
            $primaryWarehouseId = $this->warehouseManager->getPrimaryWarehouseId();
            
            $this->stockManager->issueStock($primaryWarehouseId, $productVariantId, $remainingQuantity);
            $consumedFromWarehouse = $remainingQuantity;
            
            $this->logger->info('Remaining stock deducted from primary warehouse', [
                'warehouse_id' => $primaryWarehouseId,
                'quantity' => $remainingQuantity,
            ]);
        }

        // Dispatch event
        $event = new PartsConsumedEvent(
            $workOrderId,
            $productVariantId,
            $quantity,
            $technicianId,
            new \DateTimeImmutable(),
            $consumedFromVan,
            $consumedFromWarehouse
        );
        
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Get parts consumed for a work order.
     * 
     * @return array<PartsConsumptionInterface>
     */
    public function getTotalPartsConsumed(string $workOrderId): array
    {
        return $this->consumptionRepository->findByWorkOrder($workOrderId);
    }

    /**
     * Get total parts cost for a work order.
     */
    public function getTotalPartsCost(string $workOrderId): float
    {
        return $this->consumptionRepository->getTotalCost($workOrderId);
    }
}
