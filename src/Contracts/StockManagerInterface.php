<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

interface StockManagerInterface
{
    public function getAvailableQuantity(string $locationId, string $productVariantId): float;

    public function issueStock(string $locationId, string $productVariantId, float $quantity): void;
}
