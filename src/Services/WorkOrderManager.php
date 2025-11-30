<?php

declare(strict_types=1);

namespace Nexus\FieldService\Services;

use Nexus\FieldService\Contracts\WorkOrderInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\Contracts\ServiceContractRepositoryInterface;
use Nexus\FieldService\Contracts\ChecklistRepositoryInterface;
use Nexus\FieldService\Contracts\GpsTrackerInterface;
use Nexus\FieldService\Contracts\SlaCalculatorInterface;
use Nexus\FieldService\Enums\WorkOrderStatus;
use Nexus\FieldService\Enums\WorkOrderPriority;
use Nexus\FieldService\Enums\ServiceType;
use Nexus\FieldService\Enums\ContractStatus;
use Nexus\FieldService\Exceptions\WorkOrderNotFoundException;
use Nexus\FieldService\Exceptions\InvalidWorkOrderStateException;
use Nexus\FieldService\Exceptions\ServiceContractExpiredException;
use Nexus\FieldService\Exceptions\SignatureRequiredException;
use Nexus\FieldService\Events\WorkOrderCreatedEvent;
use Nexus\FieldService\Events\WorkOrderAssignedEvent;
use Nexus\FieldService\Events\WorkOrderStartedEvent;
use Nexus\FieldService\Events\WorkOrderCompletedEvent;
use Nexus\FieldService\Events\WorkOrderVerifiedEvent;
use Nexus\FieldService\Events\GpsLocationCapturedEvent;
use Nexus\FieldService\ValueObjects\WorkOrderNumber;
use Nexus\FieldService\ValueObjects\GpsLocation;
use Nexus\FieldService\ValueObjects\LaborHours;
use Nexus\FieldService\ValueObjects\CustomerSignature;
use Nexus\FieldService\Core\Checklist\ChecklistValidator;
use Nexus\Sequencing\Contracts\SequenceManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Work Order Manager Service
 *
 * Main orchestrator for work order lifecycle management.
 * Enforces state machine transitions and business rules.
 */
