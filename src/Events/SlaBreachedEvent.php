<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * SLA Breached Event
 *
 * Published when a work order breaches its SLA deadline.
 * Triggers escalation workflow.
 */
final readonly class SlaBreachedEvent
{
    public function __construct(
        private string $workOrderId,
        private \DateTimeImmutable $slaDeadline,
        private \DateTimeImmutable $breachedAt,
        private string $reason
    ) {
    }

    public function getWorkOrderId(): string
    {
        return $this->workOrderId;
    }

    public function getSlaDeadline(): \DateTimeImmutable
    {
        return $this->slaDeadline;
    }

    public function getBreachedAt(): \DateTimeImmutable
    {
        return $this->breachedAt;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
