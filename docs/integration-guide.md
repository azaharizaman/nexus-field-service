# Integration Guide: FieldService

This guide demonstrates how to integrate the FieldService package into Laravel and Symfony applications.

---

## Laravel Integration

### Complete Laravel Implementation Example

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\FieldService\Contracts\{
    WorkOrderRepositoryInterface,
    ServiceContractRepositoryInterface,
    TechnicianAssignmentStrategyInterface,
    GpsTrackerInterface,
    RouteOptimizerInterface,
    MobileSyncManagerInterface,
    SlaCalculatorInterface
};
use Nexus\FieldService\Core\Assignment\ProximityAssignmentStrategy;
use Nexus\FieldService\Core\Sync\LastWriteWinsSyncManager;
use Nexus\FieldService\Core\Sla\BusinessHoursSlaCalculator;
use App\Repositories\FieldService\{
    EloquentWorkOrderRepository,
    EloquentServiceContractRepository
};
use App\Services\FieldService\{
    GoogleMapsGpsTracker,
    GoogleRoutesOptimizer
};

class FieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->singleton(
            WorkOrderRepositoryInterface::class,
            EloquentWorkOrderRepository::class
        );
        
        $this->app->singleton(
            ServiceContractRepositoryInterface::class,
            EloquentServiceContractRepository::class
        );
        
        // GPS Tracking (Google Maps integration)
        $this->app->singleton(
            GpsTrackerInterface::class,
            GoogleMapsGpsTracker::class
        );
        
        // Route Optimization (Google Routes API)
        $this->app->singleton(
            RouteOptimizerInterface::class,
            GoogleRoutesOptimizer::class
        );
        
        // Technician Assignment Strategy
        $this->app->singleton(
            TechnicianAssignmentStrategyInterface::class,
            ProximityAssignmentStrategy::class
        );
        
        // SLA Calculator
        $this->app->singleton(
            SlaCalculatorInterface::class,
            BusinessHoursSlaCalculator::class
        );
        
        // Mobile Sync Manager
        $this->app->singleton(
            MobileSyncManagerInterface::class,
            LastWriteWinsSyncManager::class
        );
    }
}
```

### Laravel Controller Example

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\Contracts\TechnicianAssignmentStrategyInterface;
use Nexus\FieldService\Enums\{WorkOrderStatus, WorkOrderPriority};
use App\Models\WorkOrder;

class WorkOrderController extends Controller
{
    public function __construct(
        private readonly WorkOrderRepositoryInterface $workOrderRepository,
        private readonly TechnicianAssignmentStrategyInterface $assignmentStrategy
    ) {}
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|ulid',
            'description' => 'required|string',
            'priority' => 'required|string',
            'service_type' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'required_skills' => 'array',
        ]);
        
        $workOrder = new WorkOrder([
            'number' => 'WO-' . time(),
            'customer_id' => $validated['customer_id'],
            'description' => $validated['description'],
            'status' => WorkOrderStatus::Draft,
            'priority' => WorkOrderPriority::from($validated['priority']),
            'service_type' => $validated['service_type'],
            'site_location_lat' => $validated['latitude'],
            'site_location_lng' => $validated['longitude'],
            'required_skills' => $validated['required_skills'] ?? [],
            'scheduled_date' => now()->addDay(),
        ]);
        
        $this->workOrderRepository->save($workOrder);
        
        // Auto-assign technician
        $technicianId = $this->assignmentStrategy->assignTechnician($workOrder);
        $workOrder->assignTechnician($technicianId);
        $this->workOrderRepository->save($workOrder);
        
        return response()->json($workOrder, 201);
    }
    
    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'signature_data' => 'nullable|string',
        ]);
        
        $workOrder = $this->workOrderRepository->findById($id);
        $workOrder->setStatus(WorkOrderStatus::from($validated['status']));
        
        $this->workOrderRepository->save($workOrder);
        
        return response()->json($workOrder);
    }
}
```

---

## Symfony Integration

### Symfony Services Configuration

`config/services.yaml`:

```yaml
services:
    # Repositories
    Nexus\FieldService\Contracts\WorkOrderRepositoryInterface:
        class: App\Repository\FieldService\DoctrineWorkOrderRepository
        arguments:
            $entityManager: '@doctrine.orm.entity_manager'
            $tenantContext: '@Nexus\Tenant\Contracts\TenantContextInterface'
    
    Nexus\FieldService\Contracts\ServiceContractRepositoryInterface:
        class: App\Repository\FieldService\DoctrineServiceContractRepository
        arguments:
            $entityManager: '@doctrine.orm.entity_manager'
            $tenantContext: '@Nexus\Tenant\Contracts\TenantContextInterface'
    
    # GPS Tracking
    Nexus\FieldService\Contracts\GpsTrackerInterface:
        class: App\Service\FieldService\GoogleMapsGpsTracker
        arguments:
            $apiKey: '%env(GOOGLE_MAPS_API_KEY)%'
    
    # Route Optimization
    Nexus\FieldService\Contracts\RouteOptimizerInterface:
        class: App\Service\FieldService\GoogleRoutesOptimizer
        arguments:
            $apiKey: '%env(GOOGLE_ROUTES_API_KEY)%'
    
    # Assignment Strategy
    Nexus\FieldService\Contracts\TechnicianAssignmentStrategyInterface:
        class: Nexus\FieldService\Core\Assignment\ProximityAssignmentStrategy
        arguments:
            $gpsTracker: '@Nexus\FieldService\Contracts\GpsTrackerInterface'
            $technicianRepository: '@App\Repository\TechnicianRepository'
    
    # SLA Calculator
    Nexus\FieldService\Contracts\SlaCalculatorInterface:
        class: Nexus\FieldService\Core\Sla\BusinessHoursSlaCalculator
    
    # Mobile Sync
    Nexus\FieldService\Contracts\MobileSyncManagerInterface:
        class: Nexus\FieldService\Core\Sync\LastWriteWinsSyncManager
        arguments:
            $workOrderRepository: '@Nexus\FieldService\Contracts\WorkOrderRepositoryInterface'
```

