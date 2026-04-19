# Pull-to-Now Integration Log

Date: 2026-04-19

## 1. Purpose
This document records what was integrated and hardened after pulling teammate changes, up to the current code state.

## 2. Baseline From Pulled Code
Pulled branch already contained major RBAC and feature work, including:
1. Branch Manager service module routes and controller.
2. Branch Manager schedule module routes, controller, and view.
3. Owner branch management module (branch CRUD + assign manager).
4. Customer booking lifecycle extensions (cancel, reschedule, feedback).
5. Barber self-service appointment status flow.

## 3. Post-Pull Hardening and Fixes Applied

### 3.1 Navigation and Role UX
1. Restored Branch Manager Services link in sidebar so feature is reachable without manual URL entry.
2. Scope labels and role-aware visibility retained in manager navigation/dashboard.

### 3.2 RBAC and Branch Scope Enforcement
1. Walk-in recording restricted to Branch Manager with assigned branch.
2. Walk-in barber selection now enforces barber belongs to selected branch.
3. Manager dashboard walk-in section now respects branch assignment and branch-filtered barber list.
4. Queue and manager operations remain branch-filter aware for Branch Manager role.

### 3.3 Plan Gating Consistency
1. Added active_plan middleware group on legacy customer booking/service routes under /customer.
2. Prevented alternate-route booking access when tenant has no active plan.

### 3.4 Booking and Schema Stability
1. Fixed booking sort fallback in customer dashboard to check bookings.booked_at.
2. Added same bookings.booked_at fallback logic in customer booking index path.
3. Preserved duplicate-booking guard and active-booking guard behavior.

### 3.5 Credentials and Provisioning Alignment
1. Admin tenant provisioning sync now ensures admin-created active tenants receive active subscription rows.
2. Resend credentials flow hardened with temporary password generation + verification fallback.
3. Tenant URL/domain handling improvements preserved for local port-aware links and professional credential emails.

### 3.6 Calendar Sync Lifecycle
1. Google Calendar event ID persistence enabled for appointment sync.
2. Cancellation sync now supports delete-by-event-ID and fallback matching strategy.

## 4. Documentation Updated
1. SYSTEM_OVERVIEW.md updated to reflect final branch-manager walk-in policy and merge-hardening snapshot.
2. This PULL_TO_NOW_CHANGELOG.md file added for traceability.

## 5. Validation Performed
1. Targeted edited-file error checks passed.
2. PHP lint checks passed for edited controllers/routes.
3. tests/Feature/CustomerBookingGuardsTest.php passed (2/2) after aligning test fixture with active-plan middleware expectations.

## 6. Current Known Status
1. Core owner/branch-manager/customer/barber flows are merge-stabilized for the patched paths.
2. Active-plan gating is now consistent across both booking route families.
3. No unresolved merge conflict markers detected in scanned PHP/Blade/Markdown files.

## 7. Suggested Next Verification (Optional)
1. Manual smoke test by role: Owner, Branch Manager, Barber, Customer.
2. Billing + subscription display verification on a real local tenant domain.
3. End-to-end booking create -> cancel/reschedule -> feedback flow in UI.
