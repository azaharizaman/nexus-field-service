<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * Customer Signature Captured Event
 *
 * Published when a customer signature is captured.
 */
final readonly class CustomerSignatureCapturedEvent
{
    public function __construct(
        private string $workOrderId,
        private string $signatureId,
        private string $technicianId,
        private \DateTimeImmutable $capturedAt,
        private ?array $gpsLocation = null
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

    public function getTechnicianId(): string
    {
        return $this->technicianId;
    }

    public function getCapturedAt(): \DateTimeImmutable
    {
        return $this->capturedAt;
    }

    public function getGpsLocation(): ?array
    {
        return $this->gpsLocation;
    }
}
