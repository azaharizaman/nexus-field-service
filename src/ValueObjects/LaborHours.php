<?php

declare(strict_types=1);

namespace Nexus\FieldService\ValueObjects;

use Nexus\FieldService\Exceptions\InvalidLaborHoursException;

/**
 * Labor Hours Value Object
 *
 * Immutable representation of labor hours worked with optional hourly rate.
 * Used to calculate labor costs for work orders.
 */
final readonly class LaborHours
{
    private function __construct(
        private float $hours,
        private ?float $hourlyRate = null,
        private string $currency = 'MYR'
    ) {
        if ($hours < 0) {
            throw new InvalidLaborHoursException('Labor hours cannot be negative');
        }

        if ($hourlyRate !== null && $hourlyRate < 0) {
            throw new InvalidLaborHoursException('Hourly rate cannot be negative');
        }
    }

    /**
     * Create labor hours from hours worked.
     */
    public static function create(
        float $hours,
        ?float $hourlyRate = null,
        string $currency = 'MYR'
    ): self {
        return new self($hours, $hourlyRate, $currency);
    }

    /**
     * Create labor hours from start and end times.
     */
    public static function fromTimeRange(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        ?float $hourlyRate = null,
        string $currency = 'MYR'
    ): self {
        $diff = $end->getTimestamp() - $start->getTimestamp();
        $hours = $diff / 3600;

        return new self($hours, $hourlyRate, $currency);
    }

    /**
     * Create zero labor hours.
     */
    public static function zero(): self
    {
        return new self(0.0);
    }

    /**
     * Get the number of hours.
     */
    public function getHours(): float
    {
        return $this->hours;
    }

    /**
     * Get the hourly rate.
     */
    public function getHourlyRate(): ?float
    {
        return $this->hourlyRate;
    }

    /**
     * Get the currency code.
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Calculate total labor cost.
     *
     * @return float|null Returns null if hourly rate is not set
     */
    public function getTotalCost(): ?float
    {
        if ($this->hourlyRate === null) {
            return null;
        }

        return $this->hours * $this->hourlyRate;
    }

    /**
     * Get hours rounded to nearest quarter hour (0.25).
     */
    public function getRoundedHours(): float
    {
        return round($this->hours * 4) / 4;
    }

    /**
     * Convert hours to minutes.
     */
    public function toMinutes(): int
    {
        return (int) ($this->hours * 60);
    }

    /**
     * Add hours to this labor hours, returning a new instance.
     */
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidLaborHoursException('Cannot add labor hours with different currencies');
        }

        // Use the hourly rate from this instance if both have rates
        $rate = $this->hourlyRate ?? $other->hourlyRate;

        return new self(
            $this->hours + $other->hours,
            $rate,
            $this->currency
        );
    }

    /**
     * Check if labor hours are billable (has hourly rate).
     */
    public function isBillable(): bool
    {
        return $this->hourlyRate !== null;
    }

    /**
     * Check if this equals another labor hours instance.
     */
    public function equals(self $other): bool
    {
        return abs($this->hours - $other->hours) < 0.01
            && $this->hourlyRate === $other->hourlyRate
            && $this->currency === $other->currency;
    }

    /**
     * Get a human-readable string representation.
     */
    public function toString(): string
    {
        $hoursFormatted = number_format($this->hours, 2);
        
        if ($this->hourlyRate === null) {
            return "{$hoursFormatted} hours";
        }

        $rateFormatted = number_format($this->hourlyRate, 2);
        $totalCost = $this->getTotalCost();
        $totalFormatted = number_format($totalCost, 2);

        return "{$hoursFormatted} hours @ {$this->currency} {$rateFormatted}/hr = {$this->currency} {$totalFormatted}";
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
