# Architecture

OpenMage AI Profiler is built around collectors.

Each collector gathers one category of information and adds it to a report model.

The report model can then be written to multiple formats:

- Text
- Markdown
- JSON

## Planned Core Components

- CollectorInterface
- AbstractCollector
- CollectorRegistry
- Report
- Section
- TextWriter
- MarkdownWriter
- JsonWriter
- CliApplication

## Design Goals

- Read-only
- Collector isolation
- Graceful error handling
- Deterministic output
- No external dependencies
- PHP 5.6 compatible syntax
