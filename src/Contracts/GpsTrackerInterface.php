<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\FieldService\ValueObjects\GpsLocation;

/**
 * GPS Tracker Interface
 *
 * Captures and stores GPS locations with privacy controls.
 * Auto-purges data after 90 days per GDPR compliance (SEC-FIE-0462).
 */
interface GpsTrackerInterface
{
    /**
     * Capture GPS location for work order event.
     *
     * @param string $eventType 'job_start' | 'job_end'
     */
    public function captureLocation(
        string $workOrderId,
        string $technicianId,
        string $eventType,
        GpsLocation $location
    ): void;

    /**
     * Get all GPS locations for a work order.
     *
     * @return array<array{event_type: string, location: GpsLocation, captured_at: \DateTimeImmutable}>
     */
    public function getLocations(string $workOrderId): array;

    /**
     * Schedule automatic purge of GPS data after retention period.
     */
    public function schedulePurge(
        string $workOrderId,
        \DateTimeImmutable $purgeAfter
    ): void;
}
