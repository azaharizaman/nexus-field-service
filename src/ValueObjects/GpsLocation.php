<?php

declare(strict_types=1);

namespace Nexus\FieldService\ValueObjects;

use Nexus\FieldService\Exceptions\InvalidGpsLocationException;

/**
 * GPS Location Value Object
 *
 * Immutable representation of a geographic coordinate with timestamp.
 * Used for job start/end tracking (BUS-FIE-0107) and proximity calculations.
 */
final readonly class GpsLocation
{
    private const float MIN_LATITUDE = -90.0;
    private const float MAX_LATITUDE = 90.0;
    private const float MIN_LONGITUDE = -180.0;
    private const float MAX_LONGITUDE = 180.0;
    private const int EARTH_RADIUS_KM = 6371;

    private function __construct(
        private float $latitude,
        private float $longitude,
        private \DateTimeImmutable $capturedAt,
        private ?float $accuracyMeters = null
    ) {
        if ($latitude < self::MIN_LATITUDE || $latitude > self::MAX_LATITUDE) {
            throw new InvalidGpsLocationException(
                sprintf('Latitude must be between %f and %f', self::MIN_LATITUDE, self::MAX_LATITUDE)
            );
        }

        if ($longitude < self::MIN_LONGITUDE || $longitude > self::MAX_LONGITUDE) {
            throw new InvalidGpsLocationException(
                sprintf('Longitude must be between %f and %f', self::MIN_LONGITUDE, self::MAX_LONGITUDE)
            );
        }

        if ($accuracyMeters !== null && $accuracyMeters < 0) {
            throw new InvalidGpsLocationException('Accuracy cannot be negative');
        }
    }

    /**
     * Create a GPS location from coordinates.
     */
    public static function create(
        float $latitude,
        float $longitude,
        ?\DateTimeImmutable $capturedAt = null,
        ?float $accuracyMeters = null
    ): self {
        return new self(
            $latitude,
            $longitude,
            $capturedAt ?? new \DateTimeImmutable(),
            $accuracyMeters
        );
    }

    /**
     * Get the latitude.
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * Get the longitude.
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * Get the timestamp when this location was captured.
     */
    public function getCapturedAt(): \DateTimeImmutable
    {
        return $this->capturedAt;
    }

    /**
     * Get the accuracy in meters (if available).
     */
    public function getAccuracyMeters(): ?float
    {
        return $this->accuracyMeters;
    }

    /**
     * Calculate distance to another GPS location using Haversine formula.
     *
     * @return float Distance in kilometers
     */
    public function distanceTo(self $other): float
    {
        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($other->latitude);
        $lonTo = deg2rad($other->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Calculate distance in meters.
     */
    public function distanceToMeters(self $other): float
    {
        return $this->distanceTo($other) * 1000;
    }

    /**
     * Check if this location is within a certain radius of another location.
     *
     * @param float $radiusKm Radius in kilometers
     */
    public function isWithinRadius(self $other, float $radiusKm): bool
    {
        return $this->distanceTo($other) <= $radiusKm;
    }

    /**
     * Convert to array format suitable for JSON serialization.
     *
     * @return array{latitude: float, longitude: float, captured_at: string, accuracy_meters: float|null}
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'captured_at' => $this->capturedAt->format('c'),
            'accuracy_meters' => $this->accuracyMeters,
        ];
    }

    /**
     * Create from array format.
     *
     * @param array{latitude: float, longitude: float, captured_at?: string, accuracy_meters?: float|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['latitude'],
            $data['longitude'],
            isset($data['captured_at']) 
                ? new \DateTimeImmutable($data['captured_at']) 
                : new \DateTimeImmutable(),
            $data['accuracy_meters'] ?? null
        );
    }

    /**
     * Check if this location equals another (ignoring timestamp).
     */
    public function equals(self $other): bool
    {
        return abs($this->latitude - $other->latitude) < 0.000001
            && abs($this->longitude - $other->longitude) < 0.000001;
    }

    public function __toString(): string
    {
        return sprintf('%.6f, %.6f', $this->latitude, $this->longitude);
    }
}
