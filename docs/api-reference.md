# API Reference: FieldService

Complete documentation of all interfaces, value objects, enums, and exceptions in the FieldService package.

---

## Interfaces

### WorkOrderInterface

**Location:** `src/Contracts/WorkOrderInterface.php`

**Purpose:** Defines the contract for work order entities in field service operations.

**Methods:**

#### getId()
```php
public function getId(): string;
```
Returns the work order's unique identifier (ULID).

#### getNumber()
```php
public function getNumber(): string;
```
Returns the human-readable work order number (e.g., "WO-12345").

#### getStatus()
```php
public function getStatus(): WorkOrderStatus;
```
Returns the current status enum (Draft, Assigned, InProgress, Completed, Verified, Cancelled).

#### setStatus()
```php
public function setStatus(WorkOrderStatus $status): void;
```
Updates the work order status. Validates state transitions.

#### getTechnicianId()
```php
public function getTechnicianId(): ?string;
```
Returns the assigned technician's ID, or null if unassigned.

#### assignTechnician()
```php
public function assignTechnician(string $technicianId): void;
```
Assigns a technician to the work order and updates status to Assigned.

---

### WorkOrderRepositoryInterface

**Location:** `src/Contracts/WorkOrderRepositoryInterface.php`

**Purpose:** Persistence operations for work orders.

**Methods:**

#### findById()
```php
public function findById(string $id): WorkOrderInterface;
```
**Throws:** `WorkOrderNotFoundException` if not found.

#### save()
```php
public function save(WorkOrderInterface $workOrder): void;
```
Persists work order changes to storage.

#### findByTechnician()
```php
public function findByTechnician(string $technicianId, ?string $status = null): array;
```
Returns all work orders for a technician, optionally filtered by status.

#### findScheduledBetween()
```php
public function findScheduledBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): array;
```
Returns work orders scheduled within a date range.

---

### ServiceContractInterface

**Location:** `src/Contracts/ServiceContractInterface.php`

**Purpose:** Represents service level agreements and maintenance contracts.

**Methods:**

#### getResponseSla()
```php
public function getResponseSla(): int;
```
Returns response time SLA in minutes.

#### getResolutionSla()
```php
public function getResolutionSla(): int;
```
Returns resolution time SLA in minutes.

#### isActiveForEquipment()
```php
public function isActiveForEquipment(string $equipmentId): bool;
```
Checks if contract covers specific equipment.

---

### TechnicianAssignmentStrategyInterface

**Location:** `src/Contracts/TechnicianAssignmentStrategyInterface.php`

**Purpose:** Defines algorithms for assigning technicians to work orders.

**Methods:**

#### assignTechnician()
```php
public function assignTechnician(WorkOrderInterface $workOrder): string;
```
Returns the ID of the best-suited technician for the work order.

**Implementations:**
- `ProximityAssignmentStrategy` - Assigns nearest technician
- `SkillsMatchStrategy` - Assigns based on skill requirements
- `WorkloadBalancedStrategy` - Distributes work evenly

---

### GpsTrackerInterface

**Location:** `src/Contracts/GpsTrackerInterface.php`

**Purpose:** GPS location tracking and geofencing validation.

**Methods:**

#### validateLocation()
```php
public function validateLocation(
    GpsLocation $technicianLocation,
    GpsLocation $customerLocation,
    int $radiusMeters
): bool;
```
Returns true if technician is within specified radius of customer site.

#### trackLocation()
```php
public function trackLocation(string $technicianId, GpsLocation $location): void;
```
Records technician's current location for tracking and audit.

---

### RouteOptimizerInterface

**Location:** `src/Contracts/RouteOptimizerInterface.php`

**Purpose:** Optimizes technician routes for multiple work orders.

**Methods:**

#### optimize()
```php
public function optimize(array $workOrders): array;
```
Returns work orders sorted in optimal visit sequence.

**Parameters:**
- `$workOrders` - Array of `WorkOrderInterface` instances

**Returns:** Reordered array optimized for minimal travel time.

---

### MobileSyncManagerInterface

**Location:** `src/Contracts/MobileSyncManagerInterface.php`

**Purpose:** Manages offline mobile data synchronization.

**Methods:**

