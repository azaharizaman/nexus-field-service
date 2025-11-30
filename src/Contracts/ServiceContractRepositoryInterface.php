<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Service Contract Repository Interface
 *
 * Defines persistence operations for service contracts.
 */
interface ServiceContractRepositoryInterface
{
    /**
     * Find a service contract by ID.
     */
    public function findById(string $id): ?ServiceContractInterface;

    /**
     * Find a service contract by contract number.
     */
    public function findByContractNumber(string $contractNumber): ?ServiceContractInterface;

    /**
     * Find active contracts for a customer.
     *
     * @return array<ServiceContractInterface>
     */
    public function findActiveByCustomer(string $customerPartyId): array;

    /**
     * Find contracts covering a specific asset.
     *
     * @return array<ServiceContractInterface>
     */
    public function findByAsset(string $assetId): array;

    /**
     * Find contracts expiring within a date range.
     *
     * @return array<ServiceContractInterface>
     */
    public function findExpiringBetween(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Save a service contract.
     */
    public function save(ServiceContractInterface $contract): void;

    /**
     * Delete a service contract.
     */
    public function delete(string $id): void;
}
