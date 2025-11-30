# Nexus\FieldService

Framework-agnostic field service management engine for work orders, technician dispatch, mobile job execution, service contracts, and SLA tracking.

## Overview

The **FieldService** package provides a complete solution for managing field service operations including:

- Work order lifecycle management (NEW → SCHEDULED → IN_PROGRESS → COMPLETED → VERIFIED)
- Intelligent technician assignment based on skills, proximity, and capacity
- Service contract management with SLA tracking
- Mobile job execution with offline sync capability
- Parts consumption with van stock waterfall logic
- GPS location tracking with privacy controls
- Customer signature capture and verification
- Automated preventive maintenance scheduling
- Service report generation

## Features by Tier

### Tier 1: Basic Work Orders
- Manual work order creation and assignment
- Technician schedule management
- Basic parts consumption tracking
- Customer signature capture (SHA-256 hash)
- Service report PDF generation

### Tier 2: Service Contracts & Preventive Maintenance
- Service contract management with asset linkage
- SLA deadline tracking and breach alerts
- Automated preventive maintenance scheduling (7 days before due date)
- Maintenance deduplication (±3 days conflict detection)
- Checklist templates and validation

### Tier 3: Enterprise Features
- ML-powered technician assignment via `Nexus\Intelligence`
- VRP route optimization via `Nexus\Routing`
- RFC 3161 cryptographic timestamp signing for signatures
- Advanced GPS tracking and analytics
- Event sourcing for compliance auditing

## Installation

```bash
composer require nexus/field-service
```

## Key Concepts

### Work Order States

```
NEW → SCHEDULED → IN_PROGRESS → COMPLETED → VERIFIED
  ↓       ↓            ↓            ↓
  └───────┴────────────┴────────────┴─→ CANCELLED
```

### Parts Consumption Waterfall

1. Check technician van stock
2. Deduct available quantity from van
3. Deduct remainder from primary warehouse
4. Update work order parts cost

### SLA Tracking

Service contracts define response times (e.g., "4 hours"). When a work order is created against a contract:

1. Calculate SLA deadline based on response time
2. Schedule escalation check job
3. Monitor progress via `Nexus\Workflow`
4. Trigger escalation workflow on breach

## Dependencies

### Required Packages
- `nexus/party` - Customer and vendor management
- `nexus/backoffice` - Staff/technician management
- `nexus/inventory` - Parts consumption tracking
- `nexus/warehouse` - Van stock management
- `nexus/scheduler` - Preventive maintenance automation
- `nexus/routing` - Route optimization (Tier 3)
- `nexus/geo` - Geocoding and distance calculation
- `nexus/workflow` - SLA escalation
- `nexus/sequencing` - Work order numbering
- `nexus/document` - Service report PDF generation
- `nexus/storage` - Photo and signature storage
- `nexus/notifier` - Multi-channel notifications
- `nexus/audit-logger` - Audit trail
- `nexus/tenant` - Multi-tenancy isolation
- `nexus/product` - Service and parts catalog

### Optional Packages
- `nexus/assets` - Asset tracking and maintenance history
- `nexus/intelligence` - AI-powered assignment (Tier 3)
- `nexus/crypto` - Signature timestamp signing (Tier 3)
- `nexus/event-stream` - Event sourcing for compliance

## Usage

### Creating a Work Order

```php
use Nexus\FieldService\Services\WorkOrderManager;
use Nexus\FieldService\Enums\WorkOrderPriority;
use Nexus\FieldService\Enums\ServiceType;

$workOrder = $workOrderManager->create([
    'customer_party_id' => 'party-123',
    'service_location_id' => 'address-456',
    'service_type' => ServiceType::REPAIR,
    'priority' => WorkOrderPriority::HIGH,
    'description' => 'HVAC system not cooling properly',
    'scheduled_start' => new \DateTimeImmutable('2025-11-25 09:00:00'),
]);
```

### Assigning a Technician

```php
use Nexus\FieldService\Services\TechnicianDispatcher;

// Find best available technician
$technician = $technicianDispatcher->findBestTechnician(
    $workOrder,
    $availableTechnicians
);

// Assign to work order
$workOrderManager->assign($workOrder->getId(), $technician->getId());
```

### Recording Parts Consumption

```php
use Nexus\FieldService\Services\PartsConsumptionManager;

$partsConsumptionManager->recordConsumption(
    $workOrder->getId(),
    $productVariantId,
    $quantity,
    $technicianId
);
// Automatically deducts from van stock first, then warehouse
```

### Capturing Customer Signature

```php
$signatureData = base64_encode($signatureImageBinary);

$workOrderManager->captureSignature(
    $workOrder->getId(),
    $signatureData,
    $technicianId,
    $gpsLocation
);
```

### Generating Service Report

```php
use Nexus\FieldService\Services\ServiceReportGenerator;

$document = $serviceReportGenerator->generate($workOrder->getId());
// Returns DocumentInterface with PDF content
```

## Architecture

This package follows the Nexus monorepo architecture:

- **Package Layer** (`packages/FieldService/`): Framework-agnostic business logic
- **Application Layer** (`apps/Atomy/`): Laravel implementation with Eloquent models and migrations

### Package Structure

```
packages/FieldService/
├── src/
│   ├── Contracts/          # Interfaces for dependency injection
│   ├── Services/           # Business logic orchestrators
│   ├── Core/               # Internal engine components
│   ├── Enums/              # Native PHP 8.3 enums
│   ├── ValueObjects/       # Immutable data structures
│   ├── Events/             # Domain events
│   └── Exceptions/         # Domain-specific exceptions
```

## Testing

```bash
composer test
```

## Documentation

### Getting Started
- **[Getting Started Guide](docs/getting-started.md)** - Comprehensive 840-line guide covering:
  - Prerequisites and installation
  - 7 core concepts (work order lifecycle, assignment strategies, SLA enforcement, preventive maintenance, offline sync, GPS tracking, parts consumption)
  - Configuration and setup
  - Integration examples
  - Troubleshooting and performance tips

### API Reference
- **[API Reference](docs/api-reference.md)** - Complete documentation of:
  - 17 interfaces (WorkOrderInterface, TechnicianAssignmentStrategyInterface, GpsTrackerInterface, MobileSyncManagerInterface, etc.)
  - 3 value objects (GpsLocation, SkillSet, LaborHours)
  - 3 enums (WorkOrderStatus, WorkOrderPriority, MaintenanceType)
  - 14 exceptions with factory methods

### Integration Guides
- **[Integration Guide](docs/integration-guide.md)** - Framework integration examples:
  - Laravel service provider and controller examples
  - Symfony services.yaml and controller examples
  - Common patterns (offline sync, GPS tracking, SLA monitoring)
  - Testing examples

### Code Examples
- **[Basic Usage](docs/examples/basic-usage.php)** - Common operations:
  - Create and auto-assign work orders
  - Start work orders with GPS validation
  - Complete work orders with signatures
  - Service contract validation
  - SLA breach detection
- **[Advanced Usage](docs/examples/advanced-usage.php)** - Complex scenarios:
  - Custom technician assignment strategies
  - Offline mobile sync with conflict resolution
  - Preventive maintenance scheduling with deduplication

### Implementation Documentation
- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - 806-line detailed implementation status
- **[Requirements](REQUIREMENTS.md)** - 100 documented requirements with traceability
- **[Test Suite Summary](TEST_SUITE_SUMMARY.md)** - ~95 tests with coverage metrics
- **[Valuation Matrix](VALUATION_MATRIX.md)** - Package valuation ($820K value, 3,696% ROI)

## License

MIT License. See [LICENSE](LICENSE) file for details.