#### sync()
```php
public function sync(WorkOrderInterface $offlineWorkOrder): void;
```
Synchronizes offline work order changes with server.

**Throws:** `SyncConflictException` if server version is newer.

#### resolveConflict()
```php
public function resolveConflict(
    WorkOrderInterface $serverVersion,
    WorkOrderInterface $clientVersion,
    string $strategy
): WorkOrderInterface;
```
Resolves sync conflicts using specified strategy (last_write_wins, manual_merge, field_merge).

---

### SlaCalculatorInterface

**Location:** `src/Contracts/SlaCalculatorInterface.php`

**Purpose:** Calculates SLA compliance and breach detection.

**Methods:**

#### calculateResponseTime()
```php
public function calculateResponseTime(
    \DateTimeImmutable $createdAt,
    \DateTimeImmutable $respondedAt,
    bool $businessHoursOnly
): int;
```
Returns response time in minutes, optionally excluding non-business hours.

#### isSlaBre ached()
```php
public function isSlaBreached(
    \DateTimeImmutable $createdAt,
    int $slaMinutes,
    bool $businessHoursOnly
): bool;
```
Returns true if SLA has been breached based on current time.

---

### ChecklistItemInterface

**Location:** `src/Contracts/ChecklistItemInterface.php`

**Purpose:** Represents individual checklist items for work order completion.

**Methods:**

#### isCompleted()
```php
public function isCompleted(): bool;
```
Returns true if checklist item has been completed.

#### getValue()
```php
public function getValue(): ?string;
```
Returns the recorded value (text, measurement, etc.).

---

### PartsConsumptionInterface

**Location:** `src/Contracts/PartsConsumptionInterface.php`

**Purpose:** Tracks parts used during work order execution.

**Methods:**

#### getPartId()
```php
public function getPartId(): string;
```
Returns the consumed part's ID.

#### getQuantity()
```php
public function getQuantity(): int;
```
Returns quantity consumed.

#### getCost()
```php
public function getCost(): float;
```
Returns total cost (quantity × unit price).

---

## Value Objects

### GpsLocation

**Location:** `src/ValueObjects/GpsLocation.php`

**Purpose:** Immutable GPS coordinates.

**Properties:**
- `latitude` (float) - Latitude coordinate (-90 to 90)
- `longitude` (float) - Longitude coordinate (-180 to 180)

**Methods:**

#### constructor
```php
public function __construct(
    public readonly float $latitude,
    public readonly float $longitude
)
```

**Validation:**
- Latitude must be between -90 and 90
- Longitude must be between -180 and 180

**Example:**
```php
$location = new GpsLocation(latitude: 3.1390, longitude: 101.6869); // Kuala Lumpur
```

---

### SkillSet

**Location:** `src/ValueObjects/SkillSet.php`

**Purpose:** Represents technician skills or work order requirements.

**Properties:**
- `skills` (array<string>) - List of skill identifiers

**Methods:**

#### hasSkill()
```php
public function hasSkill(string $skill): bool;
```
Returns true if skill set contains the specified skill.

#### matchesRequirements()
```php
public function matchesRequirements(SkillSet $required): bool;
```
Returns true if this skill set satisfies all required skills.

**Example:**
```php
$technicianSkills = new SkillSet(['HVAC', 'Electrical', 'Plumbing']);
$workOrderRequires = new SkillSet(['HVAC', 'Electrical']);

$technicianSkills->matchesRequirements($workOrderRequires); // true
```

---

### LaborHours

**Location:** `src/ValueObjects/LaborHours.php`

**Purpose:** Represents labor hours worked on a work order.

**Properties:**
- `hours` (float) - Total hours worked (supports decimal values)

**Methods:**

#### toDecimal()
```php
public function toDecimal(): float;
```
Returns hours as decimal (e.g., 2.5 hours).

#### toHoursAndMinutes()
```php
public function toHoursAndMinutes(): array;
```
Returns `['hours' => 2, 'minutes' => 30]`.

---

## Enums

### WorkOrderStatus

**Location:** `src/Enums/WorkOrderStatus.php`

**Purpose:** Work order lifecycle states.

