<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

final class LoggerRoot extends Logger
{
    public function __construct(?LoggerLevel $level = null)
    {
        parent::__construct('root');

        if ($level === null) {
            $level = LoggerLevel::getLevelAll();
        }

        $this->setLevel($level);
    }

    public function getEffectiveLevel(): ?LoggerLevel
    {
        return $this->getLevel();
    }

    public function setLevel(?LoggerLevel $level = null): void
    {
        if ($level !== null) {
            parent::setLevel($level);
            return;
        }

        trigger_error('log4php: Cannot set LoggerRoot level to null.', E_USER_WARNING);
    }

    public function setParent(Logger $logger): void
    {
        trigger_error('log4php: LoggerRoot cannot have a parent.', E_USER_WARNING);
    }
}
