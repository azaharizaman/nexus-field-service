<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * Work Order Assigned Event
 *
 * Published when a work order is assigned to a technician.
 */
final readonly class WorkOrderAssignedEvent
{
    public function __construct(
        private string $workOrderId,
        private string $technicianId,
        private \DateTimeImmutable $scheduledStart,
        private \DateTimeImmutable $occurredAt
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

    public function getScheduledStart(): \DateTimeImmutable
    {
        return $this->scheduledStart;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