final readonly class WorkOrderManager
{
    public function __construct(
        private WorkOrderRepositoryInterface $workOrderRepository,
        private ServiceContractRepositoryInterface $contractRepository,
        private ChecklistRepositoryInterface $checklistRepository,
        private GpsTrackerInterface $gpsTracker,
        private SlaCalculatorInterface $slaCalculator,
        private SequenceManagerInterface $sequenceManager,
        private ChecklistValidator $checklistValidator,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create a new work order.
     *
     * @param array{
     *     customer_party_id: string,
     *     service_location_id?: string|null,
     *     asset_id?: string|null,
     *     service_contract_id?: string|null,
     *     service_type: ServiceType|string,
     *     priority: WorkOrderPriority|string,
     *     description: string,
     *     scheduled_start?: \DateTimeImmutable|null,
     *     scheduled_end?: \DateTimeImmutable|null,
     *     metadata?: array
     * } $data
     */
    public function create(array $data): WorkOrderInterface
    {
        // Validate service contract if provided
        if (isset($data['service_contract_id'])) {
            $this->validateServiceContract(
                $data['service_contract_id'],
                $data['service_type'] instanceof ServiceType ? $data['service_type'] : ServiceType::from($data['service_type']),
                $data['priority'] instanceof WorkOrderPriority ? $data['priority'] : WorkOrderPriority::from($data['priority'])
            );
        }

        // Generate work order number
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $sequence = $this->sequenceManager->next('work_orders', ['year' => $year]);
        $workOrderNumber = WorkOrderNumber::generate($year, $sequence);

        // Calculate SLA deadline if service contract provided
        $slaDeadline = null;
        if (isset($data['service_contract_id'])) {
            $contract = $this->contractRepository->findById($data['service_contract_id']);
            if ($contract !== null) {
                $priority = $data['priority'] instanceof WorkOrderPriority 
                    ? $data['priority'] 
                    : WorkOrderPriority::from($data['priority']);
                
                $slaDeadline = $this->slaCalculator->calculateDeadline(
                    $contract->getResponseTime(),
                    $priority->value
                );
            }
        }

        // Create work order (implementation will be in Atomy)
        // For now, we just validate and prepare the data
        
        $this->logger->info('Work order created', [
            'number' => $workOrderNumber->toString(),
            'customer_party_id' => $data['customer_party_id'],
            'service_type' => $data['service_type'] instanceof ServiceType ? $data['service_type']->value : $data['service_type'],
        ]);

        // Dispatch event
        $event = new WorkOrderCreatedEvent(
            'wo-id', // Will be actual ID from repository
            $data['customer_party_id'],
            $data['service_type'] instanceof ServiceType ? $data['service_type']->value : $data['service_type'],
            $data['priority'] instanceof WorkOrderPriority ? $data['priority']->value : $data['priority'],
            new \DateTimeImmutable()
        );
        
        $this->eventDispatcher->dispatch($event);

        // Return will be implemented in Atomy
        throw new \LogicException('WorkOrder creation must be implemented in application layer');
    }

    /**
     * Assign work order to technician.
     */
    public function assign(
        string $workOrderId,
        string $technicianId,
        \DateTimeImmutable $scheduledStart,
        ?\DateTimeImmutable $scheduledEnd = null
    ): void {
        $workOrder = $this->findWorkOrder($workOrderId);

        if (!$workOrder->getStatus()->canAssign()) {
            throw new InvalidWorkOrderStateException(
                "Cannot assign work order in status: {$workOrder->getStatus()->value}"
            );
        }

        // Update will be implemented in application layer

        $this->logger->info('Work order assigned', [
            'work_order_id' => $workOrderId,
            'technician_id' => $technicianId,
            'scheduled_start' => $scheduledStart->format('c'),
        ]);

        $event = new WorkOrderAssignedEvent(
            $workOrderId,
            $technicianId,
            $scheduledStart,
            new \DateTimeImmutable()
        );
        
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Start work on a work order (technician begins job).
     */
    public function start(
        string $workOrderId,
        string $technicianId,
        ?GpsLocation $gpsLocation = null
    ): void {
        $workOrder = $this->findWorkOrder($workOrderId);

        if (!$workOrder->getStatus()->canStart()) {
            throw new InvalidWorkOrderStateException(
                "Cannot start work order in status: {$workOrder->getStatus()->value}"
            );
        }

        // Capture GPS location if provided (BUS-FIE-0107)
        if ($gpsLocation !== null) {
            $this->gpsTracker->captureLocation(
                $workOrderId,
                $technicianId,
                'job_start',
                $gpsLocation
            );

            $event = new GpsLocationCapturedEvent(
                $workOrderId,
                $technicianId,
                'job_start',
                $gpsLocation->getLatitude(),
                $gpsLocation->getLongitude(),
                $gpsLocation->getCapturedAt(),
                $gpsLocation->getAccuracyMeters()
            );
            
            $this->eventDispatcher->dispatch($event);
        }

        $actualStart = new \DateTimeImmutable();

        $this->logger->info('Work order started', [
            'work_order_id' => $workOrderId,
            'technician_id' => $technicianId,
            'actual_start' => $actualStart->format('c'),
        ]);

        $event = new WorkOrderStartedEvent(
            $workOrderId,
            $technicianId,
            $actualStart,
            $gpsLocation?->toArray()
        );
        
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Complete a work order (technician finishes job).
     */
    public function complete(
        string $workOrderId,
        string $technicianId,
        string $checklistResponseId,
        ?string $notes = null,
        ?GpsLocation $gpsLocation = null
    ): void {
        $workOrder = $this->findWorkOrder($workOrderId);

        if (!$workOrder->getStatus()->canComplete()) {
            throw new InvalidWorkOrderStateException(
                "Cannot complete work order in status: {$workOrder->getStatus()->value}"
            );
        }

        // Validate checklist (BUS-FIE-0059)
        $this->validateChecklist($workOrderId, $checklistResponseId);

        // Capture GPS location if provided
        if ($gpsLocation !== null) {
            $this->gpsTracker->captureLocation(
                $workOrderId,
                $technicianId,
                'job_end',
                $gpsLocation
            );

            $event = new GpsLocationCapturedEvent(
                $workOrderId,
                $technicianId,
                'job_end',
                $gpsLocation->getLatitude(),
                $gpsLocation->getLongitude(),
                $gpsLocation->getCapturedAt(),
                $gpsLocation->getAccuracyMeters()
            );
            
            $this->eventDispatcher->dispatch($event);
        }

        // Calculate labor hours
        $laborHours = $this->calculateLaborHours($workOrder);

        $actualEnd = new \DateTimeImmutable();

        $this->logger->info('Work order completed', [
            'work_order_id' => $workOrderId,
            'technician_id' => $technicianId,
            'labor_hours' => $laborHours->getHours(),
        ]);

        $event = new WorkOrderCompletedEvent(
            $workOrderId,
            $technicianId,
            $actualEnd,
            $laborHours->getHours(),
            $laborHours->getTotalCost(),
            $gpsLocation?->toArray()
        );
        
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Verify work order with customer signature.
     */
    public function verify(
        string $workOrderId,
        string $signatureId
    ): void {
        $workOrder = $this->findWorkOrder($workOrderId);

        if (!$workOrder->getStatus()->canVerify()) {
            throw new InvalidWorkOrderStateException(
                "Cannot verify work order in status: {$workOrder->getStatus()->value}"
            );
        }

        $this->logger->info('Work order verified', [
            'work_order_id' => $workOrderId,
            'signature_id' => $signatureId,
        ]);

        $event = new WorkOrderVerifiedEvent(
            $workOrderId,
            $signatureId,
            new \DateTimeImmutable()
        );
        
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Cancel a work order.
     */
    public function cancel(string $workOrderId, string $reason): void
    {
        $workOrder = $this->findWorkOrder($workOrderId);

        if (!$workOrder->getStatus()->canCancel()) {
            throw new InvalidWorkOrderStateException(
                "Cannot cancel work order in status: {$workOrder->getStatus()->value}"
            );
        }

        $this->logger->info('Work order cancelled', [
            'work_order_id' => $workOrderId,
            'reason' => $reason,
        ]);
    }

    /**
     * Find work order by ID.
     */
    private function findWorkOrder(string $id): WorkOrderInterface
    {
        $workOrder = $this->workOrderRepository->findById($id);

        if ($workOrder === null) {
            throw new WorkOrderNotFoundException("Work order not found: {$id}");
        }

        return $workOrder;
    }

    /**
     * Validate service contract.
     */
    private function validateServiceContract(
        string $contractId,
        ServiceType $serviceType,
        WorkOrderPriority $priority
    ): void {
        $contract = $this->contractRepository->findById($contractId);

        if ($contract === null) {
            throw new \InvalidArgumentException("Service contract not found: {$contractId}");
        }

        // Check if contract allows work orders (BUS-FIE-0117)
        if (!$contract->getStatus()->allowsWorkOrders()) {
            // Emergency work orders can bypass expired contracts
            if ($priority->bypassesContractValidation() && $contract->getStatus()->allowsEmergencyWorkOrders()) {
                $this->logger->warning('Emergency work order created against expired contract', [
                    'contract_id' => $contractId,
                    'priority' => $priority->value,
                ]);
                return;
            }

            throw new ServiceContractExpiredException(
                "Service contract is {$contract->getStatus()->value} and does not allow new work orders"
            );
        }

        // Check if contract covers this service type
        if (!$contract->covers($serviceType->value)) {
            throw new \InvalidArgumentException(
                "Service contract does not cover service type: {$serviceType->value}"
            );
        }
    }

    /**
     * Validate checklist responses.
     */
    private function validateChecklist(string $workOrderId, string $checklistResponseId): void
    {
        $response = $this->checklistRepository->findResponseByWorkOrder($workOrderId);

        if ($response === null) {
            throw new \InvalidArgumentException("Checklist response not found for work order: {$workOrderId}");
        }

        $template = $this->checklistRepository->findTemplateById($response['checklist_template_id']);

        if ($template === null) {
            throw new \InvalidArgumentException("Checklist template not found");
        }

        // Validate all critical items passed
        $this->checklistValidator->validate($response['responses'], $template['items']);
    }

    /**
     * Calculate labor hours from work order start/end times.
     */
    private function calculateLaborHours(WorkOrderInterface $workOrder): LaborHours
    {
        $start = $workOrder->getActualStart();
        $end = new \DateTimeImmutable();

        if ($start === null) {
            // If no start time recorded, use scheduled start
            $start = $workOrder->getScheduledStart() ?? $end->modify('-2 hours');
        }

        return LaborHours::fromTimeRange($start, $end);
    }
}
