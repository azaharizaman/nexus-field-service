# Getting Started with Nexus FieldService

## Prerequisites

- **PHP 8.3 or higher**
- **Composer**
- **Database** (MySQL 8.0+, PostgreSQL 13+, or compatible)
- **Cache system** (Redis recommended for mobile sync)
- **Nexus\Tenant** - Multi-tenancy support (required)
- **Nexus\Geo** - GPS tracking and geofencing (required)
- **Nexus\Routing** - Route optimization (required)
- **Nexus\Inventory** - Parts consumption tracking (required)
- **Nexus\AuditLogger** - Audit trail (optional but recommended)
- **Nexus\Monitoring** - Performance metrics (optional but recommended)

## When to Use This Package

This package is designed for:
- ✅ **Field service organizations** - HVAC, telecom, utilities, equipment maintenance
- ✅ **Mobile workforce management** - Technician dispatch, scheduling, tracking
- ✅ **Service contract management** - SLA enforcement, preventive maintenance
- ✅ **Offline-capable mobile apps** - Work order completion without internet connectivity
- ✅ **Multi-tenant field service platforms** - SaaS providers serving multiple organizations
- ✅ **Integrated ERP systems** - Field service module within larger business system

Do NOT use this package for:
- ❌ **Simple task management** - Use lightweight todo/project management instead
- ❌ **Warehouse operations** - Use Nexus\Warehouse for internal logistics
- ❌ **Delivery tracking only** - Use Nexus\Routing for basic route optimization
- ❌ **Customer support ticketing** - Use helpdesk/ticketing system instead

---

## Core Concepts

### Concept 1: Work Order Lifecycle

Work orders follow a strict state machine to ensure data integrity and proper tracking:

**State Flow:**
```
Draft → Assigned → InProgress → Completed → Verified
         ↓           ↓
    Cancelled    Paused
```

**States:**
- **Draft** - Work order created, awaiting assignment
- **Assigned** - Technician assigned, not yet started
- **InProgress** - Technician actively working on-site
- **Paused** - Temporarily suspended (awaiting parts, customer approval, etc.)
- **Completed** - Work finished, signature captured, awaiting verification
- **Verified** - Quality check passed, ready for invoicing
- **Cancelled** - Work order cancelled before completion

**Rules:**
- Cannot start unassigned work order
- Cannot complete without customer signature
- Cannot complete without checklist completion
- Cannot verify incomplete work order
- GPS location required for start/complete transitions

---

### Concept 2: Technician Assignment Strategies

Multiple strategies available for assigning technicians to work orders:

#### **Skills-Based Assignment**
Matches technician skills to work order requirements.

```php
// Example: Work order requires "HVAC" and "Electrical" skills
$workOrder->requiredSkills = new SkillSet(['HVAC', 'Electrical']);

// Technician A: ['HVAC', 'Electrical', 'Plumbing'] ✅ Qualified
// Technician B: ['HVAC'] ❌ Missing 'Electrical'
```

#### **Proximity-Based Assignment**
Assigns nearest available technician using GPS coordinates.

```php
// Automatically assigns technician closest to work order location
$strategy = new ProximityAssignmentStrategy($gpsTracker);
$technician = $strategy->assignTechnician($workOrder);
```

#### **Workload-Balanced Assignment**
Distributes work orders evenly across technicians.

```php
// Assigns to technician with fewest active work orders
$strategy = new WorkloadBalancedStrategy($workOrderRepository);
$technician = $strategy->assignTechnician($workOrder);
```

#### **Custom Assignment**
Implement `TechnicianAssignmentStrategyInterface` for custom logic.

```php
interface TechnicianAssignmentStrategyInterface
{
    public function assignTechnician(WorkOrderInterface $workOrder): string;
}
```

---

### Concept 3: Service Contracts & SLA Enforcement

Service contracts define response time and resolution time SLAs:

```php
$contract = new ServiceContract(
    customerId: 'CUST-001',
    responseSla: 120, // 2 hours response time
    resolutionSla: 480, // 8 hours resolution time
    businessHoursOnly: true
);
```

**SLA Calculation Features:**
- **Business Hours Aware** - Excludes nights, weekends, holidays
- **Priority-Based** - Different SLAs for emergency vs. routine work
- **Automatic Breach Detection** - Triggers alerts when SLA at risk
- **Pause/Resume Support** - Stops SLA clock during customer delays

