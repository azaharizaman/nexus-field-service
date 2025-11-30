<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * Preventive Maintenance Skipped Event
 *
 * Published when automatic PM work order generation is skipped due to duplicate detection.
 * Logged via AuditLogger for scheduler review.
 */
final readonly class PreventiveMaintenanceSkippedEvent
{
    public function __construct(
        private string $assetId,
        private string $serviceContractId,
        private \DateTimeImmutable $plannedDate,
        private string $conflictingWorkOrderId,
        private string $reason
    ) {
    }

    public function getAssetId(): string
    {
        return $this->assetId;
    }

    public function getServiceContractId(): string
    {
        return $this->serviceContractId;
    }

    public function getPlannedDate(): \DateTimeImmutable
    {
        return $this->plannedDate;
    }

    public function getConflictingWorkOrderId(): string
    {
        return $this->conflictingWorkOrderId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
