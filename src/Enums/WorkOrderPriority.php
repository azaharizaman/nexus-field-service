<?php

declare(strict_types=1);

namespace Nexus\FieldService\Enums;

/**
 * Work Order Priority Enum
 *
 * Defines the urgency level of a work order, affecting SLA calculations
 * and technician assignment priority.
 */
enum WorkOrderPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case EMERGENCY = 'emergency';

    /**
     * Get the SLA multiplier for this priority level.
     *
     * Used to adjust response time requirements from service contracts.
     * Emergency: 0.5x (half the normal time)
     * High: 0.75x
     * Normal: 1.0x
     * Low: 1.5x
     */
    public function slaMultiplier(): float
    {
        return match ($this) {
            self::EMERGENCY => 0.5,
            self::HIGH => 0.75,
            self::NORMAL => 1.0,
            self::LOW => 1.5,
        };
    }

    /**
     * Get numeric priority for sorting (higher = more urgent).
     */
    public function weight(): int
    {
        return match ($this) {
            self::EMERGENCY => 4,
            self::HIGH => 3,
            self::NORMAL => 2,
            self::LOW => 1,
        };
    }

    /**
     * Check if this priority bypasses service contract validation.
     *
     * Emergency work orders can be created even if the service contract is expired.
     */
    public function bypassesContractValidation(): bool
    {
        return $this === self::EMERGENCY;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::NORMAL => 'Normal',
            self::HIGH => 'High',
            self::EMERGENCY => 'Emergency',
        };
    }

    /**
     * Get color code for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::LOW => 'gray',
            self::NORMAL => 'blue',
            self::HIGH => 'orange',
            self::EMERGENCY => 'red',
        };
    }

    /**
     * Get icon name for UI representation.
     */
    public function icon(): string
    {
        return match ($this) {
            self::LOW => 'arrow-down',
            self::NORMAL => 'minus',
            self::HIGH => 'arrow-up',
            self::EMERGENCY => 'exclamation-triangle',
        };
    }
}
