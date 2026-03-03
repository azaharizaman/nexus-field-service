<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

interface LocationManagerInterface
{
    public function getPrimaryWarehouseId(): string;

    public function getTechnicianVanWarehouseId(string $technicianId): string;
}
