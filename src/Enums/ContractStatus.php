<?php

declare(strict_types=1);

namespace Nexus\FieldService\Enums;

/**
 * Service Contract Status Enum
 *
 * Represents the lifecycle states of a service contract.
 */
enum ContractStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case RENEWED = 'renewed';

    /**
     * Check if the contract allows new work orders to be created.
     */
    public function allowsWorkOrders(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            default => false,
        };
    }

    /**
     * Check if the contract allows emergency work orders.
     *
     * Per BUS-FIE-0117: Expired contracts prevent new work orders unless emergency.
     */
    public function allowsEmergencyWorkOrders(): bool
    {
        return match ($this) {
            self::ACTIVE, self::EXPIRED => true,
            default => false,
        };
    }

    /**
     * Check if the contract is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::CANCELLED, self::RENEWED => true,
            default => false,
        };
    }

    /**
     * Check if the contract can be renewed.
     */
    public function canRenew(): bool
    {
        return match ($this) {
            self::ACTIVE, self::EXPIRED => true,
            default => false,
        };
    }

    /**
     * Check if the contract can be suspended.
     */
    public function canSuspend(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the contract can be cancelled.
     */
    public function canCancel(): bool
    {
        return match ($this) {
            self::DRAFT, self::ACTIVE, self::SUSPENDED => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
            self::RENEWED => 'Renewed',
        };
    }

    /**
     * Get color code for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::ACTIVE => 'green',
            self::EXPIRED => 'orange',
            self::SUSPENDED => 'yellow',
            self::CANCELLED => 'red',
            self::RENEWED => 'blue',
        };
    }
}
