# Changelog

All notable changes to this project are documented in this file.

This project adheres to semantic versioning.

## [0.2.0] - 2025-10-15
- Refactor: Archive legacy payment controllers (`TransactionController`, `WebhookController`) to `app/Http/Controllers/Archive/V1/`.
- Remove provider-based lookups and references to dropped transaction columns.
- Align runtime with final transactions schema; disable payment/webhook routes.
- Update documentation: project README and API overview.

## [0.1.0] - 2025-10-15
- Initial project setup with contracts, verifications, notifications, and base app scaffolding.

### Notes
- Future preparation: add `payment_metadata` table and a dedicated fee service to support reintroducing payments without legacy columns.