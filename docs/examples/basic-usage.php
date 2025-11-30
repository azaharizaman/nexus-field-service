<?php

declare(strict_types=1);

/**
 * Basic Usage Examples: FieldService
 * 
 * Demonstrates common field service operations:
 * 1. Creating and assigning work orders
 * 2. Starting work orders with GPS validation
 * 3. Completing work orders with signatures
 * 4. Service contract validation
 * 5. SLA tracking
 */

use Nexus\FieldService\Contracts\{
    WorkOrderRepositoryInterface,
    TechnicianAssignmentStrategyInterface,
    GpsTrackerInterface,
    ServiceContractRepositoryInterface,
    SlaCalculatorInterface
};
use Nexus\FieldService\Enums\{WorkOrderStatus, WorkOrderPriority};
use Nexus\FieldService\ValueObjects\{GpsLocation, SkillSet};

// ============================================
// Example 1: Create and Auto-Assign Work Order
// ============================================

class WorkOrderService
{
    public function __construct(
        private readonly WorkOrderRepositoryInterface $workOrderRepository,
        private readonly TechnicianAssignmentStrategyInterface $assignmentStrategy
    ) {}
    
    public function createEmergencyWorkOrder(array $data): WorkOrderInterface
    {
        // Create work order
        $workOrder = new WorkOrder([
            'number' => 'WO-' . time(),
            'customer_id' => $data['customer_id'],
            'description' => $data['description'],
            'status' => WorkOrderStatus::Draft,
            'priority' => WorkOrderPriority::Emergency,
            'service_type' => 'repair',
            'site_location_lat' => $data['latitude'],
            'site_location_lng' => $data['longitude'],
            'required_skills' => ['HVAC', 'Electrical'],
            'scheduled_date' => now(),
        ]);
        
        $this->workOrderRepository->save($workOrder);
        
        // Auto-assign nearest qualified technician
        $technicianId = $this->assignmentStrategy->assignTechnician($workOrder);
        $workOrder->assignTechnician($technicianId);
        $this->workOrderRepository->save($workOrder);
        
        return $workOrder;
    }
}

// Usage:
// $workOrder = $service->createEmergencyWorkOrder([
//     'customer_id' => 'CUST-001',
//     'description' => 'Air conditioning unit failure',
//     'latitude' => 3.1390,
//     'longitude' => 101.6869
// ]);

// ============================================
// Example 2: Start Work Order with GPS Validation
// ============================================

class MobileWorkOrderService
{
    public function __construct(
        private readonly WorkOrderRepositoryInterface $workOrderRepository,
        private readonly GpsTrackerInterface $gpsTracker
    ) {}
    
    public function startWorkOrder(
        string $workOrderId,
        float $technicianLat,
        float $technicianLng
    ): void {
        $workOrder = $this->workOrderRepository->findById($workOrderId);
        
        // Validate technician is on-site (within 100 meters)
        $technicianLocation = new GpsLocation($technicianLat, $technicianLng);
        $customerLocation = new GpsLocation(
            (float) $workOrder->site_location_lat,
            (float) $workOrder->site_location_lng
        );
        
        $isOnSite = $this->gpsTracker->validateLocation(
            technicianLocation: $technicianLocation,
            customerLocation: $customerLocation,
            radiusMeters: 100
        );
        
        if (!$isOnSite) {
            throw new InvalidGpsLocationException(
                'Technician must be at customer site to start work'
            );
        }
        
        // Record start time and location
        $workOrder->setStatus(WorkOrderStatus::InProgress);
        $workOrder->started_at = now();
        $workOrder->start_gps_lat = $technicianLat;
        $workOrder->start_gps_lng = $technicianLng;
        
        $this->workOrderRepository->save($workOrder);
    }
}

// Usage:
// $mobileService->startWorkOrder(
//     workOrderId: 'WO-12345',
//     technicianLat: 3.1390,
//     technicianLng: 101.6869
// );

// ============================================
// Example 3: Complete Work Order with Signature
// ============================================

class WorkOrderCompletionService
{
    public function __construct(
        private readonly WorkOrderRepositoryInterface $workOrderRepository,
        private readonly SignatureRepositoryInterface $signatureRepository
    ) {}
    
