<?php

declare(strict_types=1);

namespace Nexus\FieldService\ValueObjects;

use Nexus\FieldService\Exceptions\InvalidTimeWindowException;

/**
 * Service Time Window Value Object
 *
 * Immutable representation of a scheduled appointment time range.
 * Used for route optimization constraints (BUS-FIE-0122).
 *
 * @example 09:00 - 11:00 (2-hour window)
 */
final readonly class ServiceTimeWindow
{
    private function __construct(
        private \DateTimeImmutable $start,
        private \DateTimeImmutable $end
    ) {
        if ($end <= $start) {
            throw new InvalidTimeWindowException(
                'Time window end must be after start'
            );
        }
    }

    /**
     * Create a time window from start and end times.
     */
    public static function create(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): self {
        return new self($start, $end);
    }

    /**
     * Create a time window from a start time and duration in hours.
     */
    public static function fromDuration(
        \DateTimeImmutable $start,
        float $durationHours
    ): self {
        $end = $start->modify(sprintf('+%d minutes', (int)($durationHours * 60)));
        return new self($start, $end);
    }

    /**
     * Get the start time of the window.
     */
    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * Get the end time of the window.
     */
    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * Get the duration of the time window in hours.
     */
    public function getDurationHours(): float
    {
        $diff = $this->end->getTimestamp() - $this->start->getTimestamp();
        return $diff / 3600;
    }

    /**
     * Get the duration of the time window in minutes.
     */
    public function getDurationMinutes(): int
    {
        $diff = $this->end->getTimestamp() - $this->start->getTimestamp();
        return (int)($diff / 60);
    }

    /**
     * Check if a given time falls within this window.
     */
    public function contains(\DateTimeImmutable $time): bool
    {
        return $time >= $this->start && $time <= $this->end;
    }

    /**
     * Check if this time window overlaps with another.
     */
    public function overlaps(self $other): bool
    {
        return $this->start < $other->end && $this->end > $other->start;
    }

    /**
     * Check if this time window is entirely before another.
     */
    public function isBefore(self $other): bool
    {
        return $this->end <= $other->start;
    }

    /**
     * Check if this time window is entirely after another.
     */
    public function isAfter(self $other): bool
    {
        return $this->start >= $other->end;
    }

    /**
     * Get the midpoint of the time window.
     */
    public function getMidpoint(): \DateTimeImmutable
    {
        $midTimestamp = ($this->start->getTimestamp() + $this->end->getTimestamp()) / 2;
        return (new \DateTimeImmutable())->setTimestamp((int)$midTimestamp);
    }

    /**
     * Check if this time window equals another.
     */
    public function equals(self $other): bool
    {
        return $this->start == $other->start && $this->end == $other->end;
    }

    /**
     * Get a human-readable string representation.
     */
    public function toString(string $format = 'H:i'): string
    {
        return sprintf(
            '%s - %s',
            $this->start->format($format),
            $this->end->format($format)
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
