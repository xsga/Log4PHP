# Log4PHP - Complete Documentation

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Log Messages](#log-messages)
- [Appenders](#appenders)
- [Layouts](#layouts)
- [Development Environment](#development-environment)

---

## Installation

### Requirements

Log4PHP requires the following to work correctly:

| Requirement | Version |
|-------------|---------|
| **PHP**     | 8.4 or higher |
| **Composer** | Any current version |

### How to Install

To install Log4PHP in your project, use Composer:

```bash
composer require xsga/log4php
```

Once installed, the library will be available automatically through Composer's autoload.

### Quick Start

Here is a basic example of how to use Log4PHP:

```php
use Xsga\Log4Php\Logger;

// Load configuration from an XML file
Logger::configure('path/to/log4php.xml');

// Get a named logger
$logger = Logger::getLogger('MyApp');

// Log events
$logger->info('Application started.');
$logger->warning('Disk space running low.', ['threshold' => '90%']);
$logger->error('Unhandled exception.', ['exception' => $e->getMessage()]);

// Use the root logger
$root = Logger::getRootLogger();
$root->debug('Root logger is ready.');
```

---

## Configuration

### Configuration Overview

Log4PHP uses a flexible configuration system that allows you to define:

- **Appenders**: Destinations where logs will be written (files, console, Loki, etc.)
- **Layouts**: Log presentation formats (free text, JSON, simple)
- **Loggers**: Named loggers that inherit configuration in a hierarchy
- **Levels**: Minimum severity of events to log (DEBUG, INFO, WARNING, etc.)

Configuration can be done in two ways:

1. **Using an XML file**: The recommended way for production.
2. **Using a PHP array**: Useful for development and programmatic configuration.

### Example Configuration with XML File

The XML file is the standard way to configure Log4PHP. The root element `<configuration>` defines the global structure.

**Basic structure:**

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<configuration xmlns="http://logging.apache.org/log4php/" threshold="all">

  <!-- Daily appender: rotates logs each day -->
  <appender name="daily" class="LoggerAppenderDailyFile" threshold="all">
    <param name="file" value="logs/app_%s.log" />
    <param name="datePattern" value="Ymd" />
    <layout class="LoggerLayoutPattern">
      <param name="conversionPattern" value="%date{Y-m-d H:i:s} [%requestid] [%-5level] [%-40class{0}] [%4line] - %msg%n" />
    </layout>
  </appender>

  <!-- JSON appender: for ingestion pipelines -->
  <appender name="json" class="LoggerAppenderRollingFile" threshold="all">
    <param name="file" value="logs/app.json.log" />
    <param name="maxFileSize" value="10MB" />
    <param name="maxBackupIndex" value="20" />
    <layout class="LoggerLayoutJson">
      <param name="prettyPrint" value="false" />
    </layout>
  </appender>

  <!-- Root logger: inherited by all other loggers -->
  <root>
    <level value="debug" />
    <appender_ref ref="daily" />
    <appender_ref ref="json" />
  </root>

</configuration>
```

**Main elements:**

- `<configuration>`: Root element (accepts global `threshold` attribute)
- `<appender>`: Defines a log destination
- `<layout>`: Defines the message format within each appender
- `<root>`: Configuration for the root logger
- `<appender_ref>`: References to appenders from loggers

To load this configuration in your code:

```php
use Xsga\Log4Php\Logger;

Logger::configure('path/to/log4php.xml');
```

### Example Configuration with PHP Array

Alternatively, you can pass configuration as a PHP array directly:

```php
use Xsga\Log4Php\Logger;

Logger::configure([
    'appenders' => [
        'default' => [
            'class'  => 'LoggerAppenderFile',
            'params' => ['file' => 'logs/app.log'],
            'layout' => ['class' => 'LoggerLayoutPattern'],
        ],
    ],
    'rootLogger' => [
        'level'     => 'debug',
        'appenders' => ['default'],
    ],
]);
```

**Configuration structure:**

- `appenders`: Dictionary of appenders, where each key is the appender name
  - `class`: Class of the appender to use
  - `params`: Array of appender-specific parameters
  - `layout`: Layout configuration (class and parameters)
- `rootLogger`: Configuration for the root logger
  - `level`: Minimum log level to record
  - `appenders`: Array of appender names to use

You can also reset the entire configuration at runtime:

```php
Logger::resetConfiguration();
```

---

## Log Messages

### Log Levels

Log levels in Log4PHP follow the PSR-3 specification and are ordered by severity, from lowest to highest. Each level has an associated constant and numeric value:

| Level       | Constant                       | Value | Description |
|-------------|--------------------------------|:-----:|-------------|
| `ALL`       | `LoggerLevel::getLevelAll()`   | 0     | Records all events |
| `TRACE`     | `LoggerLevel::getLevelTrace()` | 1     | Detailed tracing information for debugging |
| `DEBUG`     | `LoggerLevel::getLevelDebug()` | 2     | Debugging information |
| `INFO`      | `LoggerLevel::getLevelInfo()`  | 3     | General information (operation completed) |
| `NOTICE`    | `LoggerLevel::getLevelNotice()` | 4    | Significant normal events |
| `WARNING`   | `LoggerLevel::getLevelWarning()` | 5   | Warnings (something unexpected) |
| `ERROR`     | `LoggerLevel::getLevelError()` | 6     | Errors (operation failed) |
| `CRITICAL`  | `LoggerLevel::getLevelCritical()` | 7 | Critical conditions |
| `ALERT`     | `LoggerLevel::getLevelAlert()` | 8     | Alert (immediate action required) |
| `EMERGENCY` | `LoggerLevel::getLevelEmergency()` | 9 | Emergency (system unusable) |
| `OFF`       | `LoggerLevel::getLevelOff()`   | 10    | Records nothing |

When you set a level for a logger, all events at that level and above are recorded. For example, if you set the level to `INFO`, then `INFO`, `NOTICE`, `WARNING`, `ERROR`, `CRITICAL`, `ALERT`, and `EMERGENCY` are recorded, but not `TRACE` or `DEBUG`.

### Context Information

Context is additional information that accompanies each log event. Log4PHP supports context in all PSR-3 logging operations, allowing you to pass an associative array as the second parameter.

**How it's used in Log4PHP:**

```php
$logger = Logger::getLogger('MyApp');

$logger->info('User authenticated.', [
    'event'      => 'user_login',
    'user_id'    => 123,
    'user_email' => 'alice@example.com',
    'ip'         => '192.168.1.10',
]);
```

**Behavior by layout:**

- **In `LoggerLayoutPattern`**: Context is interpolated into the message using `{key}` placeholders per PSR-3. A message like `'User {user_id} logged in'` with context `['user_id' => 42]` renders as `'User 42 logged in'`.

- **In `LoggerLayoutJson`**: Context is included as a `context` property in the JSON output. Additionally, certain known keys are automatically promoted to top-level fields:
  - `event`, `user_id`, `user_email`, `ip`, `action`, `resource`, `error_code`

**Example JSON output:**

```json
{
  "timestamp": "2026-04-18T10:23:45.123Z",
  "level": "INFO",
  "message": "User logged in",
  "request_id": "abc-123",
  "event": "user_login",
  "user_id": 42,
  "location": {
    "class": "App\\Controller",
    "method": "login",
    "file": "/app/Controller.php",
    "line": "58"
  },
  "context": {
    "event": "user_login",
    "user_id": 42
  }
}
```

### Logger Hierarchy

Log4PHP organizes loggers into a hierarchy based on dot-separated names. This structure allows you to organize code logically and share configuration among related loggers.

**Main features:**

- **Appender inheritance**: A child logger inherits appenders from its parent logger, unless additivity is disabled.
- **Root logger**: The ancestor of all loggers. Forms the base of the hierarchy.
- **Hierarchical names**: `MyApp.Http.Request` is a child of `MyApp.Http`, which is a child of `MyApp`.

**Hierarchy example:**

```php
use Xsga\Log4Php\Logger;

// Root logger
$root = Logger::getRootLogger();

// Named logger
$app = Logger::getLogger('MyApp');

// Child logger - inherits from MyApp
$http = Logger::getLogger('MyApp.Http');

// Grandchild logger - inherits from MyApp.Http
$request = Logger::getLogger('MyApp.Http.Request');
```

In the hierarchy above:
- `$root` is the ancestor of all.
- `$app` inherits appenders from `$root` (if it doesn't have its own).
- `$http` inherits from `$app` and, indirectly, from `$root`.
- `$request` inherits from `$http`, `$app`, and `$root`.

This is useful for structuring logs from different components without needing to reconfigure each logger individually.

---

## Appenders

### General Description

An **appender** is the component responsible for determining **where** log events are written. Log4PHP provides several built-in appenders, each specialized for a different destination (files, console, Loki, etc.).

Each appender must have an associated **layout** that defines the message format.

### Appenders Summary Table

| Appender | Class | Purpose |
|----------|-------|---------|
| **File** | `LoggerAppenderFile` | Writes logs to a simple file |
| **Daily File** | `LoggerAppenderDailyFile` | Automatically rotates logs each day |
| **Rolling File** | `LoggerAppenderRollingFile` | Rotates logs by size, with optional compression |
| **Console** | `LoggerAppenderConsole` | Writes to standard outputs (stdout/stderr) |
| **Loki** | `LoggerAppenderLoki` | Sends logs to Grafana Cloud Loki |

---

### LoggerAppenderFile

#### Description

Writes log events to a plain text file. It's the most basic and straightforward appender. Directories are created automatically if they don't exist.

#### Parameters Table

| Parameter | Type    | Default | Description |
|-----------|---------|---------|-------------|
| `file`    | string  | —       | Path to the log file. Created automatically if it doesn't exist. |
| `append`  | bool    | `true`  | If `true`, append to existing file. If `false`, overwrite it. |
| `locking` | bool    | `true`  | Uses exclusive file locking on each write to prevent conflicts. |

#### XML Configuration Example

```xml
<appender name="file" class="LoggerAppenderFile">
  <param name="file" value="logs/app.log" />
  <param name="append" value="true" />
  <param name="locking" value="true" />
  <layout class="LoggerLayoutPattern">
    <param name="conversionPattern" value="%date{Y-m-d H:i:s} [%-5level] %message%newline" />
  </layout>
</appender>
```

#### PHP Array Configuration Example

```php
'appenders' => [
    'file' => [
        'class'  => 'LoggerAppenderFile',
        'params' => [
            'file'     => 'logs/app.log',
            'append'   => true,
            'locking'  => true,
        ],
        'layout' => [
            'class'  => 'LoggerLayoutPattern',
            'params' => [
                'conversionPattern' => '%date{Y-m-d H:i:s} [%-5level] %message%newline',
            ],
        ],
    ],
]
```

---

### LoggerAppenderDailyFile

#### Description

Extends `LoggerAppenderFile` and provides automatic log rotation each day. The filename can include a `%s` placeholder that is replaced with the current date according to the specified pattern.

Useful for keeping logs organized by day without needing manual rotation.

#### Parameters Table

| Parameter      | Type   | Default | Description |
|----------------|--------|---------|-------------|
| `file`         | string | —       | File path with `%s` placeholder for date (e.g., `logs/app_%s.log`). |
| `datePattern`  | string | `Ymd`   | PHP `date()` format for the date segment (e.g., `Y-m-d` for `2026-04-18`). |
| `append`       | bool   | `true`  | If `true`, append to the existing file for that day. |
| `locking`      | bool   | `true`  | Uses exclusive file locking on each write. |

#### XML Configuration Example

```xml
<appender name="daily" class="LoggerAppenderDailyFile">
  <param name="file" value="logs/app_%s.log" />
  <param name="datePattern" value="Y-m-d" />
  <param name="append" value="true" />
  <layout class="LoggerLayoutPattern">
    <param name="conversionPattern" value="%date{Y-m-d H:i:s} [%-5level] [%logger] %message%newline" />
  </layout>
</appender>
```

**Result:** Generates files like `logs/app_2026-04-18.log`, `logs/app_2026-04-19.log`, etc.

#### PHP Array Configuration Example

```php
'appenders' => [
    'daily' => [
        'class'  => 'LoggerAppenderDailyFile',
        'params' => [
            'file'        => 'logs/app_%s.log',
            'datePattern' => 'Y-m-d',
            'append'      => true,
            'locking'     => true,
        ],
        'layout' => [
            'class'  => 'LoggerLayoutPattern',
            'params' => [
                'conversionPattern' => '%date{Y-m-d H:i:s} [%-5level] [%logger] %message%newline',
            ],
        ],
    ],
]
```

---

### LoggerAppenderRollingFile

#### Description

Extends `LoggerAppenderFile` and provides automatic rotation when the file reaches a maximum size. Maintains a configurable number of backup files, optionally compressed with gzip.

Ideal for long-running applications that generate many logs and need automatic disk space management.

#### Parameters Table

| Parameter         | Type   | Default  | Description |
|-------------------|--------|----------|-------------|
| `file`            | string | —        | Path to the active log file. |
| `maxFileSize`     | string | `10MB`   | Maximum size before rotation (e.g., `5MB`, `1GB`, `512KB`). |
| `maxBackupIndex`  | int    | `1`      | Number of backup files to keep. |
| `compress`        | bool   | `false`  | If `true`, compresses backups as `.gz`. |
| `append`          | bool   | `true`   | If `true`, append to the existing file. |
| `locking`         | bool   | `true`   | Uses exclusive file locking on each write. |

#### XML Configuration Example

```xml
<appender name="rolling" class="LoggerAppenderRollingFile">
  <param name="file" value="logs/app.log" />
  <param name="maxFileSize" value="10MB" />
  <param name="maxBackupIndex" value="10" />
  <param name="compress" value="true" />
  <layout class="LoggerLayoutPattern">
    <param name="conversionPattern" value="%date{Y-m-d H:i:s} [%-5level] %message%newline" />
  </layout>
</appender>
```

**Result:** When `logs/app.log` reaches 10MB, it's renamed to `logs/app.log.1.gz`, the current becomes `logs/app.log.2.gz`, etc. Maximum 10 backups are kept.

#### PHP Array Configuration Example

```php
'appenders' => [
    'rolling' => [
        'class'  => 'LoggerAppenderRollingFile',
        'params' => [
            'file'             => 'logs/app.log',
            'maxFileSize'      => '10MB',
            'maxBackupIndex'   => 10,
            'compress'         => true,
            'append'           => true,
            'locking'          => true,
        ],
        'layout' => [
            'class'  => 'LoggerLayoutPattern',
            'params' => [
                'conversionPattern' => '%date{Y-m-d H:i:s} [%-5level] %message%newline',
            ],
        ],
    ],
]
```

---

### LoggerAppenderConsole

#### Description

Writes log events to system standard outputs (stdout, stderr) or PHP's error log (`error_log`). Useful for development and debugging.

#### Parameters Table

| Parameter | Type   | Default  | Description |
|-----------|--------|----------|-------------|
| `target`  | string | `stdout` | Target: `stdout` (standard output), `stderr` (error output), or `php` (PHP error_log). |

#### XML Configuration Example

```xml
<appender name="console" class="LoggerAppenderConsole">
  <param name="target" value="stdout" />
  <layout class="LoggerLayoutPattern">
    <param name="conversionPattern" value="[%-5level] %message%newline" />
  </layout>
</appender>
```

#### PHP Array Configuration Example

```php
'appenders' => [
    'console' => [
        'class'  => 'LoggerAppenderConsole',
        'params' => [
            'target' => 'stdout',
        ],
        'layout' => [
            'class'  => 'LoggerLayoutPattern',
            'params' => [
                'conversionPattern' => '[%-5level] %message%newline',
            ],
        ],
    ],
]
```

---

### LoggerAppenderLoki

#### Description

Sends log events to **Grafana Cloud Loki** via HTTP. This appender authenticates with credentials and sends logs in JSON format compatible with the Loki API.

Excellent for centralizing logs in the cloud and using Grafana's analysis tools.

#### Parameters Table

| Parameter      | Type   | Required | Default    | Description |
|----------------|--------|----------|------------|-------------|
| `url`          | string | Yes      | —          | Loki endpoint URL |
| `username`     | string | Yes      | —          | Grafana Cloud user ID |
| `token`        | string | Yes      | —          | Grafana Cloud API token |
| `serviceName`  | string | No       | `json_logs` | Label `service_name` in Loki |
| `job`          | string | No       | `json_logs` | Label `job` in Loki |

#### XML Configuration Example (Secure Production)

For production, use environment variables with `${VARIABLE}` syntax:

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

Define environment variables in your system:

```bash
export GRAFANA_CLOUD_LOKI_URL="grafana_cloud_loki_url"
export GRAFANA_CLOUD_LOKI_USERNAME="username"
export GRAFANA_CLOUD_LOKI_TOKEN="token"
```

#### XML Configuration Example (Local Development)

For local environments you can include values directly (not recommended in production):

```xml
<appender name="loki" class="LoggerAppenderLoki">
  <param name="url" value="grafana_cloud_loki_url" />
  <param name="username" value="local" />
  <param name="token" value="test-token" />
  <param name="serviceName" value="my-app-dev" />
  <param name="job" value="local" />
  <layout class="LoggerLayoutJson">
    <param name="prettyPrint" value="false" />
  </layout>
</appender>
```

#### PHP Array Configuration Example

```php
'appenders' => [
    'loki' => [
        'class'  => 'LoggerAppenderLoki',
        'params' => [
            'url'         => getenv('GRAFANA_CLOUD_LOKI_URL'),
            'username'    => getenv('GRAFANA_CLOUD_LOKI_USERNAME'),
            'token'       => getenv('GRAFANA_CLOUD_LOKI_TOKEN'),
            'serviceName' => 'my-application',
            'job'         => 'backend',
        ],
        'layout' => [
            'class'  => 'LoggerLayoutJson',
            'params' => [
                'prettyPrint' => false,
            ],
        ],
    ],
]
```

#### Labels in Loki

Each log event sent to Loki includes the following labels:

- `level`: Log level (DEBUG, INFO, WARNING, ERROR, etc.)
- `service_name`: Configurable via the `serviceName` parameter
- `job`: Configurable via the `job` parameter

---

## Layouts

### General Description

A **layout** defines the **format** in which log events are rendered. Log4PHP provides several layouts for different needs:

- **LoggerLayoutPattern**: Free-text formats with configurable tokens.
- **LoggerLayoutJson**: Structured logs in JSON.
- **LoggerLayoutSimple**: Simple plain text format.

Each appender must have an associated layout.

### Layouts Summary Table

| Layout | Class | Purpose |
|--------|-------|---------|
| **Pattern** | `LoggerLayoutPattern` | Free text with configurable tokens |
| **JSON** | `LoggerLayoutJson` | Structured logs in JSON |
| **Simple** | `LoggerLayoutSimple` | Simple plain text |

---

### LoggerLayoutPattern

#### Description

Formats log events using a configurable pattern string composed of conversion characters (tokens). It's the most flexible and widely used layout.

A pattern is a text string where special tokens like `%level`, `%date`, `%message` are replaced with actual event values.

#### Parameters Table

| Parameter            | Type   | Default value |
|----------------------|--------|------------------|
| `conversionPattern`  | string | `%date %-5level %logger %message%newline` |

#### XML Configuration Example

```xml
<layout class="LoggerLayoutPattern">
  <param name="conversionPattern" value="%date{Y-m-d H:i:s} [%-5level] %logger - %message%newline" />
</layout>
```

**Result:**
```
2026-04-18 10:23:45 [INFO  ] MyApp - User logged in
2026-04-18 10:23:46 [ERROR ] MyApp.Http - Database connection failed
```

#### PHP Array Configuration Example

```php
'layout' => [
    'class'  => 'LoggerLayoutPattern',
    'params' => [
        'conversionPattern' => '%date{Y-m-d H:i:s} [%-5level] %logger - %message%newline',
    ],
]
```

#### Conversion Characters Table

The following tokens are available in `LoggerLayoutPattern`:

| Token(s) | Description |
|----------|-------------|
| `%c`, `%lo`, `%logger` | Name of the logger that generated the event. Accepts precision `{n}` (segment count from right): `%logger{0}` or `%logger{1}` → last segment, `%logger{2}` → last two segments. |
| `%C`, `%class` | Name of the class that called the logger. Accepts precision `{n}` (namespace segment count from right): `%class{0}` or `%class{1}` → simple class name, `%class{2}` → last two segments. |
| `%d`, `%date` | Timestamp of the event. Accepts a date format in `{...}` (PHP `date()` syntax), e.g. `%date{Y-m-d H:i:s}`. Also supports aliases: `{ISO8601}`, `{ABSOLUTE}`, `{DATE}`. |
| `%e`, `%env` | Environment variable (`$_ENV`). With `%env{KEY}` returns `$_ENV['KEY']` (or `empty` when not present/empty). |
| `%F`, `%file` | Name of the source file |
| `%l`, `%location` | Full location (class, method, file, line) |
| `%L`, `%line` | Line number |
| `%m`, `%msg`, `%message` | Log message |
| `%M`, `%method` | Name of the method that called the logger |
| `%n`, `%newline` | System line separator |
| `%p`, `%le`, `%level` | Log level |
| `%req`, `%request` | Request data (`$_REQUEST`). With `%request{KEY}` returns `$_REQUEST['KEY']` (or `empty` when not present/empty). |
| `%rid`, `%requestid` | `REQUEST_ID` from `$_ENV` |
| `%s`, `%server` | Server data (`$_SERVER`). With `%server{KEY}` returns `$_SERVER['KEY']` (or `empty` when not present/empty). |
| `%ses`, `%session` | Session data (`$_SESSION`). With `%session{KEY}` returns `$_SESSION['KEY']` (or `empty` when not present/empty). |
| `%sid`, `%sessionid` | Session ID |
| `%t`, `%pid`, `%process` | Current process PID |

Notes:
- For converters without option support (for example `%level`, `%message`, `%line`), any `{...}` value is ignored.
- `%class{n}` and `%logger{n}` use segment precision, not character-length truncation.

#### Padding and Truncation

The format follows the standard Log4j syntax:

- `%-5level`: Left-aligned, minimum width 5
- `%.20message`: Maximum 20 characters
- `%40class{0}`: Right-aligned, minimum width 40, only the simple class name (no namespace)

**Example:**
```
%-40class{0}  →  "LoggerAppenderFile" (40 characters, right-aligned)
%.20message   →  "User logged in succe" (maximum 20 characters)
```

---

### LoggerLayoutJson

#### Description

Formats log events as a JSON object on a single line, suitable for structured log ingestion pipelines (Elasticsearch, Loki, Datadog, etc.).

Each event automatically includes: timestamp, level, message, location information, and context. Certain context keys are promoted to top-level fields to facilitate filtering.

#### Parameters Table

| Parameter     | Type | Default | Description |
|---------------|------|---------|-------------|
| `prettyPrint` | bool | `false` | If `true`, formats JSON with indentation (useful for debugging) |

#### XML Configuration Example

```xml
<layout class="LoggerLayoutJson">
  <param name="prettyPrint" value="false" />
</layout>
```

#### PHP Array Configuration Example

```php
'layout' => [
    'class'  => 'LoggerLayoutJson',
    'params' => [
        'prettyPrint' => false,
    ],
]
```

#### Output Example

**Normal output (single line):**
```json
{"timestamp":"2026-04-18T10:23:45.123Z","level":"INFO","message":"User logged in","request_id":"abc-123","event":"user_login","user_id":42,"location":{"class":"App\\Controller","method":"login","file":"/app/Controller.php","line":"58"},"context":{"event":"user_login","user_id":42}}
```

**Output with prettyPrint (debugging):**
```json
{
  "timestamp": "2026-04-18T10:23:45.123Z",
  "level": "INFO",
  "message": "User logged in",
  "request_id": "abc-123",
  "event": "user_login",
  "user_id": 42,
  "location": {
    "class": "App\\Controller",
    "method": "login",
    "file": "/app/Controller.php",
    "line": "58"
  },
  "context": {
    "event": "user_login",
    "user_id": 42
  }
}
```

#### Automatic Promotion of Context Keys

The following context keys are automatically promoted to top-level JSON fields:

- `event`
- `user_id`
- `user_email`
- `ip`
- `action`
- `resource`
- `error_code`

The complete `context` array is always included as well, allowing access to all values.

---

### LoggerLayoutSimple

#### Description

Formats log events as plain text with the log level and rendered message, one event per line. It's the most minimalist layout.

#### Parameters

This layout has no configurable parameters.

#### XML Configuration Example

```xml
<layout class="LoggerLayoutSimple" />
```

#### PHP Array Configuration Example

```php
'layout' => [
    'class' => 'LoggerLayoutSimple',
]
```

#### Output Format

```
INFO - User logged in
ERROR - Database connection failed
WARNING - Disk space running low
```

---

## Development Environment

### General Description

Log4PHP includes several development tools to ensure code quality. These tools are configured in `composer.json` and are invoked via Composer commands.

### Available Tools

#### 1. Linting (Syntax Validation)

**Tool:** PHP Parallel Lint

**Description:** Validates PHP syntax of all project files, detecting syntax errors without executing the code.

**What it does:** Find syntax errors quickly, especially after large changes.

**How to use:**
```bash
composer lint
```

**Example of successful output:**
```
Parallel lint of PHP files
  PHP 8.4.0
1 file checked successfully
```

---

#### 2. Style Validation (PSR-12)

**Tool:** PHP CodeSniffer (PHPCS)

**Description:** Verifies that the code complies with the PSR-12 coding standard (PHP style guide).

**What it does:** Ensure consistency in code, detect formatting issues and conventions.

**How to use:**
```bash
composer style
```

**Example output:**
```
FILE: src/Logger.php
 LINE 42  ERROR  [PSR12.Files.FileHeader] File header comment missing

Found 1 error and 0 warnings
```

---

#### 3. Automatic Style Correction

**Tool:** PHP Code Beautifier and Fixer (PHPCBF)

**Description:** Automatically fixes many style issues detected by PHPCS.

**What it does:** Save time fixing formatting issues automatically.

**How to use:**
```bash
composer style-fix
```

**Note:** Not all issues can be fixed automatically. Some require manual intervention.

---

#### 4. Static Analysis (Errors)

**Tool:** Psalm

**Description:** Performs advanced static analysis to detect errors, incorrect types, and logical problems without executing code.

**What it does:** Find potential bugs before they reach production, validate types.

**How to use:**
```bash
composer analyze-errors
```

**Example output:**
```
src/Logger.php:18:16 - error: Undefined variable: $appender
src/Logger.php:42:8 - error: Cannot access property of non-object

2 errors found
```

---

#### 5. Static Analysis (Complete Information)

**Tool:** Psalm (informative mode)

**Description:** More comprehensive static analysis that includes warnings and additional information along with errors.

**What it does:** Get more comprehensive code analysis, detect less critical issues.

**How to use:**
```bash
composer analyze-info
```

**Example output:**
```
src/Logger.php:18:16 - error: Undefined variable: $appender
src/Logger.php:42:8 - error: Cannot access property of non-object
src/Logger.php:100:5 - info: Unused variable: $temp

3 issues found
```

---

### Recommended Workflow

To develop safely:

1. **Write or modify code**
2. **Validate syntax:**
   ```bash
   composer lint
   ```
3. **Analyze potential errors:**
   ```bash
   composer analyze-errors
   ```
4. **Automatically fix styles:**
   ```bash
   composer style-fix
   ```
5. **Verify styles comply:**
   ```bash
   composer style
   ```
6. **(Optional) Deeper analysis:**
   ```bash
   composer analyze-info
   ```
