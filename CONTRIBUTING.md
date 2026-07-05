# Contributing

Contributions are welcome.

## Principles

All contributions should follow the project's core principles:

- Read-only.
- Safe by default.
- No customer, order, quote, admin-user or secret data.
- Compatible with PHP 5.6 syntax.
- No external dependencies unless explicitly discussed.
- Collectors should fail gracefully.
- Collectors should be independently testable.

## PHP Compatibility

Use PHP 5.6 compatible syntax.

Do not use:

- Scalar type declarations
- Return type declarations
- Typed properties
- Union types
- Constructor property promotion
- Arrow functions
- Match expressions
- Attributes
- Enums

## Collector Design

Each collector should have a single responsibility and should not modify Magento state.

Collectors should return structured report sections rather than directly echoing output.
