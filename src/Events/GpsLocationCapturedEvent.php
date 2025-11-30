<?php

declare(strict_types=1);

namespace Nexus\FieldService\Events;

/**
 * GPS Location Captured Event
 *
 * Published when a GPS location is captured (job start/end).
 * Triggers GDPR auto-purge scheduling.
 */
final readonly class GpsLocationCapturedEvent
{
    public function __construct(
        private string $workOrderId,
        private string $technicianId,
        private string $eventType,
        private float $latitude,
        private float $longitude,
        private \DateTimeImmutable $capturedAt,
        private ?float $accuracyMeters = null
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

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getCapturedAt(): \DateTimeImmutable
    {
        return $this->capturedAt;
    }

    public function getAccuracyMeters(): ?float
    {
        return $this->accuracyMeters;
    }
}