**Cases:**
- `Draft` - Work order created, not yet assigned
- `Assigned` - Technician assigned, not started
- `InProgress` - Technician actively working
- `Paused` - Temporarily suspended
- `Completed` - Work finished, awaiting verification
- `Verified` - Quality checked, ready for invoicing
- `Cancelled` - Cancelled before completion

**Example:**
```php
$workOrder->setStatus(WorkOrderStatus::InProgress);
```

---

### WorkOrderPriority

**Location:** `src/Enums/WorkOrderPriority.php`

**Purpose:** Work order urgency levels.

**Cases:**
- `Emergency` - Critical breakdown requiring immediate response
- `High` - Urgent but not emergency
- `Normal` - Standard priority
- `Low` - Can be scheduled for convenience

**Example:**
```php
$priority = WorkOrderPriority::Emergency;
```

---

### MaintenanceType

**Location:** `src/Enums/MaintenanceType.php`

**Purpose:** Types of maintenance work.

**Cases:**
- `Preventive` - Scheduled routine maintenance
- `Corrective` - Repair after failure
- `Predictive` - Based on condition monitoring
- `Emergency` - Urgent breakdown response

---

## Exceptions

### WorkOrderNotFoundException

**Location:** `src/Exceptions/WorkOrderNotFoundException.php`

**Extends:** `FieldServiceException`

**Purpose:** Thrown when work order lookup fails.

**Factory Methods:**

#### forId()
```php
public static function forId(string $id): self
```
Returns exception with message "Work order with ID [{$id}] not found".

---

### InvalidWorkOrderStateException

**Location:** `src/Exceptions/InvalidWorkOrderStateException.php`

**Purpose:** Thrown when attempting invalid state transitions.

**Factory Methods:**

#### cannotTransition()
```php
public static function cannotTransition(WorkOrderStatus $from, WorkOrderStatus $to): self
```
Returns exception explaining invalid state transition.

**Example:**
```php
// Attempting to start unassigned work order
throw InvalidWorkOrderStateException::cannotTransition(
    WorkOrderStatus::Draft,
    WorkOrderStatus::InProgress
);
```

---

### TechnicianNotAvailableException

**Location:** `src/Exceptions/TechnicianNotAvailableException.php`

**Purpose:** Thrown when technician cannot be assigned.

**Factory Methods:**

#### forTechnician()
```php
public static function forTechnician(string $technicianId, string $reason): self
```

---

### SignatureRequiredException

**Location:** `src/Exceptions/SignatureRequiredException.php`

**Purpose:** Thrown when completing work order without customer signature.

---

### InvalidGpsLocationException

**Location:** `src/Exceptions/InvalidGpsLocationException.php`

**Purpose:** Thrown when GPS coordinates are invalid or technician not on-site.

---

### OfflineSyncConflictException

**Location:** `src/Exceptions/OfflineSyncConflictException.php`

**Purpose:** Thrown when offline changes conflict with server version.

**Methods:**

#### getConflicts()
```php
public function getConflicts(): array;
```
Returns array of conflicting fields with server and client values.

---

## Usage Patterns

### Pattern 1: Work Order Lifecycle Management

```php
// Create → Assign → Start → Complete → Verify
$workOrder = new WorkOrder(['status' => WorkOrderStatus::Draft]);
$workOrder->assignTechnician($technicianId); // Draft → Assigned
$workOrder->setStatus(WorkOrderStatus::InProgress); // Assigned → InProgress
$workOrder->setStatus(WorkOrderStatus::Completed); // InProgress → Completed
$workOrder->setStatus(WorkOrderStatus::Verified); // Completed → Verified
```

### Pattern 2: GPS Validation Before State Transition

```php
// Always validate GPS before starting/completing work
if (!$gpsTracker->validateLocation($technicianGps, $customerGps, 100)) {
    throw new InvalidGpsLocationException('Not on-site');
}
$workOrder->setStatus(WorkOrderStatus::InProgress);
```

### Pattern 3: Offline Sync with Conflict Handling

```php
try {
    $syncManager->sync($offlineWorkOrder);
} catch (OfflineSyncConflictException $e) {
    $resolved = $syncManager->resolveConflict(
        $e->getServerVersion(),
        $e->getClientVersion(),
        'last_write_wins'
    );
    $repository->save($resolved);
}
```

---

**Package Version:** 1.0.0  
**Last Updated:** 2025-01-25
