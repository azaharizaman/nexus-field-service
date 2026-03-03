<?php

declare(strict_types=1);

namespace Nexus\FieldService\Contracts;

use Nexus\FieldService\Enums\DocumentFormat;

interface ContentProcessorInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $templateName, array $data, DocumentFormat $format): string;
}
