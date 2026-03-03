<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\FieldService\Enums\DocumentFormat;

interface DocumentManagerInterface
{
    /**
     * @param string $template
     * @param array<string, mixed> $payload
     */
    public function render(string $template, array $payload, DocumentFormat $format): string;
}
