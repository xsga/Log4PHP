# Differences from Apache Log4PHP

This document describes the main differences between **xsga/log4php** and the original [Apache Log4PHP](https://github.com/apache/logging-log4php) project, which was archived in December 2020.

---

## Overview

| | Apache Log4PHP | xsga/log4php |
|---|---|---|
| Status | Archived (Dec 2020) | Actively maintained |
| PHP requirement | `>= 5.2.7` | `^8.4` |
| License | Apache-2.0 | MIT |
| PSR-3 compliance | No | Yes (`Psr\Log\LoggerInterface`) |
| Autoloading | Classmap + custom autoloader | PSR-4 (`Xsga\Log4Php\`) |
| Code style standard | None enforced | PSR-12 |
| Static analysis | None | Psalm 6 |

---

## 1. PHP Version and Modern Language Features

The original library targeted PHP 5.2.7 and used no namespaces, relying on a custom `LoggerAutoloader` class. This fork requires **PHP 8.4** and takes full advantage of modern language features:

- Strict types (`declare(strict_types=1)`) in every file.
- Constructor property promotion.
- Union types, nullable types, and `readonly` properties where appropriate.
- PSR-4 namespacing under `Xsga\Log4Php\`.
- No custom autoloader — standard Composer PSR-4 autoloading only.

---

## 2. PSR-3 Compliance

The original `Logger` class had no relationship to any PSR standard. This fork implements `Psr\Log\LoggerInterface` (PSR-3), which means:

- The `Logger` class can be injected anywhere a `LoggerInterface` is expected.
- All PSR-3 methods are available: `emergency()`, `alert()`, `critical()`, `error()`, `warning()`, `notice()`, `info()`, `debug()`, and `log()`.
- In addition to PSR-3, a `trace()` method is available as a library-specific extension.
- The generic method signature is `log(mixed $level, string|Stringable $message, array $context = [])`.
- The `$context` array is a first-class citizen passed through the entire logging pipeline.

---

## 3. Log Levels

The original library used the classic Log4j level set: `TRACE`, `DEBUG`, `INFO`, `WARN`, `ERROR`, `FATAL`. This fork replaces that set with the **PSR-3 / RFC 5424** level set:

| Apache Log4PHP | xsga/log4php  | Integer value |
|----------------|---------------|:---:|
| ALL            | ALL           | 0   |
| TRACE          | TRACE         | 1   |
| DEBUG          | DEBUG         | 2   |
| INFO           | INFO          | 3   |
| *(absent)*     | NOTICE        | 4   |
| WARN           | WARNING       | 5   |
| ERROR          | ERROR         | 6   |
| *(absent)*     | CRITICAL      | 7   |
| *(absent)*     | ALERT         | 8   |
| FATAL          | EMERGENCY     | 9   |
| OFF            | OFF           | 10  |

`WARN` and `FATAL` are removed; `NOTICE`, `CRITICAL`, `ALERT`, and `EMERGENCY` are added.

---

## 4. Appenders

The original library shipped thirteen appenders. This fork keeps a smaller, curated set focused on common modern use cases.

| Appender | Apache Log4PHP | xsga/log4php |
|---|:---:|:---:|
| `LoggerAppenderFile` | ✅ | ✅ |
| `LoggerAppenderDailyFile` | ✅ | ✅ |
| `LoggerAppenderRollingFile` | ✅ | ✅ |
| `LoggerAppenderConsole` | ✅ | ✅ |
| `LoggerAppenderLoki` | ❌ | ✅ *(new)* |
| `LoggerAppenderEcho` | ✅ | ❌ |
| `LoggerAppenderFirePHP` | ✅ | ❌ |
| `LoggerAppenderMail` | ✅ | ❌ |
| `LoggerAppenderMailEvent` | ✅ | ❌ |
| `LoggerAppenderMongoDB` | ✅ | ❌ |
| `LoggerAppenderNull` | ✅ | ❌ |
| `LoggerAppenderPDO` | ✅ | ❌ |
| `LoggerAppenderPhp` | ✅ | ❌ |
| `LoggerAppenderSocket` | ✅ | ❌ |
| `LoggerAppenderSyslog` | ✅ | ❌ |

Implemented appenders in this fork are currently: `File`, `DailyFile`, `RollingFile`, `Console`, and `Loki`.

---

## 5. Layouts

| Layout | Apache Log4PHP | xsga/log4php |
|---|:---:|:---:|
| `LoggerLayoutPattern` | ✅ | ✅ |
| `LoggerLayoutJson` | ❌ | ✅ *(new)* |
| `LoggerLayoutHtml` | ✅ | ❌ |
| `LoggerLayoutXml` | ✅ | ❌ |
| `LoggerLayoutSimple` | ✅ | ❌ |
| `LoggerLayoutSerialized` | ✅ | ❌ |
| `LoggerLayoutTTCC` *(deprecated)* | ✅ | ❌ |

### LoggerLayoutJson (new)

A brand-new layout not present in the original. It produces single-line JSON output suitable for log ingestion pipelines (Elasticsearch, Loki, Datadog, etc.). Features include:

- ISO 8601 timestamp with millisecond precision.
- Automatic promotion of well-known context keys to top-level JSON fields: `event`, `user_id`, `user_email`, `ip`, `action`, `resource`, `error_code`.
- Full location information (class, method, file, line) included in every event.
- Optional pretty-print mode via the `prettyPrint` parameter.
- `REQUEST_ID` injected from `$_ENV['REQUEST_ID']` for request tracing.

---

## 6. Filter System Removed

The original library included a complete filter chain system attached to appenders:

- `LoggerFilter` (abstract base)
- `LoggerFilterDenyAll`
- `LoggerFilterLevelMatch`
- `LoggerFilterLevelRange`
- `LoggerFilterStringMatch`

This entire subsystem has been **removed** in this fork. Filtering is handled at the appender and root logger level via the `threshold` attribute in the XML configuration.

---

## 7. Diagnostic Context Classes Removed

The following utility classes from the original library have been removed:

| Class | Purpose |
|---|---|
| `LoggerMDC` | Mapped Diagnostic Context — thread-local key/value store |
| `LoggerNDC` | Nested Diagnostic Context — thread-local stack |
| `LoggerThrowableInformation` | Wrapper for exception/throwable metadata |

Request-scoped context is now handled through the PSR-3 `$context` array passed directly to each log call, or via `$_ENV['REQUEST_ID']` for request tracing.

---

## 8. Pattern Converters

The `LoggerLayoutPattern` converter set has been updated and extended:

| Converter | Apache Log4PHP | xsga/log4php | Notes |
|---|:---:|:---:|---|
| `%logger` (`%c`, `%lo`) | ✅ | ✅ | |
| `%class` (`%C`) | ✅ | ✅ | |
| `%date` (`%d`) | ✅ | ✅ | |
| `%file` (`%F`) | ✅ | ✅ | |
| `%location` (`%l`) | ✅ | ✅ | |
| `%line` (`%L`) | ✅ | ✅ | |
| `%message` (`%m`, `%msg`) | ✅ | ✅ | |
| `%method` (`%M`) | ✅ | ✅ | |
| `%newline` (`%n`) | ✅ | ✅ | |
| `%level` (`%p`, `%le`) | ✅ | ✅ | |
| `%process` (`%t`, `%pid`) | ✅ | ✅ | |
| `%env` (`%e`) | ✅ | ✅ | |
| `%request` (`%req`) | ✅ | ✅ | |
| `%server` (`%s`) | ✅ | ✅ | |
| `%session` (`%ses`) | ✅ | ✅ | |
| `%sessionid` (`%sid`) | ✅ | ✅ | |
| `%requestid` (`%rid`) | ❌ | ✅ | *New* — reads `$_ENV['REQUEST_ID']` |

---

## 9. Configuration

| Format | Apache Log4PHP | xsga/log4php |
|---|:---:|:---:|
| XML | ✅ | ✅ |
| Properties (`.properties`) | ✅ | ❌ |
| PHP array | ✅ | ✅ |

The `.properties` file format adapter has been removed.

- File-based configuration supports XML.
- Programmatic configuration supports PHP arrays passed directly to `Logger::configure([...])`.

---

## 10. Developer Tooling

The original project had no enforced code quality tooling beyond PHPUnit tests. This fork introduces a full quality pipeline managed via Composer scripts:

| Tool | Purpose |
|---|---|
| [php-parallel-lint](https://github.com/php-parallel-lint/php-parallel-lint) | PHP syntax validation |
| [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) (PSR-12) | Code style checking and auto-fix |
| [Psalm](https://psalm.dev/) 6 | Static analysis at the strictest level |

```bash
composer lint          # syntax check
composer style         # PSR-12 style check
composer style-fix     # auto-fix style issues
composer analyze-errors  # static analysis (errors only)
composer analyze-info    # static analysis (all info)
```