**Example SLA Calculation:**
```
Work Order Created:    Monday 4:00 PM
Response SLA:          2 hours
Business Hours:        8 AM - 5 PM

Expected Response:     Tuesday 9:00 AM (excludes overnight)
Actual Response:       Tuesday 8:45 AM
Status:                ✅ Within SLA (15 min buffer)
```

---

### Concept 4: Preventive Maintenance Scheduling

Automatically schedules recurring maintenance based on service contracts:

**Deduplication Logic:**
Prevents creating duplicate PM work orders for the same equipment.

```php
// Contract specifies quarterly PM for equipment
$contract->maintenanceSchedule = [
    'frequency' => 'quarterly',
    'equipmentIds' => ['HVAC-001', 'HVAC-002']
];

// Deduplication checks:
// - Same equipment + same maintenance type + within 30 days = duplicate
```

**Maintenance Types:**
- **Preventive** - Scheduled routine maintenance
- **Corrective** - Repair after failure
- **Predictive** - Based on equipment condition monitoring (future)
- **Emergency** - Urgent breakdown response

---

### Concept 5: Offline Mobile Sync

Field technicians can work without internet connectivity:

**Offline Capabilities:**
- ✅ Complete work orders offline
- ✅ Capture customer signatures offline
- ✅ Record GPS locations offline
- ✅ Consume parts offline
- ✅ Complete checklists offline

**Sync Conflict Resolution:**
```php
// Conflict detection
if ($serverVersion->updatedAt > $clientVersion->updatedAt) {
    // Server version is newer - conflict detected
    throw new SyncConflictException();
}

// Resolution strategies:
// 1. Last Write Wins (default)
// 2. Manual Merge (user chooses)
// 3. Field-Level Merge (intelligent merge)
```

**Conflict Scenarios:**
- **Scenario 1:** Technician completes work order offline, dispatcher reassigns online
- **Scenario 2:** Technician updates checklist offline, customer changes requirements online
- **Scenario 3:** Technician consumes parts offline, inventory adjusted online

---

### Concept 6: GPS Tracking & Geofencing

Track technician locations and validate on-site presence:

**GPS Features:**
- **Real-time tracking** - Monitor technician locations
- **Geofencing** - Validate technician is at customer site
- **Route history** - Audit trail of movement
- **Travel time calculation** - Actual vs. estimated travel time

**Geofencing Validation:**
```php
// Validate technician is within 100m of customer site
$isOnSite = $gpsTracker->validateLocation(
    technicianLocation: $currentGps,
    customerLocation: $workOrder->siteLocation,
    radiusMeters: 100
);

if (!$isOnSite) {
    throw new InvalidGpsLocationException('Technician not at customer site');
}
```

---

### Concept 7: Parts Consumption Tracking

Track parts used during work order execution:

```php
// Record parts consumption
$partsConsumption = new PartsConsumption(
    workOrderId: 'WO-12345',
    partId: 'PART-7890',
    quantity: 2,
    technicianId: 'TECH-001'
);

// Automatically:
// 1. Deducts from technician's van inventory
// 2. Triggers reorder if below reorder point
// 3. Allocates cost to work order
// 4. Updates customer invoice
```

---

## Basic Configuration

### Step 1: Install Package

```bash
composer require nexus/field-service:"*@dev"
```

### Step 2: Create Database Migrations

