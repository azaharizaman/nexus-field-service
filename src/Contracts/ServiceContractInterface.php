<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\FieldService\Enums\ContractStatus;

/**
 * Service Contract Interface
 *
 * Represents a service contract with SLA terms.
 */
interface ServiceContractInterface
{
    public function getId(): string;

    public function getContractNumber(): string;

    public function getCustomerPartyId(): string;

    public function getAssetId(): ?string;

    public function getStatus(): ContractStatus;

    public function getStartDate(): \DateTimeImmutable;

    public function getEndDate(): \DateTimeImmutable;

    /**
     * Get response time (e.g., "4 hours", "24 hours").
     */
    public function getResponseTime(): string;

    /**
     * Get maintenance interval in days (for preventive maintenance).
     */
    public function getMaintenanceIntervalDays(): ?int;

    public function getContractValue(): float;

    public function getCurrency(): string;

    /**
     * Get covered service types.
     *
     * @return array<string>
     */
    public function getCoveredServices(): array;

    /**
     * Check if the contract is currently active.
     */
    public function isActive(): bool;

    /**
     * Check if the contract covers a specific service type.
     */
    public function covers(string $serviceType): bool;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;
}
