<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Signature Repository Interface
 *
 * Defines persistence operations for customer signatures.
 */
interface SignatureRepositoryInterface
{
    /**
     * Find signature by ID.
     */
    public function findById(string $id): ?CustomerSignatureInterface;

    /**
     * Find signature by work order.
     */
    public function findByWorkOrder(string $workOrderId): ?CustomerSignatureInterface;

    /**
     * Save a customer signature.
     */
    public function save(CustomerSignatureInterface $signature): void;
}
