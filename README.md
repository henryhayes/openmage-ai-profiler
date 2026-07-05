# OpenMage AI Profiler
## v0.10.0 architecture coverage

Version `0.10.0` adds the next layer of AI-focused Magento/OpenMage analysis:

- rewrite chain/conflict analysis
- parsed layout XML graph summaries
- PHTML template relationship summaries
- event dispatch to observer mapping
- route/frontName to controller/action mapping
- database schema and likely custom table summaries
- final AI architecture score and risk summary

These collectors are intended to answer: "What would an experienced OpenMage developer immediately want to know before changing this installation?"


> **Understand your Magento 1.x or OpenMage installation in minutes, not hours.**

AI-ready diagnostics, architecture analysis and project profiling for Magento Community Edition 1.x and OpenMage LTS.

---

# Overview

OpenMage AI Profiler is a read-only diagnostics and architecture analysis tool that generates AI-ready reports for Magento Community Edition 1.x and OpenMage installations.

Unlike traditional diagnostics utilities, its primary purpose is not simply to dump configuration values. Instead, it analyses an installation and produces structured reports that explain how it is built, how it has been customised, where complexity exists, and which parts of the system are most significant.

The generated reports are designed to be uploaded into Large Language Models (LLMs) such as ChatGPT, allowing an AI assistant to understand the architecture of an installation before helping with development, debugging, optimisation or migration.

The long-term goal is to become the definitive profiling and architectural analysis tool for Magento Community Edition 1.x and OpenMage.

---

# Philosophy

Traditional diagnostics answer the question:

> **"What is installed?"**

OpenMage AI Profiler answers the question:

> **"How is this installation built?"**

The distinction is important.

Rather than simply dumping thousands of configuration values, the profiler explains architecture, relationships, customisations, dependencies and complexity.

The output is designed to minimise ambiguity for both humans and AI systems by grouping related information, identifying architectural patterns and providing context instead of isolated values.

---

# Internal Architecture

The project is deliberately organised around small, focused responsibilities.

```text
Collectors
  Gather full read-only facts from Magento/OpenMage and the filesystem.

Report writers
  Render the full collected profile as TXT, JSON and optional Markdown.

AI context extractors
  Convert full collector output into a compact, high-signal context file.

AI prompt writer
  Writes usage instructions only. It does not duplicate the context data.
```

`AiContextBuilder` is intentionally small. It only orchestrates extractor classes from `src/Context/Extractors`. Collector-specific summarisation belongs in those extractor classes, not in the builder and not in the collectors themselves.

---

# Scope

OpenMage AI Profiler is designed exclusively for:

- Magento Community Edition 1.7
- Magento Community Edition 1.8
- Magento Community Edition 1.9
- OpenMage LTS

Magento 2 is **not** currently supported.

---

# Objectives

The profiler is designed around five core objectives.

## 1. Understand the architecture

Rather than simply listing modules or configuration values, the profiler explains how the installation is structured.

Examples include:

- Custom modules
- Rewrites
- Observers
- Cron jobs
- Themes
- Layouts
- Dependencies
- Routing
- EAV structure
- Module relationships

---

## 2. Explain complexity

Large Magento installations often contain many years of custom development.

The profiler identifies:

- Architectural hotspots
- Heavily customised modules
- Rewrite chains
- Rewrite conflicts
- Observer density
- Large templates
- Large classes
- Maintenance risks
- Performance hotspots

---

## 3. Produce AI-ready reports

The generated reports are intended to be consumed by both humans and AI systems.

Rather than producing thousands of unrelated configuration values, reports are organised into logical sections with architectural context.

Future versions will include:

- Architecture summaries
- Complexity scoring
- Risk analysis
- Upgrade readiness
- Performance observations
- AI-generated installation summaries

---

## 4. Never modify the installation

OpenMage AI Profiler is strictly read-only.

It will never:

- Modify configuration
- Flush caches
- Rebuild indexes
- Execute setup scripts
- Update the database
- Delete files
- Change permissions

The only files it creates are its own report outputs.

---

## 5. Protect sensitive information

Reports intentionally exclude or redact sensitive information.

This includes (but is not limited to):

- Passwords
- Crypt keys
- API keys
- Payment credentials
- Customer information
- Orders
- Quotes
- Administrator accounts
- Personally identifiable information (PII)

The intention is that reports can be safely shared with developers, consultants or AI assistants.