### Symfony Controller Example

```php
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Annotation\Route;
use Nexus\FieldService\Contracts\{
    WorkOrderRepositoryInterface,
    TechnicianAssignmentStrategyInterface
};
use Nexus\FieldService\Enums\{WorkOrderStatus, WorkOrderPriority};
use App\Entity\WorkOrder;

#[Route('/api/work-orders', name: 'api_work_orders_')]
class WorkOrderController extends AbstractController
{
    public function __construct(
        private readonly WorkOrderRepositoryInterface $workOrderRepository,
        private readonly TechnicianAssignmentStrategyInterface $assignmentStrategy
    ) {}
    
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $workOrder = new WorkOrder();
        $workOrder->setNumber('WO-' . time());
        $workOrder->setCustomerId($data['customer_id']);
        $workOrder->setDescription($data['description']);
        $workOrder->setStatus(WorkOrderStatus::Draft);
        $workOrder->setPriority(WorkOrderPriority::from($data['priority']));
        $workOrder->setServiceType($data['service_type']);
        $workOrder->setSiteLocation($data['latitude'], $data['longitude']);
        
        $this->workOrderRepository->save($workOrder);
        
        // Auto-assign
        $technicianId = $this->assignmentStrategy->assignTechnician($workOrder);
        $workOrder->assignTechnician($technicianId);
        $this->workOrderRepository->save($workOrder);
        
        return $this->json($workOrder, 201);
    }
}
```

---

## Common Integration Patterns

### Pattern 1: Offline Mobile Sync API

```php
#[Route('/api/mobile/sync', name: 'mobile_sync', methods: ['POST'])]
public function sync(Request $request): JsonResponse
{
    $offlineData = json_decode($request->getContent(), true);
    
    try {
        $this->syncManager->sync($offlineData['work_order']);
        
        return $this->json(['status' => 'synced']);
    } catch (OfflineSyncConflictException $e) {
        return $this->json([
            'status' => 'conflict',
            'conflicts' => $e->getConflicts(),
            'server_version' => $e->getServerVersion(),
            'client_version' => $e->getClientVersion(),
        ], 409);
    }
}
```

### Pattern 2: Real-Time GPS Tracking Endpoint

```php
#[Route('/api/technicians/{id}/location', name: 'update_location', methods: ['POST'])]
public function updateLocation(string $id, Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    $location = new GpsLocation(
        latitude: $data['latitude'],
        longitude: $data['longitude']
    );
    
    $this->gpsTracker->trackLocation($id, $location);
    
    return $this->json(['status' => 'updated']);
}
```

### Pattern 3: SLA Monitoring Dashboard

```php
public function slaBreaches(): JsonResponse
{
    $atRiskWorkOrders = WorkOrder::where('status', '!=', WorkOrderStatus::Completed)
        ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
        ->get()
        ->filter(function ($workOrder) {
            return $this->slaCalculator->isSlaBreached(
                $workOrder->created_at,
                $workOrder->response_sla_minutes,
                $workOrder->business_hours_only
            );
        });
    
    return $this->json($atRiskWorkOrders);
}
```

---

## Testing Integration

### Laravel Feature Test Example

```php
<?php

namespace Tests\Feature\FieldService;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{WorkOrder, Technician};
use Nexus\FieldService\Enums\{WorkOrderStatus, WorkOrderPriority};

class WorkOrderLifecycleTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_complete_work_order_workflow(): void
    {
        // Arrange
        $technician = Technician::factory()->create();
        
        // Act: Create work order
        $response = $this->postJson('/api/work-orders', [
            'customer_id' => 'CUST-001',
            'description' => 'HVAC repair',
            'priority' => 'high',
            'service_type' => 'repair',
            'latitude' => 3.1390,
            'longitude' => 101.6869,
            'required_skills' => ['HVAC'],
        ]);
        
        $response->assertStatus(201);
        $workOrderId = $response->json('id');
        
        // Assert: Work order auto-assigned
        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrderId,
            'status' => WorkOrderStatus::Assigned->value,
            'technician_id' => $technician->id,
        ]);
        
        // Act: Start work order
        $this->patchJson("/api/work-orders/{$workOrderId}/status", [
            'status' => 'in_progress',
            'latitude' => 3.1390,
            'longitude' => 101.6869,
        ])->assertOk();
        
        // Act: Complete work order
        $this->patchJson("/api/work-orders/{$workOrderId}/status", [
            'status' => 'completed',
            'latitude' => 3.1390,
            'longitude' => 101.6869,
            'signature_data' => 'base64_encoded_signature',
        ])->assertOk();
        
        // Assert: Work order completed
        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrderId,
            'status' => WorkOrderStatus::Completed->value,
        ]);
    }
}
```

---

**Package Version:** 1.0.0  
**Last Updated:** 2025-01-25
