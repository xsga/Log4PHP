<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

interface LoggerConfigurator
{
    public function configure(LoggerHierarchy $hierarchy, string|array|null $input = null): void;
}
