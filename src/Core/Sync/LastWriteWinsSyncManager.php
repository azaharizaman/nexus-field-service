<?php

declare(strict_types=1);

namespace Nexus\FieldService\Core\Sync;

use Nexus\FieldService\Contracts\MobileSyncManagerInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Last-Write-Wins Sync Manager (MVP Implementation)
 *
 * Resolves conflicts by comparing timestamps.
 * The most recent update wins.
 */
final readonly class LastWriteWinsSyncManager implements MobileSyncManagerInterface
{
    public function __construct(
        private WorkOrderRepositoryInterface $workOrderRepository,
        private LoggerInterface $logger
    ) {
    }

    public function queueUpdate(
        string $recordId,
        array $data,
        \DateTimeImmutable $timestamp
    ): void {
        // In MVP, we process updates immediately rather than queuing
        // A production implementation would use a queue system
        
        $this->logger->info('Mobile update queued', [
            'record_id' => $recordId,
            'timestamp' => $timestamp->format('c'),
        ]);
    }

    public function resolveConflicts(array $updates): array
    {
        $synced = 0;
        $conflicts = [];

        foreach ($updates as $update) {
            $recordId = $update['record_id'];
            $mobileTimestamp = new \DateTimeImmutable($update['timestamp']);
            
            $serverVersion = $this->getServerVersion($recordId);
            
            if ($serverVersion === null) {
                // Record doesn't exist on server - accept mobile version
                $synced++;
                continue;
            }

            $serverTimestamp = new \DateTimeImmutable($serverVersion['updated_at']);
            
            if ($mobileTimestamp > $serverTimestamp) {
                // Mobile version is newer - accept it
                $synced++;
                $this->logger->info('Mobile update accepted (newer)', [
                    'record_id' => $recordId,
                    'mobile_ts' => $mobileTimestamp->format('c'),
                    'server_ts' => $serverTimestamp->format('c'),
                ]);
            } else {
                // Server version is newer - conflict
                $conflicts[] = [
                    'action' => $update,
                    'server_version' => $serverVersion,
                    'conflict_reason' => 'Server has newer version',
                ];
                
                $this->logger->warning('Sync conflict detected', [
                    'record_id' => $recordId,
                    'mobile_ts' => $mobileTimestamp->format('c'),
                    'server_ts' => $serverTimestamp->format('c'),
                ]);
            }
        }

        return [
            'synced' => $synced,
            'conflicts' => $conflicts,
        ];
    }

    public function getServerVersion(string $recordId): ?array
    {
        $workOrder = $this->workOrderRepository->findById($recordId);
        
        if ($workOrder === null) {
            return null;
        }

        return [
            'data' => [
                'id' => $workOrder->getId(),
                'status' => $workOrder->getStatus()->value,
                'technician_notes' => $workOrder->getTechnicianNotes(),
                // Add other relevant fields
            ],
            'updated_at' => $workOrder->getUpdatedAt()->format('c'),
        ];
    }
}
