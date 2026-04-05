# Barbershop SaaS - System Overview

## 1. What This System Is
Barbershop SaaS is a multi-tenant web system where each barbershop tenant has its own isolated data context, its own manager/admin users, and subscription-based access to features.

This project supports:
- Platform-level tenant management (central admin control)
- Tenant-level operations (manager and barber workflows)
- Subscription and payment flows (PayMongo checkout + webhook activation)
- Controlled tenant lifecycle states (pending, active, inactive, suspended)
- Email notifications for provisioning, activation, and lifecycle updates

## 2. Core Roles
- Platform Admin: manages tenants, status, plan tier, provisioning actions
- Barbershop Admin: manages tenant operations, billing, barbers
- Branch Manager: daily operations under tenant scope
- Barber: service execution and appointment workflow
- Customer: booking workflow

## 3. Main Functional Modules

### A. Tenant Lifecycle and Provisioning
- Tenant creation from admin dashboard
- Automatic owner account creation for admin-created tenant
- Domain assignment and database provisioning logic
- Reactivation and suspension handling
- Tenant status-driven access control

### B. Billing and Subscription
- Plan tiers: Starter, Professional, Business, Enterprise
- Checkout endpoints for each plan
- Payment confirmation by PayMongo webhook (source of truth)
- Success/cancel handling for checkout UX
- Guardrails for inactive/suspended tenants with existing subscription

### C. Access Control and Feature Gating
- Role-based route middleware
- Active-plan middleware for protected tenant features
- Manager and tenant features blocked when access is inactive/suspended

### D. Dashboards and Operations
- Platform Admin dashboard: tenant signup, tenant list, tenant status/plan updates, provisioning action
- Manager dashboard: subscription overview, walk-in recording, appointment list, points and service visibility
- Manager barbers page: barber account management and tenant limits

### E. Notification System
- Tenant lifecycle notification service
- Structured/professional HTML emails
- Clickable links for login/system URLs in details payload

## 4. Tenant Status Behavior
- pending: tenant created but not fully active for operational use
- active: operational routes and features available
- inactive: tenant access locked until reactivation
- suspended: tenant access locked and requires admin intervention/reactivation path

## 5. Key Project Structure (High-Level)
- app/Http/Controllers: tenant, billing, webhook, dashboard, auth flow controllers
- app/Services: provisioning, notification, payment helper services
- resources/views/admin: platform admin screens
- resources/views/manager: manager operational screens
- resources/views/billing: plan and billing UI
- routes/web.php: core web routes and middleware mapping

## 6. Local Redirect Links (for Localhost Testing)
Base URL:
- http://127.0.0.1:8000

Auth pages:
- Login: http://127.0.0.1:8000/login
- Register: http://127.0.0.1:8000/register
- Role redirect entry point after login: http://127.0.0.1:8000/dashboard

Role destination routes after `/dashboard`:
- Platform Admin -> http://127.0.0.1:8000/admin
- Tenant side (Barbershop Admin / Branch Manager) -> http://127.0.0.1:8000/manager
- Customer -> http://127.0.0.1:8000/booking

Other useful checks:
- Barber dashboard: http://127.0.0.1:8000/barber
- Billing plans (Barbershop Admin): http://127.0.0.1:8000/billing/plans
- Plan required page: http://127.0.0.1:8000/billing/plan-required
- PayMongo webhook health check: http://127.0.0.1:8000/paymongo/webhook

## 7. Quick Walkthrough (Admin, Tenant, Customer)
Use this sequence to validate role paths and gate behavior quickly.

1. General login + role redirect behavior
- Go to http://127.0.0.1:8000/login and sign in with a test account.
- Visit http://127.0.0.1:8000/dashboard.
- Confirm redirect by role:
	- Platform Admin -> `/admin`
	- Barbershop Admin or Branch Manager -> `/manager`
	- Customer -> `/booking`

2. Platform Admin flow
- Open http://127.0.0.1:8000/admin.
- Validate tenant list loads.
- Perform tenant update/status changes (pending/active/inactive/suspended).
- Run provisioning action if needed (`/admin/tenants/{tenant}/provision-database`).

3. Tenant flow (Barbershop Admin / Branch Manager)
- Open http://127.0.0.1:8000/manager.
- Check dashboard cards, appointments, and operational widgets.
- Open barber management: http://127.0.0.1:8000/manager/barbers (requires active plan).
- As Barbershop Admin, open billing plans: http://127.0.0.1:8000/billing/plans.

4. Customer flow
- Open http://127.0.0.1:8000/booking.
- Confirm booking list/form loads under active plan conditions.
- Submit booking and verify it appears in tenant operational views.

5. Access/plan gating sanity
- If tenant is inactive/suspended or lacks active subscription, protected routes should block and route users to the appropriate restriction/plan-required experience.
- Re-activate tenant and verify protected routes become available again.

## 8. Before Commit Checklist
Use this quick checklist before pushing to GitHub:

1. Functional sanity
- Tenant list loads and shows existing records
- Admin tenant update form can submit changes
- Manager pages still load for valid roles
- Billing plans and checkout endpoints resolve correctly

2. Lifecycle sanity
- Tenant status changes (pending/active/inactive/suspended) persist
- Inactive/suspended lock behavior still works as expected

3. Notification sanity
- Lifecycle emails still send with expected formatting
- Important links in email details are clickable

4. Code sanity
- No accidental debug code left in views/controllers
- No temporary patch scripts included
- Relevant files are staged (and only intended files)

## 9. Commit Steps
From the project root:

```bash
cd "C:/Users/SCIATZY MARIE/Documents/3rd Year Second Sem/Web System/Barbershop_multitenant/barbershop-saas"
```

Check changed files:

```bash
git status
```

Stage your intended files (including this overview doc):

```bash
git add SYSTEM_OVERVIEW.md app resources/views routes
```

Create commit:

```bash
git commit -m "Add system overview doc and finalize tenant lifecycle + dashboard updates"
```

Push to GitHub:

```bash
git push origin main
```

## 10. Suggested Repo Description (Optional)
Multi-tenant Barbershop SaaS built with Laravel, including tenant lifecycle management, role-based access, subscription billing via PayMongo, and admin/manager operational dashboards.
