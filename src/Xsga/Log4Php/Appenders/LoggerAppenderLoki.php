<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Appenders;

use Xsga\Log4Php\LoggerAppender;
use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerAppenderLoki extends LoggerAppender
{
    protected string $url = '';
    protected string $username = '';
    protected string $token = '';
    protected string $serviceName = 'json_logs';
    protected string $job = 'json_logs';

    public function setUrl(string $url): void
    {
        $this->setString('url', $url);
    }

    public function setUsername(string $username): void
    {
        $this->setString('username', $username);
    }

    public function setToken(string $token): void
    {
        $this->setString('token', $token);
    }

    public function setServiceName(string $serviceName): void
    {
        $this->setString('serviceName', $serviceName);
    }

    public function setJob(string $job): void
    {
        $this->setString('job', $job);
    }

    public function activateOptions(): void
    {
        parent::activateOptions();

        if (empty($this->url)) {
            $this->warn('Loki appender requires "url" parameter. Appender will be disabled.');
            $this->closed = true;
            return;
        }

        if (empty($this->username)) {
            $this->warn('Loki appender requires "username" parameter. Appender will be disabled.');
            $this->closed = true;
            return;
        }

        if (empty($this->token)) {
            $this->warn('Loki appender requires "token" parameter. Appender will be disabled.');
            $this->closed = true;
            return;
        }
    }

    public function append(LoggerLoggingEvent $event): void
    {
        $payload = [
            'streams' => [[
                'stream' => [
                    'level'        => $event->getLevel()->toString(),
                    'service_name' => $this->serviceName,
                    'job'          => $this->job
                ],
                'values' => [
                    [(string)(int)(microtime(true) * 1e9), $this->layout?->format($event)]
                ]
            ]]
        ];

        $cUrlHandler = curl_init($this->url);

        if ($cUrlHandler === false) {
            $this->warn('Failed to initialize cURL handler.');
            return;
        }

        curl_setopt_array($cUrlHandler, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => json_encode($payload),
            CURLOPT_HTTPHEADER      => ['Content-Type: application/json'],
            CURLOPT_USERPWD         => "{$this->username}:{$this->token}",
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 5,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS
        ]);

        $result = curl_exec($cUrlHandler);

        if ($result === false) {
            $error = curl_error($cUrlHandler);
            $this->warn("Failed to send logs to Loki: {$error}");
        }
    }
}
