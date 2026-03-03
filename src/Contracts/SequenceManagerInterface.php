<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

interface SequenceManagerInterface
{
    /** @param array<string, scalar> $context */
    public function next(string $sequenceName, array $context = []): int;
}
