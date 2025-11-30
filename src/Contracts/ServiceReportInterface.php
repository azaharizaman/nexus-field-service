<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\Document\Contracts\DocumentInterface;

/**
 * Service Report Interface
 *
 * Represents a generated service report document.
 */
interface ServiceReportInterface
{
    /**
     * Get the work order this report is for.
     */
    public function getWorkOrderId(): string;

    /**
     * Get the generated PDF document.
     */
    public function getDocument(): DocumentInterface;

    /**
     * Get the timestamp when report was generated.
     */
    public function getGeneratedAt(): \DateTimeImmutable;
}
