<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\FieldService\Enums\ChecklistItemType;

/**
 * Checklist Item Interface
 *
 * Represents a single item in a checklist template.
 */
interface ChecklistItemInterface
{
    public function getId(): string;

    public function getLabel(): string;

    public function getType(): ChecklistItemType;

    public function getInstructions(): ?string;

    /**
     * Check if this item blocks work order completion when failed.
     */
    public function isCritical(): bool;
}
