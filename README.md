# Xsga Log4PHP

[![PHP](https://img.shields.io/badge/PHP-8.4%2B-blue?logo=php)](https://www.php.net/)
[![PSR-3](https://img.shields.io/badge/PSR--3-compliant-brightgreen)](https://www.php-fig.org/psr/psr-3/)
[![License: Apache 2.0](https://img.shields.io/badge/License-Apache_2.0-yellow.svg)](LICENSE)

> A modern, PSR-compliant successor to Apache Log4PHP (now unmaintained).

Xsga Log4PHP is a heavily refactored and redesigned logging library for PHP, originally inspired by Apache Log4PHP and rebuilt for the current PHP ecosystem. It targets PHP 8.4+, follows PSR-3, ships with PSR-4 autoloading, and provides a flexible logging pipeline with appenders, layouts, context interpolation, and XML or programmatic configuration.

---

## Table of Contents

* [Why this project exists](#why-this-project-exists)
* [Key Features](#key-features)
* [What makes it different](#what-makes-it-different)
* [Quick Start](#quick-start)
* [Basic Configuration](#basic-configuration)
* [Documentation](#documentation)
* [Who should use this](#who-should-use-this)
* [License](#license)
* [Acknowledgements](#acknowledgements)

---

<a id="why-this-project-exists"></a>
## 📌 Why this project exists

Apache Log4PHP is no longer actively maintained and does not align with current PHP versions, tooling, or interoperability expectations.

This project exists to:

* Provide a conceptual successor for teams familiar with Apache Log4PHP
* Preserve the strengths of the original model while removing outdated constraints
* Modernize the architecture, code style, and runtime requirements
* Align the library with current PHP standards, especially PSR-3 and PSR-4
* Offer a maintainable codebase suitable for static analysis, code style checks, and ongoing development

---

<a id="key-features"></a>
## ✨ Key Features

* ✅ PSR-3 compatible logging interface and context placeholder interpolation
* ✅ PSR-4 autoloading support via Composer
* ✅ PHP 8.4+ compatibility
* ✅ XML-based and programmatic array-based configuration
* ✅ Multiple appenders, including File, DailyFile, RollingFile, Console, and Loki
* ✅ Multiple layouts, including Pattern, JSON, and Simple
* ✅ Full log level support from ALL through OFF, aligned with modern logging expectations
* ✅ Refactored architecture with cleaner separation of responsibilities
* ✅ Improved maintainability, extensibility, and static-analysis friendliness

---

<a id="what-makes-it-different"></a>
## 🔍 What makes it different

Compared to the original Apache Log4PHP:

* The internals have been substantially refactored for modern PHP and stricter typing expectations
* The package is distributed as a Composer-first library with PSR-4 autoloading
* Logging follows the PSR-3 method set and context conventions
* Configuration remains familiar, but the implementation is cleaner and easier to maintain
* Appenders and layouts are designed for present-day use cases, including structured JSON output and Loki integration
* Development tooling now includes linting, PSR-12 style checks, and Psalm-based static analysis

For a detailed comparison, see:

👉 [doc/differences-from-apache-log4php.md](doc/differences-from-apache-log4php.md)

---

<a id="quick-start"></a>
## 🚀 Quick Start

Install via Composer:

```bash
composer require xsga/log4php
```

Basic usage:

```php
use Xsga\Log4Php\Logger;

// Load configuration from an XML file.
Logger::configure('path/to/log4php.xml');

// Obtain a named logger.
$logger = Logger::getLogger('MyApp');
$logger->info('Application started.');
$logger->warning('Disk space running low.', ['threshold' => '90%']);

// Or use the root logger.
$root = Logger::getRootLogger();
$root->debug('Root logger is ready.');
```

If you prefer to configure the library without XML, you can also pass a PHP array directly to `Logger::configure()`.

---

<a id="basic-configuration"></a>
## ⚙️ Basic Configuration

Configuration can be defined using XML or arrays.

XML example:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<configuration xmlns="http://logging.apache.org/log4php/" threshold="all">

  <!-- File appender with pattern layout: one line per event -->
  <appender name="default" class="LoggerAppenderFile">
    <param name="file" value="logs/app.log" />
    <layout class="LoggerLayoutPattern">
      <param name="conversionPattern" value="%date{Y-m-d H:i:s} [%-5level] %message%newline" />
    </layout>
  </appender>

  <root>
    <level value="debug" />
    <appender_ref ref="default" />
  </root>
</configuration>
```

Programmatic example:

```php
use Xsga\Log4Php\Logger;

Logger::configure([
    'appenders' => [
        'default' => [
            'class' => 'LoggerAppenderFile',
            'params' => [
                'file' => 'logs/app.log',
            ],
            'layout' => [
                'class' => 'LoggerLayoutPattern',
            ],
        ],
    ],
    'rootLogger' => [
        'level' => 'debug',
        'appenders' => ['default'],
    ],
]);
```

---

<a id="documentation"></a>
## 📚 Documentation

Project documentation is currently centered on the repository itself:

* Differences from Apache Log4PHP: [doc/differences-from-apache-log4php.md](doc/differences-from-apache-log4php.md)
* Project documentation: [doc/DOCUMENTATION.md](doc/DOCUMENTATION.md)

---

<a id="who-should-use-this"></a>
## 👥 Who should use this

This project is a good fit if you:

* Are currently using Apache Log4PHP and need a modern successor with a familiar mental model
* Need a PHP 8.4+ logging library with PSR-3 semantics
* Want XML-driven logging configuration without giving up programmatic configuration options
* Need appenders for local files, rolling files, console output, or Grafana Loki
* Prefer a lightweight logging library over larger framework-coupled solutions

---

<a id="license"></a>
## 📄 License

This project is based on Apache Log4PHP and remains licensed under the Apache 2.0 License. See [LICENSE](LICENSE) for details.

It includes substantial refactoring, modernization, and new implementation work tailored to the current PHP ecosystem.

---

<a id="acknowledgements"></a>
## 🙌 Acknowledgements

* Apache Log4PHP
* Apache Software Foundation
