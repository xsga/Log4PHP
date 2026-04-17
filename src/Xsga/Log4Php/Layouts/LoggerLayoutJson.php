<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Layouts;

use Xsga\Log4Php\LoggerLayout;
use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerLayoutJson extends LoggerLayout
{
    protected bool $prettyPrint = false;

    public function setPrettyPrint(bool|string|int $prettyPrint): void
    {
        if (is_string($prettyPrint)) {
            $value = strtolower(trim($prettyPrint));
            $this->prettyPrint = in_array($value, ['1', 'true', 'yes', 'on'], true);
            return;
        }

        $this->prettyPrint = (bool)$prettyPrint;
    }

    public function format(LoggerLoggingEvent $event): string
    {
        $data = [
            'timestamp'  => date('Y-m-d\TH:i:s.v\Z', (int)$event->getTimeStamp()),
            'level'      => $event->getLevel()->toString(),
            'message'    => $event->getMessage(),
            'request_id' => $_ENV['REQUEST_ID'] ?? null,
        ];

        $data['location'] = $this->getLocationInformation($event);

        $context = $event->getContext();

        if (!empty($context)) {
            $promotedFields = ['event', 'user_id', 'user_email', 'ip', 'action', 'resource', 'error_code'];

            foreach ($promotedFields as $field) {
                if (isset($context[$field])) {
                    /** @psalm-suppress MixedAssignment */
                    $data[$field] = $context[$field];
                }
            }

            $data['context'] = $context;
        }

        $json = json_encode($data, $this->getFlags());
        if ($json === false) {
            $json = '{"error":"Failed to encode log event to JSON."}';
        }

        return $json . PHP_EOL;
    }

    public function activateOptions(): void
    {
    }

    private function getFlags(): int
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }
        return $flags;
    }

    private function getLocationInformation(LoggerLoggingEvent $event): array
    {
        $location = $event->getLocationInformation();

        return [
            'class'  => $location->getClassName(),
            'method' => $location->getMethodName(),
            'file'   => $location->getFileName(),
            'line'   => $location->getLineNumber(),
        ];
    }
}
