<?php

declare(strict_types=1);

namespace Nexus\FieldService\ValueObjects;

use Nexus\FieldService\Exceptions\InvalidWorkOrderNumberException;

/**
 * Work Order Number Value Object
 *
 * Immutable representation of a work order number following the format: WO-YYYY-NNNNN
 *
 * @example WO-2025-00001
 * @example WO-2025-12345
 */
final readonly class WorkOrderNumber
{
    private const string PATTERN = '/^WO-\d{4}-\d{5}$/';
    private const string PREFIX = 'WO';

    private function __construct(
        private string $value
    ) {
        if (!preg_match(self::PATTERN, $value)) {
            throw new InvalidWorkOrderNumberException(
                "Invalid work order number format: {$value}. Expected format: WO-YYYY-NNNNN"
            );
        }
    }

    /**
     * Create a WorkOrderNumber from a string value.
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Generate a new work order number for the given year and sequence.
     */
    public static function generate(int $year, int $sequence): self
    {
        $paddedSequence = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        return new self(sprintf('%s-%d-%s', self::PREFIX, $year, $paddedSequence));
    }

    /**
     * Get the string representation of the work order number.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Get the year from the work order number.
     */
    public function getYear(): int
    {
        preg_match('/WO-(\d{4})-\d{5}/', $this->value, $matches);
        return (int) $matches[1];
    }

    /**
     * Get the sequence number from the work order number.
     */
    public function getSequence(): int
    {
        preg_match('/WO-\d{4}-(\d{5})/', $this->value, $matches);
        return (int) $matches[1];
    }

    /**
     * Check if this work order number equals another.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
