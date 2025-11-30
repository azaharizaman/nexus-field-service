<?php

declare(strict_types=1);

namespace Nexus\FieldService\Enums;

/**
 * Checklist Item Type Enum
 *
 * Defines the importance level of checklist items.
 * Critical items MUST pass for work order completion (BUS-FIE-0059).
 */
enum ChecklistItemType: string
{
    case CRITICAL = 'critical';
    case OPTIONAL = 'optional';

    /**
     * Check if this item blocks work order completion when failed.
     */
    public function blocksCompletion(): bool
    {
        return $this === self::CRITICAL;
    }

    /**
     * Check if this item can be skipped.
     */
    public function isSkippable(): bool
    {
        return $this === self::OPTIONAL;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CRITICAL => 'Critical',
            self::OPTIONAL => 'Optional',
        };
    }

    /**
     * Get color code for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::CRITICAL => 'red',
            self::OPTIONAL => 'gray',
        };
    }

    /**
     * Get icon name for UI representation.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CRITICAL => 'exclamation-circle',
            self::OPTIONAL => 'information-circle',
        };
    }
}
