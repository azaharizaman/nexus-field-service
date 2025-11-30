<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Checklist Repository Interface
 *
 * Defines persistence operations for checklists.
 */
interface ChecklistRepositoryInterface
{
    /**
     * Find checklist template by ID.
     */
    public function findTemplateById(string $id): ?array;

    /**
     * Find checklist templates by service type.
     *
     * @return array<array>
     */
    public function findTemplatesByServiceType(string $serviceType): array;

    /**
     * Find checklist response for a work order.
     */
    public function findResponseByWorkOrder(string $workOrderId): ?array;

    /**
     * Save checklist response.
     */
    public function saveResponse(string $workOrderId, string $templateId, array $responses): void;
}
