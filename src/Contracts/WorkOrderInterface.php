<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\FieldService\Enums\WorkOrderStatus;
use Nexus\FieldService\Enums\WorkOrderPriority;
use Nexus\FieldService\Enums\ServiceType;
use Nexus\FieldService\ValueObjects\WorkOrderNumber;
use Nexus\FieldService\ValueObjects\LaborHours;

/**
 * Work Order Interface
 *
 * Represents a field service work order entity.
 */
interface WorkOrderInterface
{
    public function getId(): string;

    public function getNumber(): WorkOrderNumber;

    public function getCustomerPartyId(): string;

    public function getServiceLocationId(): ?string;

    public function getAssetId(): ?string;

    public function getServiceContractId(): ?string;

    public function getAssignedTechnicianId(): ?string;

    public function getStatus(): WorkOrderStatus;

    public function getPriority(): WorkOrderPriority;

    public function getServiceType(): ServiceType;

    public function getDescription(): string;

    public function getScheduledStart(): ?\DateTimeImmutable;

    public function getScheduledEnd(): ?\DateTimeImmutable;

    public function getActualStart(): ?\DateTimeImmutable;

    public function getActualEnd(): ?\DateTimeImmutable;

    public function getSlaDeadline(): ?\DateTimeImmutable;

    public function getTechnicianNotes(): ?string;

    public function getLaborHours(): ?LaborHours;

    /**
     * Get metadata as associative array.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;
}