    public function completeWorkOrder(
        string $workOrderId,
        string $signatureData,
        array $checklistData,
        float $lat,
        float $lng
    ): void {
        $workOrder = $this->workOrderRepository->findById($workOrderId);
        
        // Validate all checklist items completed
        if (!$this->isChecklistComplete($workOrder, $checklistData)) {
            throw new ChecklistNotCompletedException(
                'All checklist items must be completed'
            );
        }
        
        // Store customer signature
        $signature = new CustomerSignature(
            workOrderId: $workOrderId,
            signatureData: $signatureData,
            capturedAt: now(),
            capturedBy: auth()->id()
        );
        
        $signaturePath = $this->signatureRepository->store($signature);
        
        // Mark as completed
        $workOrder->setStatus(WorkOrderStatus::Completed);
        $workOrder->completed_at = now();
        $workOrder->completion_gps_lat = $lat;
        $workOrder->completion_gps_lng = $lng;
        $workOrder->signature_path = $signaturePath;
        
        // Calculate labor hours
        $laborHours = $workOrder->started_at->diffInHours($workOrder->completed_at, false);
        $workOrder->labor_hours = $laborHours;
        
        $this->workOrderRepository->save($workOrder);
    }
    
    private function isChecklistComplete(WorkOrderInterface $workOrder, array $data): bool
    {
        // Validate checklist completion logic
        return true;
    }
}

// Usage:
// $completionService->completeWorkOrder(
//     workOrderId: 'WO-12345',
//     signatureData: 'base64_encoded_signature_image',
//     checklistData: ['item1' => 'passed', 'item2' => 'passed'],
//     lat: 3.1390,
//     lng: 101.6869
// );

// ============================================
// Example 4: Service Contract Validation
// ============================================

class ServiceContractService
{
    public function __construct(
        private readonly ServiceContractRepositoryInterface $contractRepository
    ) {}
    
    public function validateContractForWorkOrder(WorkOrderInterface $workOrder): void
    {
        $contract = $this->contractRepository->findActiveByCustomer(
            $workOrder->customer_id
        );
        
        if (!$contract) {
            throw new ServiceContractNotFoundException(
                "No active contract for customer {$workOrder->customer_id}"
            );
        }
        
        // Check contract covers equipment
        if (!$contract->coversEquipment($workOrder->equipment_id)) {
            throw new EquipmentNotCoveredException(
                "Equipment {$workOrder->equipment_id} not covered by contract"
            );
        }
        
        // Check contract not expired
        if ($contract->isExpired()) {
            throw new ContractExpiredException(
                "Service contract has expired"
            );
        }
        
        // Apply SLA from contract
        $workOrder->response_sla_minutes = $contract->getResponseSla();
        $workOrder->resolution_sla_minutes = $contract->getResolutionSla();
    }
}

// Usage:
// $contractService->validateContractForWorkOrder($workOrder);

// ============================================
// Example 5: SLA Breach Detection
// ============================================

class SlaMonitoringService
{
    public function __construct(
        private readonly WorkOrderRepositoryInterface $workOrderRepository,
        private readonly SlaCalculatorInterface $slaCalculator
    ) {}
    
    public function checkSlaCompliance(string $workOrderId): array
    {
        $workOrder = $this->workOrderRepository->findById($workOrderId);
        
        $isResponseBreached = $this->slaCalculator->isSlaBreached(
            createdAt: $workOrder->created_at,
            slaMinutes: $workOrder->response_sla_minutes,
            businessHoursOnly: true
        );
        
        $isResolutionBreached = $workOrder->completed_at 
            ? false 
            : $this->slaCalculator->isSlaBreached(
                createdAt: $workOrder->created_at,
                slaMinutes: $workOrder->resolution_sla_minutes,
                businessHoursOnly: true
            );
        
        return [
            'response_sla_status' => $isResponseBreached ? 'breached' : 'within_sla',
            'resolution_sla_status' => $isResolutionBreached ? 'breached' : 'within_sla',
            'response_time_minutes' => $this->slaCalculator->calculateResponseTime(
                $workOrder->created_at,
                $workOrder->started_at ?? now(),
                true
            ),
        ];
    }
}

// Usage:
// $slaStatus = $slaMonitoring->checkSlaCompliance('WO-12345');
// if ($slaStatus['response_sla_status'] === 'breached') {
//     // Send escalation notification
// }

// Expected output:
// [
//     'response_sla_status' => 'within_sla',
//     'resolution_sla_status' => 'within_sla',
//     'response_time_minutes' => 45
// ]