#### Work Orders Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('number', 20)->unique();
            $table->string('customer_id', 26)->index();
            $table->string('equipment_id', 26)->nullable();
            $table->string('service_contract_id', 26)->nullable()->index();
            $table->string('technician_id', 26)->nullable()->index();
            $table->string('status', 20)->index();
            $table->string('priority', 10)->index();
            $table->string('service_type', 30);
            $table->text('description');
            $table->json('required_skills')->nullable();
            $table->string('site_location_lat', 20)->nullable();
            $table->string('site_location_lng', 20)->nullable();
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->integer('response_sla_minutes')->nullable();
            $table->integer('resolution_sla_minutes')->nullable();
            $table->decimal('labor_hours', 8, 2)->nullable();
            $table->decimal('parts_cost', 10, 2)->default(0);
            $table->string('signature_path')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'technician_id']);
            $table->index(['tenant_id', 'scheduled_date']);
        });
    }
};
```

#### Service Contracts Table

```php
Schema::create('service_contracts', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('tenant_id', 26)->index();
    $table->string('contract_number', 20)->unique();
    $table->string('customer_id', 26)->index();
    $table->string('status', 20);
    $table->date('start_date');
    $table->date('end_date');
    $table->integer('response_sla_minutes');
    $table->integer('resolution_sla_minutes');
    $table->boolean('business_hours_only')->default(true);
    $table->json('covered_equipment')->nullable();
    $table->json('maintenance_schedule')->nullable();
    $table->timestamps();
    
    $table->index(['tenant_id', 'status']);
    $table->index(['tenant_id', 'customer_id']);
});
```

#### Checklists Table

```php
Schema::create('work_order_checklists', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('work_order_id', 26)->index();
    $table->string('item_type', 20);
    $table->string('label', 255);
    $table->boolean('required')->default(true);
    $table->boolean('completed')->default(false);
    $table->text('value')->nullable();
    $table->string('photo_path')->nullable();
    $table->timestamps();
});
```

#### Parts Consumption Table

```php
Schema::create('parts_consumption', function (Blueprint $table) {
    $table->string('id', 26)->primary();
    $table->string('work_order_id', 26)->index();
    $table->string('part_id', 26)->index();
    $table->integer('quantity');
    $table->decimal('unit_cost', 10, 2);
    $table->string('technician_id', 26)->index();
    $table->timestamps();
    
    $table->index(['work_order_id', 'part_id']);
});
```

### Step 3: Create Eloquent Models

#### WorkOrder Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nexus\FieldService\Contracts\WorkOrderInterface;
use Nexus\FieldService\Enums\WorkOrderStatus;
use Nexus\FieldService\Enums\WorkOrderPriority;

class WorkOrder extends Model implements WorkOrderInterface
{
    protected $fillable = [
        'number', 'customer_id', 'equipment_id', 'service_contract_id',
        'technician_id', 'status', 'priority', 'service_type',
        'description', 'required_skills', 'site_location_lat',
        'site_location_lng', 'scheduled_date', 'response_sla_minutes',
        'resolution_sla_minutes'
    ];
    
    protected $casts = [
        'required_skills' => 'array',
        'scheduled_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'status' => WorkOrderStatus::class,
        'priority' => WorkOrderPriority::class,
    ];
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getNumber(): string
    {
        return $this->number;
    }
    
    public function getStatus(): WorkOrderStatus
    {
        return $this->status;
    }
    
    public function setStatus(WorkOrderStatus $status): void
    {
        $this->status = $status;
    }
    
    public function getTechnicianId(): ?string
    {
        return $this->technician_id;
    }
    
    public function assignTechnician(string $technicianId): void
    {
        $this->technician_id = $technicianId;
        $this->status = WorkOrderStatus::Assigned;
    }
    
    // ... implement remaining interface methods
}
```

### Step 4: Create Repository Implementations

#### WorkOrderRepository

```php
<?php

namespace App\Repositories\FieldService;

use App\Models\WorkOrder;
use Nexus\FieldService\Contracts\WorkOrderInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\Exceptions\WorkOrderNotFoundException;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class EloquentWorkOrderRepository implements WorkOrderRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}
    
    public function findById(string $id): WorkOrderInterface
    {
        $workOrder = WorkOrder::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('id', $id)
            ->first();
            
        if (!$workOrder) {
            throw WorkOrderNotFoundException::forId($id);
        }
        
        return $workOrder;
    }
    
    public function save(WorkOrderInterface $workOrder): void
    {
        $workOrder->save();
    }
    
    public function findByTechnician(string $technicianId, ?string $status = null): array
    {
        $query = WorkOrder::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('technician_id', $technicianId);
            
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->get()->all();
    }
    
    public function findScheduledBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return WorkOrder::where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->whereBetween('scheduled_date', [$start, $end])
            ->get()
            ->all();
    }
}
```

