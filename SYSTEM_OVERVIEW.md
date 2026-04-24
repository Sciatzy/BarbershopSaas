# Online Barbershop Booking and Management System (SaaS)

Last updated: 2026-04-19

## 1. Executive Summary

This system is a multi-tenant Software-as-a-Service platform for barbershop operations. It centralizes customer booking, staff operations, branch management, subscription billing, and performance tracking.

Primary goals:
1. Digitize appointment handling to reduce walk-in congestion.
2. Enforce role-based access with strict tenant data isolation.
3. Support scalable plans from single-shop to multi-branch/franchise-like operations.
4. Automate lifecycle tasks (provisioning, billing activation, notifications).

Technology stack:
1. Laravel (PHP) application framework.
2. Blade views + Tailwind-style UI patterns.
3. MySQL database.
4. Laravel Cashier subscription model usage (customized for PayMongo checkout flow).
5. Spatie Permission for RBAC.

## 2. Architecture Overview

### 2.1 Multi-Tenancy Model

The platform is tenant-centric. Each tenant represents one barbershop business and has isolated operational data.

Tenant-isolated domains of data include:
1. Branches.
2. Barbers and manager-side staff accounts.
3. Appointments/bookings.
4. Services and pricing.
5. Points and performance transactions.

Isolation strategy:
1. Tenant-aware querying in controllers/models.
2. Tenant middleware and scoped access checks.
3. Role + tenant ownership checks on mutation actions.

### 2.2 Tenant Types Supported by Plan Capacity

Operationally, tenant behavior maps to your approved categories through plan limits:
1. Single-location tenant: starter/professional limits.
2. Multi-branch tenant: business plan (up to 3 branches).
3. Large chain/franchise-like tenant: enterprise plan (unlimited branches and barbers).

## 3. User Roles and Access Boundaries

System roles:
1. Platform Admin.
2. Barbershop Admin (Owner).
3. Branch Manager.
4. Barber.
5. Customer.

### 3.1 Platform Admin

Capabilities:
1. Create tenants and owner credentials.
2. Update tenant status and plan tier.
3. Suspend/reactivate tenant access.
4. Trigger database provisioning and resend credentials.
5. Access platform-level admin dashboard.

### 3.2 Barbershop Admin (Owner)

Capabilities:
1. Tenant-wide manager dashboard and oversight.
2. Billing plan selection and checkout initiation.
3. Domain/setup controls.
4. Branch management (create/update/delete) with active-plan gating.
5. Barber account management with plan-limit validation.

### 3.3 Branch Manager

Capabilities (branch-scoped):
1. Queue monitoring and status updates in own branch.
2. Service management routes currently branch-manager scoped in code.
3. Branch schedule management for barbers in assigned branch.
4. Walk-in recording is branch-manager scoped and server-side validated to assigned branch and branch-matching barber.

### 3.4 Barber

Capabilities:
1. Own daily dashboard with schedule and today appointments.
2. Own appointment progression actions:
- queued to in_progress
- in_progress to completed
3. Cannot mutate appointments not assigned to self.

### 3.5 Customer

Capabilities:
1. Browse services and create bookings.
2. Select preferred barber and appointment inputs in available flows.
3. Cancel and reschedule allowed booking states.
4. Submit rating and textual feedback for completed bookings.
5. Access customer dashboard, profile, points, and booking history views.

## 4. Subscription and Pricing Model

Monthly tiers implemented:
1. Starter: PHP 499
- Branch limit: 1
- Barber limit: 2
2. Professional: PHP 1299
- Branch limit: 1
- Barber limit: 5
3. Business: PHP 2499
- Branch limit: 3
- Barber limit: unlimited
4. Enterprise: PHP 4999
- Branch limit: unlimited
- Barber limit: unlimited

Limit enforcement source:
1. TenantLimitValidator service validates branch and barber creation.
2. Limits are cached and invalidated on relevant model/role changes.

Note:
1. Annual billing is currently not implemented.

## 5. Billing and Activation Flow

