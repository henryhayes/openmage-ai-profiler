# OpenMage AI Profiler

> AI-ready diagnostics, architecture analysis and project profiling for Magento 1.x and OpenMage.

## Overview

OpenMage AI Profiler is a read-only diagnostics and architecture analysis tool for Magento Community Edition 1.x and OpenMage.

Unlike traditional diagnostics utilities, its primary purpose is not simply to dump configuration values. Instead, it produces structured, AI-friendly reports that explain how an installation is built, how it has been customised, where complexity exists, and which parts of the system are most significant.

The generated reports are designed to be uploaded into Large Language Models (LLMs) such as ChatGPT, allowing an AI assistant to understand the architecture of an installation before helping with development, debugging or optimisation.

The project aims to become the definitive profiling and architectural analysis tool for Magento 1.x and OpenMage.

---

# Objectives

The profiler is designed around five core objectives.

## 1. Understand the architecture

Rather than simply listing modules or configuration values, the profiler explains how the installation is structured.

Examples include:

- custom modules
- rewrites
- observers
- cron jobs
- themes
- layouts
- dependencies
- routing
- EAV structure
- module relationships

---

## 2. Explain complexity

Large Magento installations often contain years of custom development.

The profiler identifies:

- architectural hotspots
- heavily customised modules
- rewrite chains
- observer density
- large templates
- large classes
- potential maintenance risks

---

## 3. Produce AI-friendly reports

The generated reports are intended to be read by both humans and AI systems.

Rather than producing thousands of unrelated configuration values, the report is structured into logical sections with context.

Future versions will include architecture summaries, complexity scoring and installation health reports.

---

## 4. Never modify the installation

OpenMage AI Profiler is strictly read-only.

It will never:

- modify configuration
- flush caches
- rebuild indexes
- execute setup scripts
- update the database
- create or delete files (other than its own report output)

---

## 5. Protect sensitive information

Reports intentionally exclude or redact sensitive information.

This includes (but is not limited to):

- passwords
- crypt keys
- API keys
- payment credentials
- customer information
- orders
- quotes
- administrator accounts
- personally identifiable information

The goal is to allow reports to be safely shared with developers or AI assistants.

---

# Compatibility

The profiler is designed to run on the same server as the Magento installation being analysed.

## Magento

- Magento Community Edition 1.7
- Magento Community Edition 1.8
- Magento Community Edition 1.9
- OpenMage LTS

## PHP

The profiler is intentionally written using a conservative subset of PHP to maximise compatibility.

Supported versions:

- PHP 5.6
- PHP 7.0
- PHP 7.1
- PHP 7.2
- PHP 7.3
- PHP 7.4
- PHP 8.x (where supported by the target installation)

No external dependencies are required.

Composer is **not** required.

---

# Project Status

Current Version

```
v0.1.0
```

Status

```
Framework
```

The project is currently establishing its internal architecture before implementing collectors.

---

# Roadmap

## v0.1.0

Framework

- Project structure
- Collector framework
- Report model
- Output writers
- CLI runner
- Error handling
- Logging
- Timing
- Versioning

---

## v0.2.0

Core collectors

- System
- PHP
- Magento
- Filesystem
- Stores
- Modules

---

## v0.3.0

Magento architecture

- Themes
- Layouts
- Routers
- Controllers
- Blocks
- Models
- Helpers

---

## v0.4.0

Application behaviour

- Events
- Observers
- Cron
- Cache
- Indexes

---

## v0.5.0

Catalog analysis

- Products
- Categories
- Attributes
- EAV
- CMS

---

## v0.6.0

Code analysis

- Rewrite chains
- Rewrite conflicts
- Module dependencies
- Module complexity
- Template inventory

---

## v0.7.0

AI analysis

- Architecture summary
- Complexity scoring
- Risk analysis
- Hotspot detection
- Upgrade readiness
- Performance observations

---

## v1.0.0

Stable release

Production ready.

---

# Supported Platforms

The aim is to support:

- Magento Community Edition 1.7
- Magento Community Edition 1.8
- Magento Community Edition 1.9
- OpenMage

---

# Output Formats

The profiler will generate multiple report formats.

## Text

```
ai-project-profile.txt
```

Designed for reading and uploading into AI assistants.

---

## Markdown

```
ai-project-profile.md
```

Human-readable documentation.

---

## JSON

```
ai-project-profile.json
```

Machine-readable output for tooling and future integrations.

---

# Design Principles

The project follows several key principles.

- Read-only
- Safe by default
- No external dependencies
- Compatible with Magento 1.x and OpenMage
- Object-oriented
- Modular collectors
- AI-first report structure
- Graceful error handling
- Deterministic output

---

# Contributing

Contributions are welcome.

Future versions are expected to include support for additional third-party modules, improved architectural analysis and enhanced AI summaries.

---

# License

MIT License.
