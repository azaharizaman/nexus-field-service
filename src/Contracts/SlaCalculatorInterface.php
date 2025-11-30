<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * SLA Calculator Interface
 *
 * Calculates SLA deadlines from service contract terms.
 */
interface SlaCalculatorInterface
{
    /**
     * Calculate SLA deadline from response time and priority.
     *
     * @param string $responseTime e.g., "4 hours", "24 hours"
     * @param string $priority WorkOrderPriority value
     * @param \DateTimeImmutable|null $from Calculate from this time (defaults to now)
     */
    public function calculateDeadline(
        string $responseTime,
        string $priority,
        ?\DateTimeImmutable $from = null
    ): \DateTimeImmutable;

    /**
     * Parse response time string to hours.
     *
     * @param string $responseTime e.g., "4 hours", "2 days"
     */
    public function parseResponseTime(string $responseTime): float;
}
