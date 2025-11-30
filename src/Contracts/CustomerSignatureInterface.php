<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\FieldService\ValueObjects\CustomerSignature;

/**
 * Customer Signature Interface
 *
 * Represents a customer's digital signature on a work order.
 */
interface CustomerSignatureInterface
{
    public function getId(): string;

    public function getWorkOrderId(): string;

    public function getSignature(): CustomerSignature;

    public function getCapturedByTechnicianId(): string;

    /**
     * Get GPS location where signature was captured.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function getGpsLocation(): ?array;

    public function getCapturedAt(): \DateTimeImmutable;
}