### Step 5: Create Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\Contracts\ServiceContractRepositoryInterface;
use Nexus\FieldService\Contracts\TechnicianAssignmentStrategyInterface;
use Nexus\FieldService\Contracts\GpsTrackerInterface;
use Nexus\FieldService\Contracts\RouteOptimizerInterface;
use Nexus\FieldService\Contracts\MobileSyncManagerInterface;
use Nexus\FieldService\Core\Assignment\ProximityAssignmentStrategy;
use Nexus\FieldService\Core\Sync\LastWriteWinsSyncManager;
use App\Repositories\FieldService\EloquentWorkOrderRepository;
use App\Repositories\FieldService\EloquentServiceContractRepository;
use App\Services\FieldService\GoogleMapsGpsTracker;
use App\Services\FieldService\GoogleRoutesOptimizer;

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
        
        // GPS Tracking (using Nexus\Geo)
        $this->app->singleton(
            GpsTrackerInterface::class,
            GoogleMapsGpsTracker::class
        );
        
        // Route Optimization (using Nexus\Routing)
        $this->app->singleton(
            RouteOptimizerInterface::class,
            GoogleRoutesOptimizer::class
        );
        
        // Technician Assignment Strategy
        $this->app->singleton(
            TechnicianAssignmentStrategyInterface::class,
            ProximityAssignmentStrategy::class
        );
        
        // Mobile Sync Manager
        $this->app->singleton(
            MobileSyncManagerInterface::class,
            function ($app) {
                return new LastWriteWinsSyncManager(
                    $app->make(WorkOrderRepositoryInterface::class)
                );
            }
        );
    }
}
```

### Step 6: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\FieldServiceProvider::class,
],
```

---

## Your First Integration

### Example 1: Create and Assign Work Order

```php
<?php

use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\Contracts\TechnicianAssignmentStrategyInterface;
use Nexus\FieldService\Enums\WorkOrderStatus;
use Nexus\FieldService\Enums\WorkOrderPriority;
use Nexus\FieldService\ValueObjects\SkillSet;
use Nexus\FieldService\ValueObjects\GpsLocation;
use App\Models\WorkOrder;

// Inject dependencies
public function __construct(
    private readonly WorkOrderRepositoryInterface $workOrderRepository,
    private readonly TechnicianAssignmentStrategyInterface $assignmentStrategy
) {}

public function createWorkOrder(array $data): WorkOrder
{
    // Create work order
    $workOrder = new WorkOrder([
        'number' => 'WO-' . time(),
        'customer_id' => $data['customer_id'],
        'description' => $data['description'],
        'status' => WorkOrderStatus::Draft,
        'priority' => WorkOrderPriority::from($data['priority']),
        'service_type' => $data['service_type'],
        'required_skills' => $data['required_skills'] ?? [],
        'site_location_lat' => $data['latitude'],
        'site_location_lng' => $data['longitude'],
        'scheduled_date' => now()->addDay(),
    ]);
    
    $this->workOrderRepository->save($workOrder);
    
    // Auto-assign technician based on strategy (proximity, skills, workload)
    $technicianId = $this->assignmentStrategy->assignTechnician($workOrder);
    $workOrder->assignTechnician($technicianId);
    $this->workOrderRepository->save($workOrder);
    
    return $workOrder;
}
```

### Example 2: Start Work Order with GPS Validation

```php
<?php

use Nexus\FieldService\Contracts\GpsTrackerInterface;
use Nexus\FieldService\ValueObjects\GpsLocation;
use Nexus\FieldService\Enums\WorkOrderStatus;
use Nexus\FieldService\Exceptions\InvalidGpsLocationException;

public function startWorkOrder(string $workOrderId, float $lat, float $lng): void
{
    $workOrder = $this->workOrderRepository->findById($workOrderId);
    
    // Validate technician is on-site (within 100m of customer location)
    $technicianLocation = new GpsLocation($lat, $lng);
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
        throw new InvalidGpsLocationException('Technician must be at customer site to start work');
    }
    
    // Record start time and GPS location
    $workOrder->setStatus(WorkOrderStatus::InProgress);
    $workOrder->started_at = now();
    $workOrder->start_gps_lat = $lat;
    $workOrder->start_gps_lng = $lng;
    
    $this->workOrderRepository->save($workOrder);
}
```

### Example 3: Complete Work Order with Signature

