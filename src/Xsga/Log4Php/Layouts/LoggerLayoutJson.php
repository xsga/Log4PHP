<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Layouts;

use Xsga\Log4Php\LoggerLayout;
use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerLayoutJson extends LoggerLayout
{
    private const array PROMOTED_FIELDS = [
        'event',
        'user_id',
        'user_email',
        'ip',
        'action',
        'resource',
        'error_code'
    ];

    protected bool $prettyPrint = false;

    public function format(LoggerLoggingEvent $event): string
    {
        $data = [
            'timestamp'  => date('Y-m-d\TH:i:s.v\Z', (int)$event->getTimeStamp()),
            'level'      => $event->getLevel()->toString(),
            'message'    => $event->getRenderedMessage(),
            'request_id' => $_ENV['REQUEST_ID'] ?? null,
        ];

        $data = $this->setPromotedFields($event, $data);

        $data['location'] = $this->getLocationInformation($event);
        $data['context']  = $event->getContext();

        $json = json_encode($data, $this->getFlags());
        if ($json === false) {
            $errorMsg = 'Failed to encode log event to JSON. Using fallback message.';
            trigger_error('log4php: [' . get_class($this) . ']: ' . $errorMsg, E_USER_WARNING);
            $json = '{"error":"Failed to encode log event to JSON."}';
        }

        return $json . PHP_EOL;
    }

    private function setPromotedFields(LoggerLoggingEvent $event, array $data): array
    {
        $context = $event->getContext();

        if (!empty($context)) {
            foreach (self::PROMOTED_FIELDS as $field) {
                if (isset($context[$field])) {
                    /** @psalm-suppress MixedAssignment */
                    $data[$field] = $context[$field];
                }
            }
        }

        return $data;
    }

    public function setPrettyPrint(bool|string|int $prettyPrint): void
    {
        if (is_string($prettyPrint)) {
            $value = strtolower(trim($prettyPrint));
            $this->prettyPrint = in_array($value, ['1', 'true', 'yes', 'on'], true);
            return;
        }

        $this->prettyPrint = (bool)$prettyPrint;
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