---

# Non-goals

OpenMage AI Profiler is **not** intended to:

- Replace monitoring software
- Benchmark server performance
- Modify Magento
- Repair installations
- Replace static analysis tools
- Replace unit testing
- Replace code review

---

# Compatibility

The profiler is designed to run on the same server as the Magento installation being analysed.

## Magento

Supported platforms:

- Magento Community Edition 1.7
- Magento Community Edition 1.8
- Magento Community Edition 1.9
- OpenMage LTS

## PHP

The profiler intentionally uses a conservative subset of PHP to maximise compatibility.

Supported runtimes:

- PHP 5.6
- PHP 7.0
- PHP 7.1
- PHP 7.2
- PHP 7.3
- PHP 7.4
- PHP 8.x (where supported by the target Magento/OpenMage installation)

No external dependencies are required.

Composer is **not** required.

---

# Planned Features

The long-term vision includes:

- Installation overview
- System analysis
- PHP environment analysis
- Filesystem analysis
- Store hierarchy
- Theme analysis
- Layout analysis
- Module inventory
- Module dependency analysis
- Rewrite analysis
- Rewrite conflict detection
- Event and observer analysis
- Cron analysis
- Controller inventory
- Block inventory
- Helper inventory
- Model inventory
- Router analysis
- Product architecture analysis
- Category analysis
- EAV analysis
- CMS analysis
- Cache analysis
- Index analysis
- Database analysis
- Security review
- Performance observations
- Complexity scoring
- Architectural hotspot detection
- Upgrade readiness analysis
- AI-generated architecture summaries

---

# Project Status

Current Version

```text
v0.9.0
```

Status

```text
Architecture refactor / pre-1.0 hardening
```

The project now includes the core collector framework, automatic collector registration, dependency-aware collector ordering, full profile writers, compact AI context generation and an instructions-only AI prompt writer.

The main focus before a future 1.0 release is deeper architectural analysis and continued hardening of the generated AI context.

---

# Roadmap

## v0.1.0

Framework

- Project structure
- Collector framework
- Collector registry
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

Architecture collectors

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

Catalogue analysis

- Products
- Categories
- Attributes
- EAV
- CMS

---

## v0.6.0

Architecture analysis

- Rewrite chains
- Rewrite conflicts
- Module dependencies
- Module complexity
- Template inventory
- Code metrics

---

## v0.7.0

Architectural Intelligence

- Architecture summary
- Complexity scoring
- Risk analysis
- Hotspot detection
- Upgrade readiness
- Performance observations
- AI summaries

---

## v0.8.0

Report Refinement

- Automatic collector registration with stable preferred ordering
- Dependency-safe collector execution
- Instructions-only ChatGPT prompt output
- Cleaner separation between prompt, compact context and full profile

---

## v1.0.0

Stable Release

Production-ready.

---

# Output Formats

The profiler generates multiple report formats.

## AI Context

```text
ai-project-context.txt
```

This is intended to be uploaded first when asking an AI assistant for help.

---

## ChatGPT Prompt

```text
ai-chatgpt-prompt.txt
```

A ready-made prompt file containing recommended AI instructions plus the generated project context.

---

## Text

```text
ai-project-profile.txt
```

Complete human-readable technical inventory.

This is the preferred detailed report to upload alongside the AI context file.

---

## JSON

```text
ai-project-profile.json
```

Machine-readable output suitable for automation, comparison tools and future integrations.

---

## Markdown

```text
ai-project-profile.md
```

An optional Markdown version of the project profile, intended for GitHub, documentation and code reviews.

Generate with: `php profiler.php --markdown`

---

## HTML *(planned)*

An interactive report with navigation, filtering and architecture visualisation.

---

# Design Principles

The project follows several key principles.

- Read-only
- Safe by default
- AI-first
- Deterministic output
- Collector isolation
- Graceful degradation
- Versioned report schema
- No external dependencies
- Object-oriented architecture
- Modular collectors
- Maximum compatibility with Magento 1.x and OpenMage

---

# Contributing

Contributions are welcome.

The project aims to become a community resource for Magento Community Edition 1.x and OpenMage developers.

Ideas, bug reports, new collectors and improvements are all encouraged.

Please ensure all contributions follow the project's design principles:

- Read-only
- Backwards compatible
- Well documented
- Independently testable
- Safe to run on production systems

---

# License

This project is released under the MIT License.