### 5.1 Checkout

1. Owner opens billing plans and selects tier.
2. System creates PayMongo checkout session.
3. User is redirected to PayMongo checkout URL.

### 5.2 Activation Source of Truth

1. PayMongo webhook endpoint receives paid events.
2. Tenant subscription record is updated/created.
3. Tenant plan tier and active status are updated.
4. Previous active subscription records are normalized/cancelled where needed.
5. Tenant environment provisioning runs when required.

### 5.3 Post-Checkout UX

1. Success and cancel routes return role-appropriate redirects.
2. Session-based direct checkout status check is attempted when possible.
3. Webhook remains the final activation authority.

## 6. Tenant Lifecycle States

States in use:
1. pending
2. active
3. inactive
4. suspended

Behavior summary:
1. active with active/trialing subscription and valid end date means operational access is enabled.
2. inactive or suspended restricts protected tenant features.
3. Reactivation can restore suspended subscription status according to lifecycle logic.

## 7. Active Plan Gating

Protected routes use active_plan middleware.

Gating behavior:
1. Platform Admin bypasses plan gating.
2. Owner without active plan is redirected to billing plans.
3. Branch Manager without active plan is redirected to manager dashboard with plan-required message.
4. JSON callers receive payment-required style response (402).

## 8. Operational Modules

### 8.1 Admin Tenant Module

Implemented actions:
1. Tenant creation with owner provisioning.
2. Status and plan updates.
3. Suspend/reactivate behavior.
4. Database provisioning trigger.
5. Credential regeneration and resend.

### 8.2 Manager Dashboard Module

Includes:
1. Subscription/details panel.
2. Domain settings (owner-controlled path).
3. Customer availed services list.
4. Branch appointments monitoring table.
5. Barber points visibility.
6. Service visibility.

### 8.3 Branch Management Module (Owner)

Includes:
1. Create branch.
2. Update branch.
3. Delete branch with safety checks.
4. Plan usage cards and branch limit indicator.
5. Route, controller, and UI-level active-plan controls.

### 8.4 Barber Management Module

Includes:
1. Create barber account under tenant.
2. Branch assignment rules by role scope.
3. Auto-generated temporary password emailed to barber.
4. Plan limit validation on barber creation.

### 8.5 Branch Schedule Management Module

Includes:
1. Branch Manager can create/update/delete schedules for own-branch barbers.
2. Day-of-week and work time block management.
3. Upsert behavior by barber plus day.
4. Cross-branch schedule mutation denial.

### 8.6 Queue and Service Operations

Includes:
1. Queue listing and status transitions for manager roles.
2. Branch filters for Branch Manager where applied.
3. Service create/update flows in manager routes.

### 8.7 Walk-In Recording

Includes:
1. Walk-in completion recording endpoint.
2. Captures service/barber/branch work metadata for scoring and operations.

### 8.8 Barber Daily Workspace

Includes:
1. Today schedule blocks.
2. Today appointments.
3. Total barber points view.
4. Start and Complete buttons for own appointments only.

### 8.9 Customer Booking Lifecycle

Includes:
1. Booking create/store/index.
2. Cancel booking action.
3. Reschedule queued/confirmed booking to future date/time.
4. Rating and feedback submission for completed bookings.

## 9. Points and Performance System

Barber incentive rules implemented through events/listeners:
1. Standard service completion: 10 points.
2. Premium service completion: 15 points.
3. 5-star customer rating: 20 points.
4. On-time completion: 5 points.
5. Rebooking same barber (online flow condition): 25 points.

Additional customer points flow:
1. PointsService awards customer loyalty points based on booking value (floor(price/50)).

## 10. Event and Notification Flows

Event-triggered behaviors:
1. Appointment confirmed event:
- Send appointment confirmation email.
- Sync appointment to Google Calendar.
2. Appointment completed event:
- Award service points.
- Award punctuality points.
3. Review submitted event:
- Award rating points.
4. Appointment model created event:
- Award rebooking points when criteria are met.

