<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Parts Consumption Interface
 *
 * Represents parts/materials consumed on a work order.
 */
interface PartsConsumptionInterface
{
    public function getId(): string;

    public function getWorkOrderId(): string;

    public function getProductVariantId(): string;

    public function getQuantity(): float;

    /**
     * Get the source warehouse/van ID where parts were deducted.
     */
    public function getSourceWarehouseId(): string;

    public function getUnitCost(): float;

    public function getCurrency(): string;

    /**
     * Get total cost (quantity * unit cost).
     */
    public function getTotalCost(): float;

    public function getCreatedAt(): \DateTimeImmutable;
}
