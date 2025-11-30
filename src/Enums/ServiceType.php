<?php

declare(strict_types=1);

namespace Nexus\FieldService\Enums;

/**
 * Service Type Enum
 *
 * Categorizes the type of field service work being performed.
 */
enum ServiceType: string
{
    case INSTALLATION = 'installation';
    case REPAIR = 'repair';
    case MAINTENANCE = 'maintenance';
    case INSPECTION = 'inspection';
    case EMERGENCY = 'emergency';
    case CONSULTATION = 'consultation';

    /**
     * Check if this service type requires parts consumption.
     */
    public function requiresParts(): bool
    {
        return match ($this) {
            self::INSTALLATION, self::REPAIR, self::MAINTENANCE => true,
            self::INSPECTION, self::CONSULTATION => false,
            self::EMERGENCY => true, // Emergency may require parts
        };
    }

    /**
     * Check if this service type is typically scheduled (vs. reactive).
     */
    public function isScheduled(): bool
    {
        return match ($this) {
            self::MAINTENANCE, self::INSPECTION, self::CONSULTATION => true,
            self::INSTALLATION, self::REPAIR, self::EMERGENCY => false,
        };
    }

    /**
     * Check if this service type requires a checklist.
     */
    public function requiresChecklist(): bool
    {
        return match ($this) {
            self::INSTALLATION, self::MAINTENANCE, self::INSPECTION => true,
            default => false,
        };
    }

    /**
     * Get estimated duration multiplier (relative to base service time).
     */
    public function durationMultiplier(): float
    {
        return match ($this) {
            self::INSTALLATION => 2.0,
            self::REPAIR => 1.5,
            self::MAINTENANCE => 1.0,
            self::INSPECTION => 0.75,
            self::EMERGENCY => 1.5,
            self::CONSULTATION => 0.5,
        };
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::INSTALLATION => 'Installation',
            self::REPAIR => 'Repair',
            self::MAINTENANCE => 'Maintenance',
            self::INSPECTION => 'Inspection',
            self::EMERGENCY => 'Emergency',
            self::CONSULTATION => 'Consultation',
        };
    }

    /**
     * Get icon name for UI representation.
     */
    public function icon(): string
    {
        return match ($this) {
            self::INSTALLATION => 'wrench',
            self::REPAIR => 'tool',
            self::MAINTENANCE => 'cog',
            self::INSPECTION => 'clipboard-check',
            self::EMERGENCY => 'exclamation-circle',
            self::CONSULTATION => 'chat',
        };
    }
}
