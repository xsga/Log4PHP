# Log4PHP

[![PHP](https://img.shields.io/badge/PHP-8.4%2B-blue?logo=php)](https://www.php.net/)
[![PSR-3](https://img.shields.io/badge/PSR--3-compliant-brightgreen)](https://www.php-fig.org/psr/psr-3/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A modern, PSR-3 compliant PHP logging library inspired by Apache Log4PHP, rebuilt from the ground up for **PHP 8.4**. It provides a flexible, XML-driven logging pipeline with support for multiple appenders, layouts, and a full set of log levels aligned with the PSR-3 standard.

If you are migrating from or comparing against the original Apache Log4PHP library, see [Differences from Apache Log4PHP](doc/differences-from-apache-log4php.md).

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
  - [XML Configuration File](#xml-configuration-file)
  - [Programmatic Configuration](#programmatic-configuration)
- [Log Levels](#log-levels)
- [Appenders](#appenders)
  - [LoggerAppenderFile](#loggerappenderfile)
  - [LoggerAppenderDailyFile](#loggerappenderdailyfile)
  - [LoggerAppenderRollingFile](#loggerappenderrollingfile)
  - [LoggerAppenderConsole](#loggerappenderconsole)
  - [LoggerAppenderLoki](#loggerappenderloki)
- [Layouts](#layouts)
  - [LoggerLayoutPattern](#loggerlayoutpattern)
  - [LoggerLayoutJson](#loggerlayoutjson)
- [Pattern Conversion Characters](#pattern-conversion-characters)
- [Context Support](#context-support)
- [Logger Hierarchy](#logger-hierarchy)
- [Development](#development)
- [Differences from Apache Log4PHP](doc/differences-from-apache-log4php.md)
- [License](#license)

---

## Requirements

- PHP **8.4** or higher
- Composer

---

## Installation

```bash
composer require xsga/log4php
```

---

## Quick Start

```php
use Xsga\Log4Php\Logger;

// Load configuration from an XML file
Logger::configure('path/to/log4php.xml');

// Obtain a named logger
$logger = Logger::getLogger('MyApp');

$logger->info('Application started.');
$logger->warning('Disk space running low.', ['threshold' => '90%']);
$logger->error('Unhandled exception.', ['exception' => $e->getMessage()]);

// Or use the root logger
$root = Logger::getRootLogger();
$root->debug('Root logger is ready.');
```

---

## Configuration

### XML Configuration File

Log4PHP is configured through an XML file. The root element accepts a global `threshold` attribute that filters all appenders at once.

Each appender should define a `<layout ...>` block explicitly. Appenders no longer assume an implicit default layout.

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<configuration xmlns="http://logging.apache.org/log4php/" threshold="all">

  <!-- Pattern layout: one line per event -->
  <appender name="daily" class="LoggerAppenderDailyFile" threshold="all">
    <param name="file" value="logs/app_%s.log" />
    <param name="datePattern" value="Ymd" />
    <layout class="LoggerLayoutPattern">
      <param name="conversionPattern" value="%date{Y-m-d H:i:s} [%requestid] [%-5level] [%-40class{0}] [%4line] - %msg%n" />
    </layout>
  </appender>

  <!-- JSON layout: structured log for ingestion pipelines -->
  <appender name="json" class="LoggerAppenderRollingFile" threshold="all">
    <param name="file" value="logs/app.json.log" />
    <param name="maxFileSize" value="10MB" />
    <param name="maxBackupIndex" value="20" />
    <layout class="LoggerLayoutJson">
      <param name="prettyPrint" value="false" />
    </layout>
  </appender>

  <root>
    <level value="debug" />
    <appender_ref ref="daily" />
    <appender_ref ref="json" />
  </root>

</configuration>
```

### Programmatic Configuration

You can also pass a configuration array directly:

```php
Logger::configure([
    'appenders' => [
        'default' => [
            'class'  => 'LoggerAppenderFile',
            'params' => ['file' => 'logs/app.log'],
            'layout' => ['class' => 'LoggerLayoutPattern'],
        ],
    ],
    'rootLogger' => [
        'level'    => 'debug',
        'appenders' => ['default'],
    ],
]);
```

To reset all loggers and appenders at runtime:

```php
Logger::resetConfiguration();
```

---

## Log Levels

Log levels follow the PSR-3 specification, ordered from lowest to highest severity:

| Level       | Constant                     | Integer value |
|-------------|------------------------------|:---:|
| `ALL`       | `LoggerLevel::getLevelAll()`  | 0  |
| `TRACE`     | `LoggerLevel::getLevelTrace()`| 1  |
| `DEBUG`     | `LoggerLevel::getLevelDebug()`| 2  |
| `INFO`      | `LoggerLevel::getLevelInfo()` | 3  |
| `NOTICE`    | `LoggerLevel::getLevelNotice()`| 4 |
| `WARNING`   | `LoggerLevel::getLevelWarning()`| 5 |
| `ERROR`     | `LoggerLevel::getLevelError()`| 6  |
| `CRITICAL`  | `LoggerLevel::getLevelCritical()`| 7 |
| `ALERT`     | `LoggerLevel::getLevelAlert()`| 8  |
| `EMERGENCY` | `LoggerLevel::getLevelEmergency()`| 9 |
| `OFF`       | `LoggerLevel::getLevelOff()`  | 10 |

---

## Appenders

### LoggerAppenderFile

Writes log events to a plain file.

| Parameter | Type    | Default | Description |
|-----------|---------|---------|-------------|
| `file`    | string  | —       | Path to the log file. Directories are created automatically. |
| `append`  | bool    | `true`  | Append to existing file (`true`) or overwrite (`false`). |
| `locking` | bool    | `true`  | Use exclusive file locking on each write. |

```xml
<appender name="file" class="LoggerAppenderFile">
  <param name="file" value="logs/app.log" />
  <param name="append" value="true" />
  <layout class="LoggerLayoutPattern" />
</appender>
```

### LoggerAppenderDailyFile

Extends `LoggerAppenderFile`. Automatically rotates the log file each day by substituting `%s` in the filename with the current date.

| Parameter     | Type   | Default  | Description |
|---------------|--------|----------|-------------|
| `file`        | string | —        | File path template; use `%s` as the date placeholder. |
| `datePattern` | string | `Ymd`    | PHP `date()` format string for the date segment. |

```xml
<appender name="daily" class="LoggerAppenderDailyFile">
  <param name="file" value="logs/app_%s.log" />
  <param name="datePattern" value="Y-m-d" />
  <layout class="LoggerLayoutPattern" />
</appender>
```

### LoggerAppenderRollingFile

Extends `LoggerAppenderFile`. Rotates the log file when it exceeds a configured size, keeping a configurable number of backup copies. Optionally compresses backups with gzip.

| Parameter        | Type   | Default   | Description |
|------------------|--------|-----------|-------------|
| `file`           | string | —         | Path to the active log file. |
| `maxFileSize`    | string | `10MB`    | Maximum file size before rotation (e.g., `5MB`, `1GB`). |
| `maxBackupIndex` | int    | `1`       | Number of backup files to retain. |
| `compress`       | bool   | `false`   | Compress backups as `.gz` files. |

```xml
<appender name="rolling" class="LoggerAppenderRollingFile">
  <param name="file" value="logs/app.log" />
  <param name="maxFileSize" value="10MB" />
  <param name="maxBackupIndex" value="10" />
  <param name="compress" value="true" />
  <layout class="LoggerLayoutPattern" />
</appender>
```

### LoggerAppenderConsole

Writes log events to console output streams.

| Parameter | Type   | Default   | Description |
|-----------|--------|-----------|-------------|
| `target`  | string | `stdout`  | Output target: `stdout`, `stderr`, or `php` (`error_log`). |

```xml
<appender name="console" class="LoggerAppenderConsole">
  <param name="target" value="stdout" />
  <layout class="LoggerLayoutPattern" />
</appender>
```

### LoggerAppenderLoki

Sends logs to **Grafana Cloud Loki** over HTTP. This appender authenticates using credentials and sends log events in structured JSON format compatible with the Loki API.

| Parameter     | Type   | Required    | Default      | Description |
|---------------|--------|-------------|--------------|-------------|
| `url`         | string | Yes         | —            | Loki endpoint URL (e.g., `https://logs-prod-012.grafana.net/loki/api/v1/push`) |
| `username`    | string | Yes         | —            | Grafana Cloud user ID |
| `token`       | string | Yes         | —            | Grafana Cloud API token |
| `serviceName` | string | No          | `json_logs`  | `service_name` label in Loki |
| `job`         | string | No          | `json_logs`  | `job` label in Loki |

**Recommendation:** Use `LoggerLayoutJson` to generate structured messages that integrate better with Loki.

#### Secure production configuration

Use the `${VARIABLE}` syntax to reference environment variables inside the XML. This keeps credentials out of the repository:

```xml
<appender name="loki" class="LoggerAppenderLoki">
  <param name="url" value="${GRAFANA_CLOUD_LOKI_URL}" />
  <param name="username" value="${GRAFANA_CLOUD_LOKI_USERNAME}" />
  <param name="token" value="${GRAFANA_CLOUD_LOKI_TOKEN}" />
  <param name="serviceName" value="my-application" />
  <param name="job" value="backend" />
  <layout class="LoggerLayoutJson">
    <param name="prettyPrint" value="false" />
  </layout>
</appender>
```

Define environment variables:

```bash
export GRAFANA_CLOUD_LOKI_URL="https://logs-prod-012.grafana.net/loki/api/v1/push"
export GRAFANA_CLOUD_LOKI_USERNAME="123456"
export GRAFANA_CLOUD_LOKI_TOKEN="glc_ey..."
```

#### Development/testing configuration

For local environments you can include values directly in the XML (not recommended for production):

```xml
<appender name="loki" class="LoggerAppenderLoki">
  <param name="url" value="http://localhost:3100/loki/api/v1/push" />
  <param name="username" value="local" />
  <param name="token" value="test-token" />
  <layout class="LoggerLayoutJson" />
</appender>
```

Each log event is sent to Loki with the following labels:

- `level`: Log level (DEBUG, INFO, WARNING, etc.)
- `service_name`: Configurable via the `serviceName` parameter
- `job`: Configurable via the `job` parameter

---

## Layouts

### LoggerLayoutPattern

Formats log events using a configurable pattern string composed of conversion characters.

| Parameter           | Default value |
|---------------------|---------------|
| `conversionPattern` | `%date %-5level %logger %message%newline` |

```xml
<layout class="LoggerLayoutPattern">
  <param name="conversionPattern" value="%date{Y-m-d H:i:s} [%-5level] %message%newline" />
</layout>
```

### LoggerLayoutJson

Formats log events as a single-line JSON object, suitable for structured logging pipelines (e.g., Elasticsearch, Loki, Datadog).

| Parameter    | Type | Default | Description |
|--------------|------|---------|-------------|
| `prettyPrint`| bool | `false` | Pretty-print the JSON output (useful for debugging). |

**Output example:**

```json
{"timestamp":"2026-04-18T10:23:45.123Z","level":"INFO","message":"User logged in","request_id":"abc-123","event":"user_login","user_id":42,"location":{"class":"App\\Controller","method":"login","file":"/app/Controller.php","line":"58"},"context":{"event":"user_login","user_id":42}}
```

The following context keys are automatically **promoted to top-level fields**: `event`, `user_id`, `user_email`, `ip`, `action`, `resource`, `error_code`. The full `context` array is always included as a separate field.

```xml
<layout class="LoggerLayoutJson">
  <param name="prettyPrint" value="false" />
</layout>
```

---

## Pattern Conversion Characters

The following tokens are available in `LoggerLayoutPattern`:

| Token(s)                        | Description |
|---------------------------------|-------------|
| `%c`, `%lo`, `%logger`          | Logger name |
| `%C`, `%class`                  | Calling class name |
| `%d`, `%date`                   | Event timestamp. Accepts a `date()` format: `%date{Y-m-d H:i:s}` |
| `%e`, `%env`                    | Environment variable (`$_ENV`) |
| `%F`, `%file`                   | Source file name |
| `%l`, `%location`               | Full location (class, method, file, line) |
| `%L`, `%line`                   | Line number |
| `%m`, `%msg`, `%message`        | Log message |
| `%M`, `%method`                 | Calling method name |
| `%n`, `%newline`                | Platform line separator |
| `%p`, `%le`, `%level`           | Log level |
| `%req`, `%request`              | `$_REQUEST` variable |
| `%rid`, `%requestid`            | `REQUEST_ID` from `$_ENV` |
| `%s`, `%server`                 | `$_SERVER` variable |
| `%ses`, `%session`              | `$_SESSION` variable |
| `%sid`, `%sessionid`            | Session ID |
| `%t`, `%pid`, `%process`        | Current process ID |

**Padding and truncation** follow the standard Log4j format: `%-5level` (left-align, min width 5), `%.20message` (max 20 chars), `%40class{0}` (right-align, min width 40, show only the unqualified class name).

---

## Context Support

All PSR-3 logging methods accept an optional `$context` array as the second argument:

```php
$logger->info('User authenticated.', [
    'event'      => 'user_login',
    'user_id'    => 123,
    'user_email' => 'alice@example.com',
    'ip'         => '192.168.1.10',
]);
```

Context data is:
- Included as-is in `LoggerLayoutJson` (with promotion of well-known keys to top-level fields; the full `context` array is always present in the output).
- Interpolated into the message using PSR-3 `{key}` placeholders before any layout processes it. For example, a message `'User {user_id} logged in'` with `['user_id' => 42]` in context will render as `'User 42 logged in'`.

---

## Logger Hierarchy

Loggers are organized in a dot-separated namespace hierarchy. A child logger inherits its parent's appenders unless `additivity` is disabled.

```php
// Named loggers
$app  = Logger::getLogger('MyApp');
$http = Logger::getLogger('MyApp.Http');  // inherits from MyApp

// Root logger — ancestor of all loggers
$root = Logger::getRootLogger();
```

---

## Development

```bash
# Install dependencies
composer install

# Lint syntax
composer lint

# Check code style (PSR-12)
composer style

# Fix code style automatically
composer style-fix

# Static analysis (errors only)
composer analyze-errors

# Static analysis (including info)
composer analyze-info
```

---

## License

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.

> Inspired by [Apache Log4PHP](https://github.com/apache/logging-log4php), rewritten for modern PHP.  
> For a detailed breakdown of what changed, see [Differences from Apache Log4PHP](doc/differences-from-apache-log4php.md).