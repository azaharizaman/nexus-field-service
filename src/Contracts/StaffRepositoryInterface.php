<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

interface StaffRepositoryInterface
{
    /**
     * @param array<string>|null $staffIds
     * @return array<StaffInterface>
     */
    public function findAvailable(?array $staffIds = null): array;
}
