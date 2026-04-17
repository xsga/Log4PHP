<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Configurators;

interface LoggerConfigurationAdapter
{
    public function convert(string $input): array;
}
