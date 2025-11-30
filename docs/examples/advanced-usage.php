<?php

declare(strict_types=1);

/**
 * Advanced Usage Examples: FieldService
 * 
 * Demonstrates complex scenarios:
 * 1. Custom technician assignment strategy
 * 2. Offline mobile sync with conflict resolution
 * 3. Preventive maintenance scheduling with deduplication
 */

use Nexus\FieldService\Contracts\{
    TechnicianAssignmentStrategyInterface,
    WorkOrderInterface,
    MobileSyncManagerInterface,
    MaintenanceDeduplicationInterface
};
use Nexus\FieldService\ValueObjects\{SkillSet, GpsLocation};
use Nexus\FieldService\Exceptions\{SyncConflictException, MaintenanceAlreadyScheduledException};

// ============================================
// Example 1: Custom Technician Assignment Strategy
// ============================================

/**
 * Assigns technicians based on:
 * 1. Skills match (must have all required skills)
 * 2. Proximity to work order location
 * 3. Current workload (prefer less busy technicians)
 * 4. Historical performance (prefer higher-rated technicians)
 */
class IntelligentAssignmentStrategy implements TechnicianAssignmentStrategyInterface
{
    public function __construct(
        private readonly TechnicianRepositoryInterface $technicianRepository,
        private readonly GpsTrackerInterface $gpsTracker,
        private readonly PerformanceTrackerInterface $performanceTracker
    ) {}
    
    public function assignTechnician(WorkOrderInterface $workOrder): string
    {
        $requiredSkills = new SkillSet($workOrder->required_skills);
        $workOrderLocation = new GpsLocation(
            (float) $workOrder->site_location_lat,
            (float) $workOrder->site_location_lng
        );
        
        // Step 1: Filter by skills
        $qualifiedTechnicians = $this->technicianRepository->findBySkills($requiredSkills);
        
        if (empty($qualifiedTechnicians)) {
            throw new InsufficientSkillsException(
                "No technicians available with required skills: " . implode(', ', $requiredSkills->skills)
            );
        }
        
        // Step 2: Score each technician
        $scoredTechnicians = [];
        foreach ($qualifiedTechnicians as $technician) {
            $technicianLocation = $this->gpsTracker->getCurrentLocation($technician->getId());
            
            // Calculate distance (km)
            $distance = $this->calculateDistance($technicianLocation, $workOrderLocation);
            
            // Get current workload (number of active work orders)
            $workload = $this->technicianRepository->getActiveWorkOrderCount($technician->getId());
            
            // Get performance rating (0-100)
            $performance = $this->performanceTracker->getRating($technician->getId());
            
            // Calculate composite score (lower is better)
            $score = ($distance * 2) + ($workload * 5) - ($performance * 0.1);
            
            $scoredTechnicians[] = [
                'technician_id' => $technician->getId(),
                'score' => $score,
                'distance_km' => $distance,
                'workload' => $workload,
                'performance' => $performance,
            ];
        }
        
        // Step 3: Sort by score and return best match
        usort($scoredTechnicians, fn($a, $b) => $a['score'] <=> $b['score']);
        
        return $scoredTechnicians[0]['technician_id'];
    }
    
    private function calculateDistance(GpsLocation $from, GpsLocation $to): float
    {
        // Haversine formula for distance calculation
        $earthRadius = 6371; // km
        
        $latDiff = deg2rad($to->latitude - $from->latitude);
        $lngDiff = deg2rad($to->longitude - $from->longitude);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($from->latitude)) * cos(deg2rad($to->latitude)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}

// Usage:
// $strategy = new IntelligentAssignmentStrategy($techRepo, $gpsTracker, $perfTracker);
// $technicianId = $strategy->assignTechnician($workOrder);

// ============================================
// Example 2: Offline Mobile Sync with Conflict Resolution
// ============================================

class MobileSyncService
{
    public function __construct(
        private readonly MobileSyncManagerInterface $syncManager,
        private readonly WorkOrderRepositoryInterface $workOrderRepository
    ) {}
    