Lifecycle notifications:
1. Owner receives activation, plan update, suspension/deactivation, provisioning, and credentials messages.
2. Credential mails include login URL and temporary password where applicable.

## 11. Data Model Highlights

Core entities:
1. tenants
2. users
3. branches
4. services
5. appointments (Booking maps to appointments table)
6. schedules
7. subscriptions (Cashier)
8. point_transactions
9. points_ledger

Recent schema addition:
1. appointments.customer_feedback field added for completed-booking feedback capture.
2. appointments.google_calendar_event_id field added for reliable Google Calendar update/delete behavior.

## 12. Route Surface Summary

### 12.1 Platform Admin
1. /admin
2. /admin/tenants/* lifecycle/provisioning/credentials actions

### 12.2 Owner and Branch Manager Shared Entry
1. /manager
2. /manager/barbers
3. /manager/queue

### 12.3 Owner-Specific
1. /manager/setup
2. /manager/domain
3. /manager/branches
4. /billing/plans and /billing/{tenant}/checkout/*

### 12.4 Branch Manager-Specific Group
1. /manager/services
2. /manager/schedules
3. /manager/queue/{booking}/status
4. /manager/walk-ins

### 12.5 Barber
1. /barber
2. /barber/appointments/{booking}/status

### 12.6 Customer
1. /booking/* routes (book, cancel, reschedule, feedback)
2. /customer/* routes (dashboard, services, bookings, points, profile, notifications)

## 13. Security and Authorization Controls

Implemented layers:
1. Route middleware by role.
2. Active plan middleware for protected features.
3. Controller-level tenant and ownership checks for sensitive mutations.
4. UI-level visibility controls to reduce privilege confusion.
5. Branch scoping in operational controllers where required.

## 14. Local Environment and Runtime

Typical local startup:
1. php artisan optimize:clear
2. php artisan serve --host=127.0.0.1 --port=8000
3. npm run dev

Base URL:
1. http://127.0.0.1:8000

PayMongo webhook health route:
1. GET /paymongo/webhook

Production-style webhook delivery in local testing requires tunnel setup (for example, ngrok).

## 15. Test Credentials (Current)

Shared password:
1. password123

Accounts:
1. Platform Admin: admin@platform.com 
2. Barbershop Admin: manager@barbershop.test
3. Branch Manager: branchmanager@barbershop.test
4. Barber: barber@barbershop.test
5. Customer: customer@barbershop.test

## 16. Current Scope Status

Approved-scope major modules are implemented, including:
1. Owner branch management with plan gating.
2. Customer reschedule and rating/feedback.
3. Branch Manager schedule management.
4. Barber self-service appointment progression.

Open/deferred item:
1. Optional annual billing model is not implemented in current codebase.

## 17. Documentation References

For implementation audit and defense scripts, see:
1. TENANT_MODULES_COMPLETION.md
2. CHANGELOG.md
3. VERSIONING.md

## 18. Suggested Next Commit Workflow

When ready to commit this documentation update on a new branch:

1. Create branch:
- git checkout -b docs/system-overview-refresh

2. Stage file:
- git add SYSTEM_OVERVIEW.md

3. Commit:
- git commit -m "docs: refresh system overview to match approved scope and current implementation"

4. Push branch:
- git push -u origin docs/system-overview-refresh

## 19. Merge Hardening Snapshot (Pull to Current)

This section summarizes important post-pull merge-hardening changes already integrated in code:

1. Branch Manager service page visibility was restored in sidebar navigation.
2. Walk-in recording flow was tightened:
- only available for branch managers with an assigned branch
- selected barber must belong to selected branch
3. Customer booking sort fallback now checks bookings.booked_at (not appointments.booked_at), preventing schema mismatch errors.
4. Legacy /customer booking routes were aligned with active_plan middleware to prevent plan-gating bypass.
5. Booking guard tests were updated with active subscription fixtures and are passing.

Validated outcomes:
1. Edited controllers/routes lint clean.
2. Booking guard feature tests passed (2/2).


#start application 
Composer dev