```php
<?php

use Nexus\FieldService\Contracts\SignatureRepositoryInterface;
use Nexus\FieldService\ValueObjects\CustomerSignature;
use Nexus\FieldService\Enums\WorkOrderStatus;
use Nexus\FieldService\Exceptions\SignatureRequiredException;

public function completeWorkOrder(
    string $workOrderId,
    string $signatureData,
    array $checklistItems,
    float $lat,
    float $lng
): void {
    $workOrder = $this->workOrderRepository->findById($workOrderId);
    
    // Validate checklist is completed
    $this->validateChecklist($workOrder, $checklistItems);
    
    // Capture customer signature
    $signature = new CustomerSignature(
        workOrderId: $workOrderId,
        signatureData: $signatureData,
        capturedAt: now(),
        capturedBy: auth()->id()
    );
    
    $signaturePath = $this->signatureRepository->store($signature);
    
    // Record completion
    $workOrder->setStatus(WorkOrderStatus::Completed);
    $workOrder->completed_at = now();
    $workOrder->completion_gps_lat = $lat;
    $workOrder->completion_gps_lng = $lng;
    $workOrder->signature_path = $signaturePath;
    
    // Calculate labor hours
    $laborHours = $workOrder->started_at->diffInHours($workOrder->completed_at);
    $workOrder->labor_hours = $laborHours;
    
    $this->workOrderRepository->save($workOrder);
}
```

---

## Next Steps

- **[API Reference](api-reference.md)** - Complete documentation of all interfaces and contracts
- **[Integration Guide](integration-guide.md)** - Laravel and Symfony integration examples
- **[Basic Usage Examples](examples/basic-usage.php)** - Common usage patterns
- **[Advanced Usage Examples](examples/advanced-usage.php)** - Complex scenarios

---

## Troubleshooting

### Issue 1: "Technician not available" error when assigning

**Cause:** Technician is already assigned to another active work order or marked unavailable.

**Solution:**
```php
// Check technician availability before assignment
$availableTechnicians = $this->technicianRepository->findAvailable($scheduledDate);

if (empty($availableTechnicians)) {
    // Reschedule or escalate to supervisor
}
```

### Issue 2: GPS validation failing even when on-site

**Cause:** GPS accuracy issues or geofence radius too tight.

**Solution:**
```php
// Increase geofence radius for urban environments with tall buildings
$isOnSite = $this->gpsTracker->validateLocation(
    technicianLocation: $currentLocation,
    customerLocation: $workOrder->siteLocation,
    radiusMeters: 200 // Increased from 100m
);
```

### Issue 3: Offline sync conflicts

**Cause:** Work order modified both offline (technician) and online (dispatcher).

**Solution:**
```php
try {
    $this->syncManager->sync($offlineWorkOrder);
} catch (SyncConflictException $e) {
    // Show conflict resolution UI
    $conflicts = $e->getConflicts();
    
    // Options:
    // 1. Keep offline version (technician wins)
    // 2. Keep online version (dispatcher wins)
    // 3. Manual merge (user chooses field-by-field)
}
```

### Issue 4: SLA breach false positives

**Cause:** SLA calculation not excluding non-business hours.

**Solution:**
```php
// Ensure service contract has business hours configured
$contract->business_hours_only = true;
$contract->business_hours = [
    'start' => '08:00',
    'end' => '17:00',
    'excludeDays' => [Carbon::SATURDAY, Carbon::SUNDAY]
];
```

### Issue 5: Preventive maintenance duplicates

**Cause:** Deduplication not checking recent PM work orders.

**Solution:**
```php
// Deduplication checks existing PM within 30-day window
$deduplicationWindow = 30; // days

$existingPm = $this->workOrderRepository->findPreventiveMaintenance(
    equipmentId: $equipment->getId(),
    fromDate: now()->subDays($deduplicationWindow)
);

if ($existingPm) {
    throw new MaintenanceAlreadyScheduledException();
}
```

---

## Performance Tips

### Tip 1: Bulk Load Work Orders for Route Optimization

```php
// Load all work orders for technician's daily route
$workOrders = $this->workOrderRepository->findScheduledBetween(
    start: now()->startOfDay(),
    end: now()->endOfDay()
);

// Optimize route once for all work orders
$optimizedRoute = $this->routeOptimizer->optimize($workOrders);
```

### Tip 2: Cache Service Contracts

```php
// Cache active contracts to avoid repeated DB queries
$contract = Cache::remember(
    "service_contract:{$customerId}",
    3600,
    fn() => $this->contractRepository->findActiveByCustomer($customerId)
);
```

### Tip 3: Batch GPS Location Updates

```php
// Queue GPS updates instead of real-time processing
dispatch(new UpdateTechnicianLocationJob($technicianId, $lat, $lng))
    ->onQueue('low-priority');
```

---

**Package Version:** 1.0.0  
**Last Updated:** 2025-01-25  
**Next Review:** 2025-04-25 (Quarterly)
