<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * Parts Consumed Event
 *
 * Published when parts/materials are consumed on a work order.
 * Triggers inventory deduction via waterfall logic.
 */
final readonly class PartsConsumedEvent
{
    public function __construct(
        private string $workOrderId,
        private string $productVariantId,
        private float $quantity,
        private string $technicianId,
        private \DateTimeImmutable $consumedAt,
        private float $consumedFromVan = 0.0,
        private float $consumedFromWarehouse = 0.0
    ) {
        if ($this->consumedFromVan < 0 || $this->consumedFromWarehouse < 0) {
            throw new \InvalidArgumentException('Consumed quantities must be non-negative');
        }

        if (($this->consumedFromVan + $this->consumedFromWarehouse) > $this->quantity + 0.0001) {
            throw new \InvalidArgumentException(sprintf(
                'Sum of consumed parts (Van: %f, Warehouse: %f) exceeds total quantity (%f)',
                $this->consumedFromVan,
                $this->consumedFromWarehouse,
                $this->quantity
            ));
        }
    }

    public function getWorkOrderId(): string
    {
        return $this->workOrderId;
    }

    public function getProductVariantId(): string
    {
        return $this->productVariantId;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getTechnicianId(): string
    {
        return $this->technicianId;
    }

    public function getConsumedAt(): \DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function getConsumedFromVan(): float
    {
        return $this->consumedFromVan;
    }

    public function getConsumedFromWarehouse(): float
    {
        return $this->consumedFromWarehouse;
    }
}
