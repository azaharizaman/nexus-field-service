<?php

declare(strict_types=1);

namespace Nexus\FieldService\Core\Checklist;

use Nexus\FieldService\Enums\ChecklistItemType;
use Nexus\FieldService\Exceptions\ChecklistValidationException;

/**
 * Checklist Validator
 *
 * Validates checklist responses and enforces critical item requirements.
 * Per BUS-FIE-0059: All critical items must pass for work order completion.
 */
final readonly class ChecklistValidator
{
    /**
     * Validate checklist responses against template.
     *
     * @param array<array{id: string, label: string, type: string, passed: bool, notes?: string}> $responses
     * @param array<array{id: string, label: string, type: string}> $templateItems
     * @throws ChecklistValidationException
     */
    public function validate(array $responses, array $templateItems): void
    {
        $this->validateAllItemsAnswered($responses, $templateItems);
        $this->validateCriticalItems($responses, $templateItems);
    }

    /**
     * Check if all critical items passed.
     */
    public function allCriticalItemsPassed(array $responses, array $templateItems): bool
    {
        $criticalItems = array_filter(
            $templateItems,
            fn(array $item) => $item['type'] === ChecklistItemType::CRITICAL->value
        );

        foreach ($criticalItems as $critical) {
            $response = $this->findResponse($critical['id'], $responses);
            
            if ($response === null || !($response['passed'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of failed critical items.
     *
     * @return array<array{id: string, label: string}>
     */
    public function getFailedCriticalItems(array $responses, array $templateItems): array
    {
        $failed = [];
        
        $criticalItems = array_filter(
            $templateItems,
            fn(array $item) => $item['type'] === ChecklistItemType::CRITICAL->value
        );

        foreach ($criticalItems as $critical) {
            $response = $this->findResponse($critical['id'], $responses);
            
            if ($response === null || !($response['passed'] ?? false)) {
                $failed[] = [
                    'id' => $critical['id'],
                    'label' => $critical['label'],
                ];
            }
        }

        return $failed;
    }

    /**
     * Ensure all items have responses.
     */
    private function validateAllItemsAnswered(array $responses, array $templateItems): void
    {
        foreach ($templateItems as $item) {
            $response = $this->findResponse($item['id'], $responses);
            
            if ($response === null) {
                throw new ChecklistValidationException(
                    "Missing response for checklist item: {$item['label']}"
                );
            }
        }
    }

    /**
     * Ensure all critical items passed.
     */
    private function validateCriticalItems(array $responses, array $templateItems): void
    {
        $failedItems = $this->getFailedCriticalItems($responses, $templateItems);
        
        if (!empty($failedItems)) {
            $labels = array_column($failedItems, 'label');
            $itemsList = implode(', ', $labels);
            
            throw new ChecklistValidationException(
                "Critical checklist items failed: {$itemsList}"
            );
        }
    }

    /**
     * Find response for a specific item ID.
     */
    private function findResponse(string $itemId, array $responses): ?array
    {
        foreach ($responses as $response) {
            if ($response['id'] === $itemId) {
                return $response;
            }
        }
        
        return null;
    }
}
