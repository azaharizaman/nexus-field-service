<?php

declare(strict_types=1);

namespace Nexus\FieldService\Core\Assignment;

use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\FieldService\Contracts\TechnicianAssignmentStrategyInterface;
use Nexus\FieldService\Contracts\WorkOrderInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\ValueObjects\SkillSet;
use Nexus\Geo\Services\ProximityService;
use Psr\Log\LoggerInterface;

/**
 * Default Technician Assignment Strategy (Tier 1)
 *
 * Assigns technicians based on:
 * 1. Required skills match
 * 2. Location proximity to job site
 * 3. Daily capacity availability (≤8 hours)
 */
final readonly class DefaultAssignmentStrategy implements TechnicianAssignmentStrategyInterface
{
    private const float MAX_DAILY_HOURS = 8.0;
    private const int MAX_SCORE = 100;

    public function __construct(
        private WorkOrderRepositoryInterface $workOrderRepository,
        private ProximityService $proximityService,
        private LoggerInterface $logger
    ) {
    }

    public function findBestTechnician(
        WorkOrderInterface $workOrder,
        array $availableTechnicians
    ): ?StaffInterface {
        $scoredTechnicians = [];

        foreach ($availableTechnicians as $technician) {
            // Check if technician has required skills
            if (!$this->hasRequiredSkills($workOrder, $technician)) {
                continue;
            }

            // Check if technician has capacity
            if (!$this->hasCapacity($technician, $workOrder)) {
                continue;
            }

            $score = $this->scoreTechnician($workOrder, $technician);
            $scoredTechnicians[] = [
                'technician' => $technician,
                'score' => $score,
            ];
        }

        if (empty($scoredTechnicians)) {
            $this->logger->warning('No suitable technician found for work order', [
                'work_order_id' => $workOrder->getId(),
                'service_type' => $workOrder->getServiceType()->value,
            ]);
            return null;
        }

        // Sort by score descending
        usort($scoredTechnicians, fn($a, $b) => $b['score'] <=> $a['score']);

        return $scoredTechnicians[0]['technician'];
    }

    public function scoreTechnician(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): float {
        $scores = [];

        // Skills match score (40% weight)
        $scores['skills'] = $this->calculateSkillScore($workOrder, $technician) * 0.4;

        // Proximity score (40% weight)
        $scores['proximity'] = $this->calculateProximityScore($workOrder, $technician) * 0.4;

        // Capacity score (20% weight)
        $scores['capacity'] = $this->calculateCapacityScore($workOrder, $technician) * 0.2;

        $totalScore = array_sum($scores);

        $this->logger->debug('Technician scored', [
            'technician_id' => $technician->getId(),
            'work_order_id' => $workOrder->getId(),
            'scores' => $scores,
            'total' => $totalScore,
        ]);

        return $totalScore;
    }

    /**
     * Check if technician has all required skills.
     */
    private function hasRequiredSkills(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): bool {
        $requiredSkills = $this->getRequiredSkills($workOrder);
        
        if ($requiredSkills->isEmpty()) {
            return true; // No specific skills required
        }

        $technicianSkills = $this->getTechnicianSkills($technician);
        
        return $technicianSkills->matches($requiredSkills);
    }

    /**
     * Check if technician has capacity for this work order.
     */
    private function hasCapacity(
        StaffInterface $technician,
        WorkOrderInterface $workOrder
    ): bool {
        if ($workOrder->getScheduledStart() === null) {
            return true; // No specific date yet
        }

        $date = $workOrder->getScheduledStart();
        $scheduledHours = $this->workOrderRepository->getTechnicianScheduledHours(
            $technician->getId(),
            $date
        );

        $estimatedDuration = $this->estimateJobDuration($workOrder);
        
        return ($scheduledHours + $estimatedDuration) <= self::MAX_DAILY_HOURS;
    }

    /**
     * Calculate skill match score (0-100).
     */
    private function calculateSkillScore(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): float {
        $requiredSkills = $this->getRequiredSkills($workOrder);
        
        if ($requiredSkills->isEmpty()) {
            return self::MAX_SCORE; // No skills required = perfect match
        }

        $technicianSkills = $this->getTechnicianSkills($technician);
        
        // Calculate overlap percentage
        $intersection = $technicianSkills->intersect($requiredSkills);
        $matchPercentage = $intersection->count() / $requiredSkills->count();
        
        return $matchPercentage * self::MAX_SCORE;
    }

    /**
     * Calculate proximity score based on distance to job site (0-100).
     */
    private function calculateProximityScore(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): float {
        // If no location data, return neutral score
        if ($workOrder->getServiceLocationId() === null) {
            return 50.0;
        }

        // TODO: Get technician current location and calculate distance
        // For now, return neutral score
        return 50.0;
    }

    /**
     * Calculate capacity score based on available time (0-100).
     */
    private function calculateCapacityScore(
        WorkOrderInterface $workOrder,
        StaffInterface $technician
    ): float {
        if ($workOrder->getScheduledStart() === null) {
            return self::MAX_SCORE;
        }

        $date = $workOrder->getScheduledStart();
        $scheduledHours = $this->workOrderRepository->getTechnicianScheduledHours(
            $technician->getId(),
            $date
        );

        $availableHours = self::MAX_DAILY_HOURS - $scheduledHours;
        $utilizationPercentage = $availableHours / self::MAX_DAILY_HOURS;
        
        return $utilizationPercentage * self::MAX_SCORE;
    }

    /**
     * Get required skills from work order metadata.
     */
    private function getRequiredSkills(WorkOrderInterface $workOrder): SkillSet
    {
        $metadata = $workOrder->getMetadata();
        
        if (!isset($metadata['required_skills'])) {
            return SkillSet::empty();
        }

        return SkillSet::fromArray($metadata['required_skills']);
    }

    /**
     * Get technician skills from staff metadata.
     */
    private function getTechnicianSkills(StaffInterface $technician): SkillSet
    {
        // TODO: Get skills from Nexus\Backoffice staff competencies
        // For now, return empty skill set
        return SkillSet::empty();
    }

    /**
     * Estimate job duration based on service type.
     */
    private function estimateJobDuration(WorkOrderInterface $workOrder): float
    {
        $serviceType = $workOrder->getServiceType();
        $baseDuration = 2.0; // 2 hours base estimate
        
        return $baseDuration * $serviceType->durationMultiplier();
    }
}
