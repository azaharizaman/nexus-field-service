<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * Work Order Completed Event
 *
 * Published when a technician completes a work order.
 * Triggers GL posting for revenue and costs.
 */
final readonly class WorkOrderCompletedEvent
{
    public function __construct(
        private string $workOrderId,
        private string $technicianId,
        private \DateTimeImmutable $actualEnd,
        private float $laborHours,
        private ?float $laborCost = null,
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

    public function getActualEnd(): \DateTimeImmutable
    {
        return $this->actualEnd;
    }

    public function getLaborHours(): float
    {
        return $this->laborHours;
    }

    public function getLaborCost(): ?float
    {
        return $this->laborCost;
    }

    public function getGpsLocation(): ?array
    {
        return $this->gpsLocation;
    }
}
