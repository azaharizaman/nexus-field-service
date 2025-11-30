<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

/**
 * Mobile Sync Manager Interface
 *
 * Defines offline data synchronization and conflict resolution.
 * MVP: Last-write-wins (LWW) strategy
 * Future: Operational transformation for complex merges
 */
interface MobileSyncManagerInterface
{
    /**
     * Queue an update from mobile device.
     *
     * @param array<string, mixed> $data
     */
    public function queueUpdate(
        string $recordId,
        array $data,
        \DateTimeImmutable $timestamp
    ): void;

    /**
     * Resolve conflicts between mobile and server data.
     *
     * @param array<array{record_id: string, data: array, timestamp: string}> $updates
     * @return array{synced: int, conflicts: array}
     */
    public function resolveConflicts(array $updates): array;

    /**
     * Get server version of a record for conflict resolution.
     *
     * @return array{data: array, updated_at: string}|null
     */
    public function getServerVersion(string $recordId): ?array;
}