    public function syncOfflineWorkOrder(array $offlineData): array
    {
        try {
            // Attempt to sync offline work order
            $this->syncManager->sync($offlineData['work_order']);
            
            return [
                'status' => 'success',
                'message' => 'Work order synced successfully',
            ];
            
        } catch (SyncConflictException $e) {
            // Conflict detected - both offline and online versions modified
            
            $conflicts = $e->getConflicts();
            $serverVersion = $e->getServerVersion();
            $clientVersion = $e->getClientVersion();
            
            // Option 1: Last Write Wins (automatically resolve)
            if ($this->shouldUseLastWriteWins($conflicts)) {
                $resolved = $this->syncManager->resolveConflict(
                    serverVersion: $serverVersion,
                    clientVersion: $clientVersion,
                    strategy: 'last_write_wins'
                );
                
                $this->workOrderRepository->save($resolved);
                
                return [
                    'status' => 'auto_resolved',
                    'strategy' => 'last_write_wins',
                    'winner' => $clientVersion->updated_at > $serverVersion->updated_at 
                        ? 'client' 
                        : 'server',
                ];
            }
            
            // Option 2: Field-Level Merge (intelligent merge)
            $resolved = $this->mergeFields($serverVersion, $clientVersion, $conflicts);
            $this->workOrderRepository->save($resolved);
            
            return [
                'status' => 'merged',
                'conflicts_resolved' => count($conflicts),
            ];
            
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
    
    private function shouldUseLastWriteWins(array $conflicts): bool
    {
        // Use Last Write Wins for minor field changes
        $minorFields = ['notes', 'internal_comments'];
        
        foreach ($conflicts as $field => $values) {
            if (!in_array($field, $minorFields)) {
                return false; // Requires manual merge
            }
        }
        
        return true;
    }
    
    private function mergeFields(
        WorkOrderInterface $serverVersion,
        WorkOrderInterface $clientVersion,
        array $conflicts
    ): WorkOrderInterface {
        $merged = clone $serverVersion;
        
        foreach ($conflicts as $field => $values) {
            // Priority rules for merging
            $merged->$field = match ($field) {
                'status' => $this->mergeStatus($values['server'], $values['client']),
                'signature_path' => $values['client'], // Prefer client signature
                'completed_at' => $values['client'] ?? $values['server'],
                'parts_consumed' => $this->mergeParts($values['server'], $values['client']),
                default => $values['client'], // Default to client
            };
        }
        
        return $merged;
    }
    
    private function mergeStatus(string $serverStatus, string $clientStatus): string
    {
        // Status priority: Completed > InProgress > Assigned > Draft
        $priority = [
            'Completed' => 4,
            'InProgress' => 3,
            'Assigned' => 2,
            'Draft' => 1,
        ];
        
        return $priority[$clientStatus] >= $priority[$serverStatus] 
            ? $clientStatus 
            : $serverStatus;
    }
    
    private function mergeParts(array $serverParts, array $clientParts): array
    {
        // Combine parts consumption from both versions
        $merged = $serverParts;
        
        foreach ($clientParts as $part) {
            $existing = array_filter($merged, fn($p) => $p['part_id'] === $part['part_id']);
            
            if (empty($existing)) {
                $merged[] = $part; // Add new part
            }
        }
        
        return $merged;
    }
}

// Usage:
// $result = $syncService->syncOfflineWorkOrder([
//     'work_order' => [
//         'id' => 'WO-12345',
//         'status' => 'Completed',
//         'signature_path' => '/signatures/offline_123.png',
//         'completed_at' => '2025-01-25 14:30:00',
//     ]
// ]);

// ============================================
// Example 3: Preventive Maintenance Deduplication
// ============================================

class PreventiveMaintenanceScheduler
{
    public function __construct(
        private readonly WorkOrderRepositoryInterface $workOrderRepository,
        private readonly ServiceContractRepositoryInterface $contractRepository,
        private readonly MaintenanceDeduplicationInterface $deduplication
    ) {}
    
    public function schedulePreventiveMaintenance(
        string $contractId,
        \DateTimeImmutable $scheduledDate
    ): array {
        $contract = $this->contractRepository->findById($contractId);
        $scheduled = [];
        
        foreach ($contract->covered_equipment as $equipmentId) {
            try {
                // Check for recent PM (within 30 days)
                $isDuplicate = $this->deduplication->isDuplicate(
                    equipmentId: $equipmentId,
                    maintenanceType: 'preventive',
                    windowDays: 30
                );
                
                if ($isDuplicate) {
                    throw new MaintenanceAlreadyScheduledException(
                        "PM already scheduled for equipment {$equipmentId} within 30 days"
                    );
                }
                
                // Create PM work order
                $workOrder = new WorkOrder([
                    'number' => 'PM-' . time(),
                    'customer_id' => $contract->customer_id,
                    'equipment_id' => $equipmentId,
                    'service_contract_id' => $contractId,
                    'status' => WorkOrderStatus::Draft,
                    'priority' => WorkOrderPriority::Normal,
                    'service_type' => 'preventive_maintenance',
                    'description' => 'Scheduled preventive maintenance per service contract',
                    'scheduled_date' => $scheduledDate,
                ]);
                
                $this->workOrderRepository->save($workOrder);
                $scheduled[] = $workOrder->getId();
                
            } catch (MaintenanceAlreadyScheduledException $e) {
                // Skip duplicate, continue with next equipment
                continue;
            }
        }
        
        return [
            'total_equipment' => count($contract->covered_equipment),
            'pm_scheduled' => count($scheduled),
            'work_order_ids' => $scheduled,
        ];
    }
}

// Usage:
// $result = $pmScheduler->schedulePreventiveMaintenance(
//     contractId: 'CONTRACT-001',
//     scheduledDate: new \DateTimeImmutable('+7 days')
// );
// 
// Result:
// [
//     'total_equipment' => 10,
//     'pm_scheduled' => 8,
//     'work_order_ids' => ['PM-001', 'PM-002', ..., 'PM-008']
// ]
// (2 equipment skipped due to recent PM)
