# Changelog

All notable changes to this project are documented in this file.

The format is based on Keep a Changelog and uses Semantic Versioning.

## [Unreleased]

## [1.1.0] - 2026-04-27

### Added
- Automatic late-marking for appointments exceeding 10-minute grace period
- Attendance tracking fields (arrived_at, late_marked_at, no_show_marked_at) on appointments
- Email notifications to customer + managers when appointment marked as late or no-show
- Barber dashboard auto-late detection on page load
- Overlap protection: barber cannot start appointment if projectedEnd exceeds next scheduled appointment

### Changed
- Barber status update workflow now triggers attendance notifications for late/no-show state changes
- BarberDashboardController refactored to support per-appointment notification dispatch

### Fixed
- Late notifications now sent consistently for both manual status changes and auto-grace-period expiration

## [1.0.0] - 2026-04-12

### Added
- Multi-tenant lifecycle flows (create, status management, provisioning hooks)
- Role-based dashboards for platform admin, manager, barber, and customer flows
- Billing plan checkout flow and webhook-based subscription updates

### Changed
- Tenant operations and dashboard UX aligned to a card-based SaaS layout
- Centralized system documentation in SYSTEM_OVERVIEW.md

### Fixed
- Tenant listing and owner-email visibility adjustments
- Dashboard visibility and style consistency fixes
