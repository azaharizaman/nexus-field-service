<?php

declare(strict_types=1);

namespace Nexus\FieldService\ValueObjects;

/**
 * Skill Set Value Object
 *
 * Immutable representation of technician skills/certifications required for work orders.
 *
 * @example new SkillSet(['HVAC', 'Electrical', 'Plumbing'])
 */
final readonly class SkillSet
{
    /**
     * @param array<string> $skills
     */
    private function __construct(
        private array $skills
    ) {
        // Normalize skills: trim, uppercase, remove duplicates, sort
        $normalized = array_map(
            fn(string $skill) => strtoupper(trim($skill)),
            $skills
        );
        
        $this->skills = array_values(array_unique($normalized));
        sort($this->skills);
    }

    /**
     * Create a SkillSet from an array of skill names.
     *
     * @param array<string> $skills
     */
    public static function fromArray(array $skills): self
    {
        return new self($skills);
    }

    /**
     * Create an empty SkillSet.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Get all skills in this set.
     *
     * @return array<string>
     */
    public function toArray(): array
    {
        return $this->skills;
    }

    /**
     * Check if this skill set contains a specific skill.
     */
    public function has(string $skill): bool
    {
        return in_array(strtoupper(trim($skill)), $this->skills, true);
    }

    /**
     * Check if this skill set matches (contains all of) the required skills.
     *
     * Used for technician assignment: technician must have ALL required skills.
     */
    public function matches(self $required): bool
    {
        foreach ($required->skills as $skill) {
            if (!$this->has($skill)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the count of skills in this set.
     */
    public function count(): int
    {
        return count($this->skills);
    }

    /**
     * Check if this skill set is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->skills);
    }

    /**
     * Merge this skill set with another, returning a new SkillSet.
     */
    public function merge(self $other): self
    {
        return new self(array_merge($this->skills, $other->skills));
    }

    /**
     * Get skills that are in this set but not in the other set.
     */
    public function difference(self $other): self
    {
        $diff = array_diff($this->skills, $other->skills);
        return new self($diff);
    }

    /**
     * Get skills that are in both this set and the other set.
     */
    public function intersect(self $other): self
    {
        $intersection = array_intersect($this->skills, $other->skills);
        return new self($intersection);
    }

    /**
     * Check if this skill set equals another.
     */
    public function equals(self $other): bool
    {
        return $this->skills === $other->skills;
    }

    public function __toString(): string
    {
        return implode(', ', $this->skills);
    }
}
