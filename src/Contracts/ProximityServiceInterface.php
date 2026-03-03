<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

interface ProximityServiceInterface
{
    public function distanceInKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float;
}
