<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * Work Order Created Event
 *
 * Published when a new work order is created in the system.
 */
final readonly class WorkOrderCreatedEvent
{
    public function __construct(
        private string $workOrderId,
        private string $customerPartyId,
        private string $serviceType,
        private string $priority,
        private \DateTimeImmutable $occurredAt
    ) {
    }

    public function getWorkOrderId(): string
    {
        return $this->workOrderId;
    }

    public function getCustomerPartyId(): string
    {
        return $this->customerPartyId;
    }

    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
