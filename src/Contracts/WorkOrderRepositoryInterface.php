<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Work Order Repository Interface
 *
 * Defines persistence operations for work orders.
 */
interface WorkOrderRepositoryInterface
{
    /**
     * Find a work order by ID.
     */
    public function findById(string $id): ?WorkOrderInterface;

    /**
     * Find a work order by number.
     */
    public function findByNumber(string $number): ?WorkOrderInterface;

    /**
     * Get all work orders for a specific technician on a given date.
     *
     * @return array<WorkOrderInterface>
     */
    public function findByTechnicianAndDate(
        string $technicianId,
        \DateTimeImmutable $date
    ): array;

    /**
     * Get total scheduled hours for a technician on a given date.
     */
    public function getTechnicianScheduledHours(
        string $technicianId,
        \DateTimeImmutable $date
    ): float;

    /**
     * Find work orders by service contract.
     *
     * @return array<WorkOrderInterface>
     */
    public function findByServiceContract(string $serviceContractId): array;

    /**
     * Find work orders by asset and date range.
     *
     * @return array<WorkOrderInterface>
     */
    public function findByAssetAndDateRange(
        string $assetId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array;

    /**
     * Find work orders approaching SLA deadline.
     *
     * @return array<WorkOrderInterface>
     */
    public function findApproachingSlaDeadline(
        \DateTimeImmutable $threshold
    ): array;

    /**
     * Save a work order.
     */
    public function save(WorkOrderInterface $workOrder): void;

    /**
     * Delete a work order.
     */
    public function delete(string $id): void;
}
