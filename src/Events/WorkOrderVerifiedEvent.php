<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * Work Order Verified Event
 *
 * Published when a work order is verified with customer signature.
 * Triggers service report generation and customer notification.
 */
final readonly class WorkOrderVerifiedEvent
{
    public function __construct(
        private string $workOrderId,
        private string $signatureId,
        private \DateTimeImmutable $verifiedAt
    ) {
    }

    public function getWorkOrderId(): string
    {
        return $this->workOrderId;
    }

    public function getSignatureId(): string
    {
        return $this->signatureId;
    }

    public function getVerifiedAt(): \DateTimeImmutable
    {
        return $this->verifiedAt;
    }
}
