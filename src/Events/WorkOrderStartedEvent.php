<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * Work Order Started Event
 *
 * Published when a technician starts working on a work order.
 */
final readonly class WorkOrderStartedEvent
{
    public function __construct(
        private string $workOrderId,
        private string $technicianId,
        private \DateTimeImmutable $actualStart,
        private ?array $gpsLocation = null
    ) {
    }

    public function getWorkOrderId(): string
    {
        return $this->workOrderId;
    }

    public function getTechnicianId(): string
    {
        return $this->technicianId;
    }

    public function getActualStart(): \DateTimeImmutable
    {
        return $this->actualStart;
    }

    public function getGpsLocation(): ?array
    {
        return $this->gpsLocation;
    }
}
