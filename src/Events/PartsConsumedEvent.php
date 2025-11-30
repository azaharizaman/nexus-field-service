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
        private \DateTimeImmutable $consumedAt
    ) {
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
}
