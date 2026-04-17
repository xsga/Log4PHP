<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Renderers;

interface LoggerRenderer
{
    public function render(mixed $input): string;
}
