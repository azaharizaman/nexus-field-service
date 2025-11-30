<?php

declare(strict_types=1);

namespace Nexus\FieldService\Enums;

/**
 * Work Order Status Enum
 *
 * Represents the lifecycle states of a work order in the field service system.
 *
 * State Transitions:
 * NEW → SCHEDULED → IN_PROGRESS → COMPLETED → VERIFIED
 *   ↓       ↓            ↓            ↓
 *   └───────┴────────────┴────────────┴─→ CANCELLED
 */
enum WorkOrderStatus: string
{
    case NEW = 'new';
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case VERIFIED = 'verified';
    case CANCELLED = 'cancelled';

    /**
     * Check if the work order can be assigned to a technician.
     */
    public function canAssign(): bool
    {
        return match ($this) {
            self::NEW, self::SCHEDULED => true,
            default => false,
        };
    }

    /**
     * Check if the work order can be started by a technician.
     */
    public function canStart(): bool
    {
        return match ($this) {
            self::SCHEDULED => true,
            default => false,
        };
    }

    /**
     * Check if the work order can be completed.
     */
    public function canComplete(): bool
    {
        return match ($this) {
            self::IN_PROGRESS => true,
            default => false,
        };
    }

    /**
     * Check if the work order can be verified (customer signature).
     */
    public function canVerify(): bool
    {
        return match ($this) {
            self::COMPLETED => true,
            default => false,
        };
    }

    /**
     * Check if the work order can be cancelled.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::NEW, self::SCHEDULED, self::IN_PROGRESS => true,
            default => false,
        };
    }

    /**
     * Check if the work order is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::VERIFIED, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * Check if the work order is active (not terminal).
     */
    public function isActive(): bool
    {
        return !$this->isTerminal();
    }

    /**
     * Get the next valid status from current state.
     */
    public function nextStatus(): ?self
    {
        return match ($this) {
            self::NEW => self::SCHEDULED,
            self::SCHEDULED => self::IN_PROGRESS,
            self::IN_PROGRESS => self::COMPLETED,
            self::COMPLETED => self::VERIFIED,
            default => null,
        };
    }

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::SCHEDULED => 'Scheduled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::VERIFIED => 'Verified',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get color code for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::NEW => 'gray',
            self::SCHEDULED => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::COMPLETED => 'green',
            self::VERIFIED => 'emerald',
            self::CANCELLED => 'red',
        };
    }
}
