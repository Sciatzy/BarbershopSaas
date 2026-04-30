# Online Barbershop Booking and Management System (SaaS)
## Comprehensive Project Documentation

**Project Version:** 1.1.0  
**Last Updated:** April 27, 2026  
**Status:** In Development

---

## 1. Introduction

### Description

The **Online Barbershop Booking and Management System (SaaS)** is a comprehensive multi-tenant Software-as-a-Service platform designed to streamline and digitize barbershop operations. This system addresses the operational complexities of modern barbershop businesses by providing a unified solution for appointment management, staff coordination, customer relationship management, and subscription-based billing.

### Key Value Propositions

1. **Operational Efficiency**: Eliminates walk-in congestion through intelligent appointment scheduling
2. **Scalability**: Supports single-location shops to multi-branch franchises through tiered subscription plans
3. **Customer Experience**: Provides customers with intuitive booking, rescheduling, and rating interfaces
4. **Staff Empowerment**: Gives barbers and managers real-time tools for queue management and attendance tracking
5. **Automated Lifecycle**: Handles tenant provisioning, billing activation, and lifecycle notifications automatically
6. **Data Isolation**: Ensures strict tenant data separation for security and compliance

### Purpose

This system serves barbershop owners (Barbershop Admins) who want to:
- Digitize their appointment process
- Manage multiple locations with centralized oversight
- Track staff performance through a points-based system
- Automate billing and subscription management
- Provide professional customer booking experiences

---

## 2. Project Overview

### High-Level System Description

The platform is built on a modern software stack combining:
- **Framework**: Laravel 12 (PHP web framework)
- **Frontend**: Blade templating with Tailwind CSS for responsive design
- **Database**: MySQL for multi-tenancy data storage
- **Authentication**: Laravel Breeze with role-based access control (RBAC) via Spatie Permission
- **Billing**: PayMongo integration for checkout sessions and webhook-based activation
- **Email**: Queue-based HTML email notifications via TenantLifecycleNotifier service

### Core Components

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Web Framework** | Laravel 12 | Routing, ORM, middleware, event handling |
| **Database** | MySQL | Tenant-isolated data storage with centralized app DB |
| **Authentication** | Laravel Breeze + Spatie | Role-based access control |
| **Frontend** | Blade + Tailwind CSS | Responsive UI for all user roles |
| **Billing** | PayMongo + Laravel Cashier | Subscription checkout and payment processing |
| **Email Service** | Notification queuing | Async customer/manager notifications |
| **Real-Time Features** | Google Calendar Sync | Appointment synchronization |
| **Version Control** | Git/GitHub | Source control and release management |

### Architecture Layers

```
┌─────────────────────────────────────────────────────┐
│                    User Interfaces                   │
│    (Blade Views + Tailwind CSS)                      │
│  - Platform Admin Dashboard                          │
│  - Barbershop Admin (Owner) Dashboard                │
│  - Branch Manager Operations                         │
│  - Barber Daily Workspace                            │
│  - Customer Booking Portal                           │
└─────────────────────────────────────────────────────┘
                         │
┌─────────────────────────────────────────────────────┐
│            HTTP Controllers & Routing                │
│         (RESTful API + Web Routes)                   │
│  - AdminTenantController                             │
│  - SubscriptionController                            │
│  - BookingController                                 │
│  - BarberDashboardController                         │
│  - ManagerDashboardController                        │
└─────────────────────────────────────────────────────┘
                         │
┌─────────────────────────────────────────────────────┐
│      Business Logic & Services                       │
│  - TenantLimitValidator                              │
│  - PayMongoCheckoutService                           │
│  - TenantLifecycleNotifier                           │
│  - PointsService                                     │
│  - GoogleCalendarSyncService                         │
└─────────────────────────────────────────────────────┘
                         │
┌─────────────────────────────────────────────────────┐
│      Middleware & Security                           │
│  - Authentication (Sanctum)                          │
│  - Authorization (Spatie Permission)                 │
│  - Tenant Scope Enforcement                          │
│  - Active Plan Gating                                │
│  - Dashboard Access Control                          │
└─────────────────────────────────────────────────────┘
                         │
┌─────────────────────────────────────────────────────┐
│       Data Layer (Eloquent ORM)                      │
│  - Tenant Model                                      │
│  - User Model with Roles                             │
│  - Appointment/Booking Model                         │
│  - Service, Branch, Schedule Models                  │
│  - PointTransaction Model                            │
└─────────────────────────────────────────────────────┘
                         │
┌─────────────────────────────────────────────────────┐
│           Databases                                  │
│  - Central App DB (PostgreSQL/MySQL)                 │
│  - Tenant-Specific DBs (MySQL - one per tenant)      │
└─────────────────────────────────────────────────────┘
```

---

## 3. Objectives

### Primary Goals

1. **Digitize Barbershop Operations**
   - Replace manual walk-in queues with intelligent digital scheduling
   - Reduce no-shows through automated reminders
   - Provide real-time slot availability to customers

2. **Enable Scalable Multi-Tenant Architecture**
   - Support single-location barbershops
   - Scale to multi-branch franchises without code changes
   - Provide tenant data isolation and security

3. **Implement Strict Role-Based Access Control (RBAC)**
   - Five user roles with distinct capabilities
   - Dashboard feature toggles for role-specific visibility
   - Branch-scoped operations for managers

4. **Automate Billing and Subscription Lifecycle**
   - Checkout workflow with multiple payment methods (GCash, Maya, Card)
   - Webhook-based subscription activation as source of truth
   - Plan-based resource limits (branches, barbers)

5. **Track Staff Performance and Incentives**
   - Points-based system for barber productivity
   - Loyalty tracking for customer bookings
   - Punctuality rewards and rebooking incentives

6. **Ensure Professional Communication**
   - Appointment confirmation emails
   - Late/no-show notifications to customers and managers
   - Emergency absence decision workflows with customer consent
   - System update notifications

---

## 4. Scope and Limitations

### In-Scope Features

#### Tenant Management
- ✅ Create tenants with owner provisioning
- ✅ Status lifecycle (pending, active, inactive, suspended)
- ✅ Database provisioning and teardown
- ✅ Tenant suspension/reactivation with notification

#### Subscription & Billing
- ✅ Four pricing tiers (Starter, Professional, Business, Enterprise)
- ✅ PayMongo checkout with multiple payment methods
- ✅ Plan-based resource limits (branches, barbers)
- ✅ Webhook-based activation as source of truth
- ✅ Subscription status management

#### Appointments & Booking
- ✅ Real-time slot generation with 15-minute increments
- ✅ Service-duration-based overlap detection
- ✅ Customer booking create/cancel/reschedule
- ✅ Barber appointment status progression
- ✅ Attendance tracking (arrived, late, no-show)
- ✅ Automatic late-marking after 10-minute grace period
- ✅ Emergency barber absence workflow with customer consent
- ✅ Overlap protection (barber cannot start if collision with next appointment)

#### Schedule Management
- ✅ Weekly recurring barber schedules
- ✅ Date-specific overrides for exceptions
- ✅ Emergency absence declarations
- ✅ Branch Manager control of barber schedules

#### Customer Features
- ✅ Service browsing
- ✅ Barber selection
- ✅ Appointment feedback and ratings (1-5 stars)
- ✅ Booking history and cancellation
- ✅ Points/loyalty view
- ✅ Emergency decision responses (accept reassignment, reschedule, cancel)

#### Barber & Manager Operations
- ✅ Daily dashboard with scheduled appointments
- ✅ Real-time queue monitoring (for managers)
- ✅ Appointment status updates (arrived, late, in-progress, completed, no-show)
- ✅ Walk-in recording
- ✅ Service management (create, update, delete)

#### Notifications & Communication
- ✅ Appointment confirmation emails
- ✅ Late/no-show status change notifications
- ✅ Auto-late grace period expiration notifications
- ✅ Emergency absence request emails to customers
- ✅ Manager alerts on booking status changes
- ✅ Credential resend for new users

#### System Features
- ✅ Multi-tenant data isolation
- ✅ Role-based access control (5 roles)
- ✅ Dashboard feature toggles per role
- ✅ Google Calendar sync for appointments
- ✅ Tenant domain/branding management
- ✅ System version tracking and release management
- ✅ Support ticket system

#### Version Control
- ✅ Semantic versioning (SemVer)
- ✅ Changelog tracking
- ✅ Release tagging on GitHub
- ✅ Incremental feature releases

### Out-of-Scope Features (Not Implemented)

- ❌ Annual billing (monthly subscriptions only)
- ❌ Advanced analytics and reporting
- ❌ Customer loyalty program (points view only)
- ❌ SMS notifications (email only)
- ❌ Mobile app (web-responsive only)
- ❌ Payment refund portal
- ❌ Advanced scheduling rules (vacation blocks, template schedules)
- ❌ Inventory management
- ❌ Product sales tracking
- ❌ Employee punch in/out time tracking
- ❌ Expense management

### Known Limitations

1. **Scalability**: Tenant database per-shop model may require database optimization for very large shops (1000+ appointments/month)

2. **Time Zone**: Currently configured for single time zone; multi-timezone support not implemented

3. **Payment Methods**: Limited to PayMongo-supported methods (GCash, Maya, Grab Pay, Card)

4. **Email Delivery**: Depends on configured mail service; no SMS as fallback

5. **Concurrent Bookings**: No real-time slot locking during checkout (optimistic concurrency only)

6. **Barber Substitution**: Emergency absence workflow supports manual reassignment proposal only; no auto-matching algorithm

---

## 5. System Architecture

### 5.1 Multi-Tenant Architecture

The system uses a **database-per-tenant** strategy for multi-tenancy:

```
Central Application Database
├── Tenants (master registry)
├── Users (admin user credentials)
├── Subscriptions (billing records)
├── TenantReleases (version tracking)
└── SupportTickets (system support)

Tenant Database 1 (Barbershop A)
├── Branches
├── Users (barbers, managers, customers)
├── Services
├── Schedules & ScheduleOverrides
├── Appointments (Bookings)
├── PointTransactions
└── CustomerFeedback

Tenant Database 2 (Barbershop B)
├── Branches
├── Users
├── Services
├── Schedules & ScheduleOverrides
├── Appointments
├── PointTransactions
└── CustomerFeedback
```

### 5.2 Request Flow for Multi-Tenant

1. **Request arrives** with tenant domain (barbershop-a.local)
2. **TenantMiddleware** resolves domain to tenant ID
3. **Tenant connection** is established to that tenant's database
4. **Controller processes** with tenant-scoped queries (UsesTenantConnection trait)
5. **Response returned** with tenant-isolated data only

### 5.3 Security Boundaries

```
┌─ Platform Admin ─────────────────────────────────┐
│ Can manage all tenants, view system metrics      │
│ Access: /admin/* routes                          │
└─────────────────────────────────────────────────┘

├─ Barbershop Admin (Owner) ──────────────────────┐
│ Manages one tenant, creates billing plans,      │
│ adds branches/barbers/managers                   │
│ Access: /manager/* (tenant-scoped)               │
├────────────────────────────────────────────────┘

│  ├─ Branch Manager ──────────────────────────┐
│  │ Manages one branch, queue, schedules,     │
│  │ can see barbers assigned to own branch    │
│  │ Access: /manager/queue, /manager/schedules│
│  └────────────────────────────────────────────┘

│  ├─ Barber ──────────────────────────────────┐
│  │ Views own daily schedule, updates own     │
│  │ appointment status, sees own points       │
│  │ Access: /barber/* routes                  │
│  └────────────────────────────────────────────┘

└─ Customer ────────────────────────────────────┐
  Views services, books appointments,            │
  submits feedback, reschedules own bookings    │
  Access: /customer/* routes                    │
└─────────────────────────────────────────────┘
```

### 5.4 Data Flow: Booking Creation

```
Customer Submits Form
        ↓
POST /customer/book (BookingController@store)
        ↓
Validate: Service exists, Barber belongs to tenant, Time available
        ↓
Check Overlap: availableSlotsForDate() checks for conflicts
        ↓
Create Appointment (status=queued, booked_at=now)
        ↓
Dispatch AppointmentCreatedEvent
        ↓
├─→ SendAppointmentConfirmationEmail (via Queue)
├─→ SyncAppointmentToGoogleCalendar (via Queue)
└─→ AwardServicePoints (reward for booking)
        ↓
Return Success + Appointment Details
```

### 5.5 Data Flow: Late Marking

```
Barber Opens Dashboard
        ↓
BarberDashboardController@index
        ↓
Fetch Appointments: WHERE appointment_datetime < now() AND status='queued'
        ↓
Check Grace Period: COMPARE arrived_at IS NULL AND DIFFERENCE > 10 minutes
        ↓
For Each Expired Booking:
  ├─ Update: status='late', late_marked_at=now()
  ├─ Dispatch: notifyAttendanceStatusChange(booking, 'late')
  │   ├─ Email Customer: "Your appointment was marked late"
  │   └─ Email Managers: "Appointment {id} marked late by system"
  └─ Award Points: No points for late appointments
        ↓
Display Updated Dashboard to Barber
```

---

## 6. Multi-Tenancy Design

### 6.1 Tenant Isolation Strategy

**Principle**: Complete data and operational isolation between tenants

#### Database Isolation
- **Central DB**: Stores only tenant registry, platform admin users, subscription records
- **Tenant DB**: Each barbershop has own MySQL database with branches, barbers, appointments, services
- **Connection Switching**: Middleware switches Laravel connection based on domain

#### Query Scope Enforcement
```php
// Automatic tenant filtering via trait
use UsesTenantConnection;

class Appointment extends Model {
    // All queries automatically filtered by tenant_id
    $appointments = Appointment::where('status', 'completed')->get();
    // Becomes: SELECT * FROM appointments WHERE tenant_id = ? AND status = 'completed'
}
```

#### Authorization Enforcement
```php
// Example: Barber cannot view another barber's appointments
Route::patch('/barber/appointments/{booking}/status', function (Appointment $booking) {
    // Middleware enforces: $booking->barber_id === Auth::user()->id
    // Middleware enforces: $booking->tenant_id === current_tenant()->id
});
```

### 6.2 Tenant Lifecycle

```
1. PENDING
   ├─ Created by Platform Admin
   ├─ Owner credentials sent via email
   └─ Database not yet provisioned

2. ACTIVE
   ├─ Subscription activated via PayMongo webhook
   ├─ Database provisioned with migrations
   ├─ Roles and permissions seeded
   └─ All features available

3. INACTIVE
   ├─ Owner stops using system
   ├─ No active subscription
   └─ Data preserved but access blocked

4. SUSPENDED
   ├─ Platform Admin manually suspended (abuse, non-payment)
   ├─ All feature access blocked
   └─ Requires manual reactivation
```

### 6.3 Branch Structure (Multi-Location Support)

```
Tenant (Barbershop: "Elite Cuts")
├─ Branch 1: Main Location
│  ├─ Barber A
│  ├─ Barber B
│  └─ Branch Manager
├─ Branch 2: Downtown
│  ├─ Barber C
│  ├─ Barber D
│  └─ Branch Manager
└─ Branch 3: Airport
   ├─ Barber E
   └─ Branch Manager
```

**Plan Limits**:
- Starter: 1 branch, 2 barbers
- Professional: 1 branch, 5 barbers
- Business: 3 branches, unlimited barbers
- Enterprise: Unlimited branches and barbers

---

## 7. Database Design

### 7.1 Central Application Database Schema

```sql
-- Primary tenant registry
Table: tenants
├── id (string, PK)
├── name
├── status (pending|active|inactive|suspended)
├── plan_tier (starter|professional|business|enterprise)
├── primary_domain
├── database_name (tenant's isolated DB)
├── owner_user_id (FK → users.id)
├── brand_color, logo_path
├── dashboard_access_settings (JSON)
├── created_at, updated_at

-- Platform admin users only
Table: users (central only, platform admin)
├── id (PK)
├── name, email, password
├── email_verified_at
├── created_at

-- Subscription billing records
Table: subscriptions
├── id (PK)
├── tenant_id (FK → tenants.id)
├── stripe/paymongo reference
├── plan_tier
├── status (active|cancelled|pending)
├── activated_at, cancelled_at
├── created_at

-- Version/release tracking
Table: tenant_releases
├── id (PK)
├── version
├── changelog
├── status (draft|published)
└── created_at

-- Support tickets
Table: support_tickets
├── id (PK)
├── tenant_id (FK)
├── title, description
├── status (open|in_progress|resolved)
└── created_at
```

### 7.2 Tenant Database Schema (per barbershop)

```sql
-- Multi-location support
Table: branches
├── id (PK)
├── tenant_id (FK)
├── name
├── address, phone
├── manager_id (FK → users.id, nullable)
└── created_at

-- User accounts (barbers, managers, customers for this tenant)
Table: users
├── id (PK)
├── tenant_id (FK)
├── name, email, phone
├── password_hash
├── role (barber|manager|customer|admin)
├── branch_id (FK, nullable, for barbers/managers)
├── email_verified_at
├── created_at

-- Barber roles/permissions
Table: role_has_permissions (Spatie)
├── permission_id (FK)
├── role_id (FK)

-- Services offered
Table: services
├── id (PK)
├── tenant_id (FK)
├── name (e.g., "Classic Haircut")
├── description
├── duration_minutes (60, 90, 120)
├── base_price
├── sort_order
├── is_active
├── deleted_at (soft delete)
└── created_at

-- Weekly recurring schedules
Table: schedules
├── id (PK)
├── tenant_id (FK)
├── barber_id (FK → users.id)
├── day_of_week (0-6, Mon-Sun)
├── start_time (HH:MM)
├── end_time (HH:MM)
└── created_at

-- Date-specific exceptions to schedule
Table: schedule_overrides
├── id (PK)
├── tenant_id (FK)
├── barber_id (FK)
├── date (YYYY-MM-DD)
├── override_type (closed|half_day|extended)
├── start_time, end_time (nullable)
└── created_at

-- Appointments/Bookings (core business data)
Table: appointments (aliased as Booking)
├── id (PK)
├── tenant_id (FK)
├── customer_id (FK → users.id)
├── barber_id (FK → users.id)
├── service_id (FK)
├── appointment_datetime (YYYY-MM-DD HH:MM)
├── booked_at (YYYY-MM-DD HH:MM, when customer booked)
├── status (queued|confirmed|arrived|late|in_progress|completed|no_show|cancelled)
├── staff_id (FK, nullable, who served, for walk-ins)
├── notes
├── completed_at (nullable)
├── -- ATTENDANCE TRACKING
├── arrived_at (nullable)
├── late_marked_at (nullable)
├── no_show_marked_at (nullable)
├── -- EMERGENCY ABSENCE FIELDS
├── requires_customer_decision (boolean)
├── proposed_replacement_barber_id (FK, nullable)
├── customer_decision_due_at (nullable)
├── emergency_reason (nullable)
├── created_at, updated_at

-- Points/loyalty system
Table: point_transactions
├── id (PK)
├── tenant_id (FK)
├── barber_id (FK)
├── customer_id (FK)
├── transaction_type (service|rating|punctuality|rebooking)
├── points_awarded
├── related_booking_id (FK, nullable)
├── notes
├── created_at

-- Customer feedback on completed appointments
Table: customer_feedbacks
├── id (PK)
├── tenant_id (FK)
├── booking_id (FK)
├── customer_id (FK)
├── rating (1-5 stars)
├── comment
├── created_at
```

### 7.3 Key Relationships

```
Tenant
├─ hasMany(branches)
├─ hasMany(services)
├─ hasMany(appointments)
├─ hasMany(schedules)
└─ hasMany(users)

Barber (User)
├─ hasMany(appointments_assigned_to)
├─ hasMany(schedules)
├─ belongsTo(branch)
├─ hasMany(point_transactions)
└─ hasMany(appointments_served)

Customer (User)
├─ hasMany(appointments)
├─ hasMany(feedback)
├─ hasMany(point_transactions)
└─ hasMany(bookings)

Service
├─ hasMany(appointments)
└─ belongsTo(tenant)

Appointment
├─ belongsTo(customer)
├─ belongsTo(barber)
├─ belongsTo(service)
├─ belongsTo(proposed_replacement_barber)
├─ hasMany(feedback)
└─ hasMany(point_transactions)
```

---

## 8. System Features

### 8.1 Customer Features

| Feature | Description | Status |
|---------|-------------|--------|
| **Browse Services** | View available services with durations and prices | ✅ |
| **Search Barbers** | Filter barbers by specialty (if available) | ✅ |
| **Real-Time Slot Selection** | Select appointment time from available 15-min slots | ✅ |
| **Booking Confirmation** | Instant confirmation email with appointment details | ✅ |
| **Appointment History** | View past, current, and future bookings | ✅ |
| **Cancel Booking** | Cancel up to X hours before appointment | ✅ |
| **Reschedule Booking** | Move appointment to different time/barber | ✅ |
| **Rate & Review** | Submit 1-5 star rating and written feedback | ✅ |
| **Loyalty Points** | Track points earned from bookings and referrals | ✅ |
| **Emergency Decision** | Accept reassignment, reschedule, or cancel if barber unavailable | ✅ |
| **Notifications** | Email on booking confirmation, late status, cancellation | ✅ |
| **Profile Management** | Edit phone, preferences, emergency contact | ✅ |

### 8.2 Barber Features

| Feature | Description | Status |
|---------|-------------|--------|
| **Daily Dashboard** | View today's schedule with all appointments | ✅ |
| **Weekly Schedule** | See full week's schedule | ✅ |
| **Mark Arrived** | Check in customer as arrived at appointment time | ✅ |
| **Mark Late** | Mark self as late (auto-marked after 10 min grace) | ✅ |
| **Start Service** | Mark appointment in-progress | ✅ |
| **Complete Service** | Mark appointment completed, earn points | ✅ |
| **Mark No-Show** | Mark customer as no-show | ✅ |
| **Appointment Details** | View customer name, service, notes, duration | ✅ |
| **Points Tracker** | View earned points from services, ratings, punctuality | ✅ |
| **Performance Metrics** | See total bookings, average rating | 🔄 (Partial) |

### 8.3 Branch Manager Features

| Feature | Description | Status |
|---------|-------------|--------|
| **Queue Monitoring** | Real-time view of all appointments in branch | ✅ |
| **Appointment Status** | Update appointment status from queue | ✅ |
| **Service Management** | Create, edit, delete services for branch | ✅ |
| **Barber Management** | Hire/fire barbers, assign to branch | ✅ |
| **Schedule Management** | Create weekly schedules, set exceptions | ✅ |
| **Emergency Absence** | Declare barber emergency absence, request customer decisions | ✅ |
| **Walk-In Recording** | Record completed work for walk-in customers | ✅ |
| **Branch Reports** | View branch metrics and performance | 🔄 (Partial) |

### 8.4 Barbershop Admin (Owner) Features

| Feature | Description | Status |
|---------|-------------|--------|
| **Billing Plans** | Select and pay for subscription tier | ✅ |
| **Branch Management** | Create and manage multiple locations | ✅ |
| **Barber Management** | Create barber accounts, assign to branches | ✅ |
| **Manager Assignment** | Assign managers to branches | ✅ |
| **Service Templates** | Create service categories and pricing | ✅ |
| **Dashboard Access** | Toggle manager and barber feature visibility | ✅ |
| **Domain Setup** | Configure custom domain or subdomain | ✅ |
| **Branding** | Upload logo, set brand colors | ✅ |
| **User Management** | Create, invite, reset passwords for staff | ✅ |
| **Tenant Reports** | View overall business metrics | 🔄 (Partial) |

### 8.5 Platform Admin Features

| Feature | Description | Status |
|---------|-------------|--------|
| **Tenant Creation** | Create new barbershop tenant + owner account | ✅ |
| **Tenant Status** | Activate, suspend, deactivate tenants | ✅ |
| **Plan Tier Management** | Upgrade/downgrade subscription tiers | ✅ |
| **Database Provisioning** | Manually provision tenant database | ✅ |
| **Resend Credentials** | Resend owner login credentials | ✅ |
| **System Releases** | Manage version releases, publish updates | ✅ |
| **Support Tickets** | View and respond to support requests | ✅ |
| **System Metrics** | View total tenants, active subscriptions | 🔄 (Partial) |

### 8.6 Core Platform Features

| Feature | Description | Status |
|---------|-------------|--------|
| **Multi-Tenancy** | Complete data isolation between barbershops | ✅ |
| **Role-Based Access** | Five user roles with distinct permissions | ✅ |
| **Appointment Scheduling** | 15-minute slot increments with duration-based conflicts | ✅ |
| **Service Duration** | Services with configurable 30/60/90/120 min durations | ✅ |
| **Real-Time Availability** | Live slot checking, overlap detection | ✅ |
| **Attendance Tracking** | Track arrived, late, no-show status | ✅ |
| **Auto-Late Marking** | Automatic marking after 10-minute grace period | ✅ |
| **Overlap Protection** | Barber cannot start if collision with next appointment | ✅ |
| **Points System** | 5-tier points for service, rating, punctuality, rebooking | ✅ |
| **Google Calendar Sync** | Sync appointments to customer's Google Calendar | ✅ |
| **Email Notifications** | Async queue-based notifications | ✅ |
| **PayMongo Billing** | Checkout sessions with multiple payment methods | ✅ |
| **Subscription Activation** | Webhook-driven activation | ✅ |
| **Version Control** | Semantic versioning, changelog tracking | ✅ |

---

## 9. User Roles and Permissions (RBAC)

### 9.1 Role Hierarchy

```
SYSTEM ROLES
│
├─ Platform Admin
│  ├─ Can manage all tenants
│  ├─ Can create tenants
│  ├─ Can suspend/activate tenants
│  └─ Access: /admin/*
│
├─ Barbershop Admin (Owner)
│  ├─ Manages one tenant
│  ├─ Can create branches
│  ├─ Can manage billing
│  ├─ Can assign managers
│  └─ Access: /manager/* (tenant-scoped)
│
├─ Branch Manager
│  ├─ Manages one branch
│  ├─ Cannot modify other branches
│  ├─ Can manage queue and schedules
│  ├─ Can create walk-ins
│  └─ Access: /manager/queue, /manager/schedules
│
├─ Barber
│  ├─ Views own schedule only
│  ├─ Updates own appointments only
│  ├─ Cannot see other barbers' appointments
│  └─ Access: /barber/*
│
└─ Customer
   ├─ Can browse services
   ├─ Can create own bookings
   ├─ Can reschedule own bookings
   ├─ Can view own booking history
   └─ Access: /customer/*
```

### 9.2 Permission Matrix

| Action | Platform Admin | Barbershop Admin | Branch Manager | Barber | Customer |
|--------|---|---|---|---|---|
| Create Tenant | ✅ | ❌ | ❌ | ❌ | ❌ |
| Suspend Tenant | ✅ | ❌ | ❌ | ❌ | ❌ |
| Manage Billing | ❌ | ✅ | ❌ | ❌ | ❌ |
| Create Branch | ❌ | ✅ | ❌ | ❌ | ❌ |
| Assign Branch Manager | ❌ | ✅ | ❌ | ❌ | ❌ |
| Create Service | ❌ | ✅* | ✅ | ❌ | ❌ |
| Manage Queue | ❌ | ✅* | ✅ | ❌ | ❌ |
| Create Schedule | ❌ | ✅* | ✅ | ❌ | ❌ |
| Create Barber | ❌ | ✅ | ❌ | ❌ | ❌ |
| Update Appointment Status | ❌ | ✅* | ✅ | ✅ (own) | ❌ |
| Create Booking | ❌ | ❌ | ❌ | ❌ | ✅ |
| Cancel Booking | ❌ | ❌ | ❌ | ❌ | ✅ (own) |
| Reschedule Booking | ❌ | ❌ | ❌ | ❌ | ✅ (own) |
| Submit Feedback | ❌ | ❌ | ❌ | ❌ | ✅ |
| View All Appointments | ✅ | ✅ | ✅ (branch) | ✅ (own) | ❌ |

*Branch Manager can only manage resources in assigned branch

### 9.3 Feature Toggles (Dashboard Access)

Platform Admin can enable/disable features per role:

```json
{
  "branch_manager": {
    "manage_services": true,
    "manage_queue": true,
    "manage_barbers": true,
    "manage_schedules": true,
    "record_walkins": true
  },
  "barber": {
    "view_dashboard": true,
    "update_appointment_status": true
  }
}
```

---

## 10. Pricing Model

### 10.1 Subscription Tiers

| Tier | Monthly Price (PHP) | Branches | Barbers | Features |
|------|-----|---------|---------|----------|
| **Starter** | 499 | 1 | 2 | Basic booking, queue, 1 manager |
| **Professional** | 1,299 | 1 | 5 | All Starter + advanced reports, schedule management |
| **Business** | 2,499 | 3 | Unlimited | All Professional + multi-branch, advanced analytics |
| **Enterprise** | 4,999 | Unlimited | Unlimited | All Business + custom integrations, priority support |

### 10.2 Feature Comparison

| Feature | Starter | Professional | Business | Enterprise |
|---------|---------|--------------|----------|-----------|
| Customer Bookings | ✅ | ✅ | ✅ | ✅ |
| Appointment Scheduling | ✅ | ✅ | ✅ | ✅ |
| Queue Management | ✅ | ✅ | ✅ | ✅ |
| Points System | ✅ | ✅ | ✅ | ✅ |
| Google Calendar Sync | ✅ | ✅ | ✅ | ✅ |
| Email Notifications | ✅ | ✅ | ✅ | ✅ |
| Basic Reports | ❌ | ✅ | ✅ | ✅ |
| Advanced Analytics | ❌ | ❌ | ✅ | ✅ |
| Custom Branding | ❌ | ✅ | ✅ | ✅ |
| Multi-Branch | ❌ | ❌ | ✅ | ✅ |
| Priority Support | ❌ | ❌ | ❌ | ✅ |

### 10.3 Resource Limits Enforcement

```php
// TenantLimitValidator.php
class TenantLimitValidator {
    
    public function canCreateBranch(Tenant $tenant): bool {
        $limit = [
            'starter' => 1,
            'professional' => 1,
            'business' => 3,
            'enterprise' => PHP_INT_MAX,
        ];
        
        $current = $tenant->branches()->count();
        return $current < $limit[$tenant->plan_tier];
    }
    
    public function canCreateBarber(Tenant $tenant): bool {
        $limit = [
            'starter' => 2,
            'professional' => 5,
            'business' => PHP_INT_MAX,
            'enterprise' => PHP_INT_MAX,
        ];
        
        $current = $tenant->users()->barbers()->count();
        return $current < $limit[$tenant->plan_tier];
    }
}
```

### 10.4 Billing Flow

```
1. Owner Clicks "Upgrade" Button
        ↓
2. Select Desired Tier (Starter/Professional/Business/Enterprise)
        ↓
3. Redirect to PayMongo Checkout
        ↓
4. Customer Completes Payment (GCash/Maya/Card/Grab Pay)
        ↓
5. PayMongo Webhook: checkout_session.payment.paid
        ↓
6. System Updates:
   ├─ Create Subscription Record
   ├─ Update Tenant.plan_tier
   ├─ Provision Database (if first time)
   └─ Set Tenant.status = active
        ↓
7. Tenant Gets Access to All Features
```

---

## 11. Security Implementation

### 11.1 Authentication

**Method**: Laravel Breeze (session-based)
- Email + password authentication
- Password hashing with Bcrypt
- Email verification required
- Session timeout after inactivity
- CSRF token protection on all forms

### 11.2 Authorization

**Method**: Spatie Permission + Custom Middleware
- Role-based access control (RBAC)
- Five user roles with granular permissions
- Middleware checks role before route execution
- Dashboard feature toggles for role-specific visibility
- Policy classes for resource-level authorization

```php
// Example authorization check
Route::patch('/manager/services/{service}', function (Service $service) {
    authorize('update', $service); // Checks service belongs to user's tenant
});
```

### 11.3 Tenant Isolation

**Method**: Database-per-tenant + Middleware + Trait
- Each tenant has isolated MySQL database
- Middleware resolves domain → tenant connection
- UsesTenantConnection trait filters all queries
- Foreign key constraints prevent cross-tenant access
- Scope guards on sensitive updates

```php
// Automatic tenant filtering
$appointments = Appointment::all(); // Automatically scoped to current tenant
```

### 11.4 API Security

**Methods**:
- ✅ HTTPS only (enforced in production)
- ✅ CSRF token validation on state-changing requests
- ✅ Rate limiting (can be added via middleware)
- ✅ Input validation and sanitization
- ✅ SQL injection prevention (Eloquent parameterized queries)
- ✅ XSS prevention (Blade escaping)
- ✅ CORS headers (configured in Laravel)

### 11.5 Data Protection

**Methods**:
- ✅ Passwords hashed with Bcrypt (Laravel default)
- ✅ Sensitive data not logged
- ✅ Database backups (responsibility of hosting)
- ✅ Soft deletes for audit trail
- ✅ Encrypted cookies for session storage

### 11.6 Payment Security

**Methods**:
- ✅ PayMongo handles PCI compliance
- ✅ No credit card data stored locally
- ✅ Webhook token validation for PayMongo events
- ✅ Webhook signature verification (can be added)
- ✅ Idempotent webhook processing

### 11.7 Third-Party Integrations

**Google Calendar**:
- OAuth 2.0 authentication
- Tokens stored encrypted in database (Spatie package)
- Automatic refresh token rotation

**PayMongo**:
- API key-based authentication
- Webhook token validation
- HTTPS-only connections

---

## 12. API Documentation

### 12.1 Authentication Endpoints

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/api/auth/login` | ❌ | User login (returns session cookie) |
| POST | `/api/auth/logout` | ✅ | User logout |
| POST | `/api/auth/register` | ❌ | Customer registration |
| GET | `/api/auth/me` | ✅ | Get current user profile |

### 12.2 Customer Booking Endpoints

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/customer/services` | ✅ | List services |
| GET | `/customer/bookings` | ✅ | List customer's bookings |
| GET | `/customer/bookings/create` | ✅ | Show booking form |
| POST | `/customer/book` | ✅ | Create booking |
| DELETE | `/customer/bookings/{id}/cancel` | ✅ | Cancel booking |
| PATCH | `/customer/bookings/{id}/reschedule` | ✅ | Reschedule booking |
| POST | `/customer/bookings/{id}/feedback` | ✅ | Submit feedback |
| POST | `/customer/bookings/{id}/decision` | ✅ | Respond to emergency decision |
| GET | `/customer/points` | ✅ | View loyalty points |

### 12.3 Barber Dashboard Endpoints

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/barber` | ✅ | Barber dashboard (today's appointments) |
| POST | `/barber/appointments/{id}/status` | ✅ | Update appointment status (arrived/late/completed/no-show) |

### 12.4 Branch Manager Endpoints

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/manager/queue` | ✅ | Queue view (all appointments in branch) |
| POST | `/manager/queue/{id}/status` | ✅ | Update appointment status from queue |
| GET | `/manager/schedules` | ✅ | View barber schedules |
| POST | `/manager/schedules` | ✅ | Create weekly schedule |
| DELETE | `/manager/schedules/{id}` | ✅ | Delete schedule |
| POST | `/manager/schedules/overrides` | ✅ | Create schedule exception |
| DELETE | `/manager/schedules/overrides/{id}` | ✅ | Delete override |
| POST | `/manager/schedules/emergency-absence` | ✅ | Declare barber emergency absence |
| PATCH | `/manager/schedules/emergency-bookings/{id}/request` | ✅ | Request customer decision |
| PATCH | `/manager/schedules/emergency-bookings/request-all` | ✅ | Bulk request customer decisions |
| GET | `/manager/services` | ✅ | List services |
| POST | `/manager/services` | ✅ | Create service |
| PATCH | `/manager/services/{id}` | ✅ | Update service |
| DELETE | `/manager/services/{id}` | ✅ | Delete service |
| GET | `/manager/barbers` | ✅ | List barbers |
| POST | `/manager/barbers` | ✅ | Create barber |
| PATCH | `/manager/barbers/{id}` | ✅ | Update barber |
| DELETE | `/manager/barbers/{id}` | ✅ | Delete barber |
| POST | `/manager/walk-ins` | ✅ | Record walk-in |

### 12.5 Barbershop Admin Endpoints

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/billing/plans` | ✅ | List subscription plans |
| POST | `/billing/{tenant}/checkout/starter` | ✅ | Initiate Starter tier checkout |
| POST | `/billing/{tenant}/checkout/professional` | ✅ | Initiate Professional tier checkout |
| POST | `/billing/{tenant}/checkout/business` | ✅ | Initiate Business tier checkout |
| POST | `/billing/{tenant}/checkout/enterprise` | ✅ | Initiate Enterprise tier checkout |
| GET | `/billing/success` | ✅ | Success page after payment |
| GET | `/billing/cancel` | ✅ | Cancel page if payment cancelled |
| GET | `/manager` | ✅ | Barbershop admin dashboard |
| POST | `/manager/users` | ✅ | Create managed user (barber/manager) |
| PATCH | `/manager/users/{id}/password` | ✅ | Reset user password |
| DELETE | `/manager/users/{id}` | ✅ | Delete user |
| GET | `/manager/branches` | ✅ | List branches |
| POST | `/manager/branches` | ✅ | Create branch |
| PATCH | `/manager/branches/{id}` | ✅ | Update branch |
| DELETE | `/manager/branches/{id}` | ✅ | Delete branch |
| POST | `/manager/branches/{id}/manager` | ✅ | Assign manager to branch |
| PATCH | `/manager/domain` | ✅ | Update tenant domain |
| PATCH | `/manager/appearance` | ✅ | Update tenant branding |

### 12.6 Platform Admin Endpoints

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/admin` | ✅ | Platform admin dashboard |
| POST | `/admin/tenants` | ✅ | Create new tenant |
| PATCH | `/admin/tenants/{id}` | ✅ | Update tenant details |
| POST | `/admin/tenants/{id}/suspend` | ✅ | Suspend tenant access |
| POST | `/admin/tenants/{id}/resend-credentials` | ✅ | Resend owner login credentials |
| POST | `/admin/tenants/{id}/provision-database` | ✅ | Manually provision database |
| GET | `/admin/customer-dashboard` | ✅ | View system from customer perspective |

### 12.7 Webhook Endpoints

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| POST | `/paymongo/webhook` | Token | PayMongo payment event webhook |
| GET/HEAD | `/paymongo/webhook` | ❌ | Health check endpoint |

---

## 13. System Screenshots & UI Overview

### 13.1 Key User Interfaces (Descriptions)

#### Customer Portal
- **Landing Page**: Browse services, see promotional hero image, CTA buttons
- **Booking Form**: Calendar date picker, barber selector, time slot grid (15-min increments)
- **Booking Confirmation**: Appointment details, QR code check-in, cancellation link
- **Booking History**: Timeline view of past, current, upcoming appointments
- **Rating Modal**: 5-star rating input, text feedback submission
- **Emergency Decision Panel**: Options to accept reassignment, reschedule, or cancel

#### Barber Dashboard
- **Daily View**: Today's appointments in chronological order
- **Appointment Card**: Customer name, service, duration, start/end time, status badges
- **Action Buttons**: Arrived, Late, Start, Finish, No-Show buttons (context-sensitive)
- **Weekly Schedule**: Mini calendar showing next 7 days' appointments
- **Points Summary**: Earned points breakdown and total

#### Branch Manager Queue
- **Real-Time Queue**: All appointments in branch with status indicators
- **Status Legend**: Color-coded badges (queued, confirmed, arrived, late, in_progress, completed, no_show)
- **Bulk Actions**: Select multiple appointments, request emergency decisions
- **Schedule Management**: Weekly schedule grid editor for each barber
- **Service List**: Active services with pricing and duration

#### Barbershop Admin Dashboard
- **Overview Cards**: Active subscriptions, total appointments, branch count
- **Branch Management**: List of branches with manager assignments
- **Billing Section**: Current plan tier, upgrade button, payment history
- **User Management**: Create barbers, managers, reset passwords
- **Setup Wizard**: Initial domain, branding, services configuration

#### Platform Admin Dashboard
- **Tenant Registry**: All barbershops with status, plan tier, owner
- **Create Tenant Modal**: New barbershop provisioning form
- **Support Tickets**: Open tickets from barbershop owners
- **System Metrics**: Total active tenants, revenue, platform health
- **Release Management**: Version tracking, deployment status

---

## 14. Development Documentation

### 14.1 Technology Stack

```
Backend:
- PHP 8.2+
- Laravel 12.x
- MySQL 8.0+
- Composer for dependency management

Frontend:
- Blade templating engine
- Tailwind CSS for styling
- Alpine.js for interactivity
- Vite for asset bundling

Services:
- PayMongo for billing/payments
- Google Calendar API for sync
- Laravel Queue for async jobs
- Mail service for notifications

Testing:
- PHPUnit for unit/feature tests
- Faker for test data generation
- Mockery for mocking

Version Control:
- Git + GitHub
- Semantic versioning (SemVer)
- Release branching strategy
```

### 14.2 Project Structure

```
barbershop-saas/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminTenantController.php
│   │   │   ├── Customer/BookingController.php
│   │   │   ├── Manager/ScheduleController.php
│   │   │   ├── BarberDashboardController.php
│   │   │   └── ...
│   │   ├── Middleware/
│   │   │   ├── TenantScopeMiddleware.php
│   │   │   ├── EnsureTenantHasActivePlan.php
│   │   │   └── ...
│   │   └── Requests/
│   ├── Models/
│   │   ├── Tenant.php
│   │   ├── User.php
│   │   ├── Appointment.php
│   │   ├── Service.php
│   │   ├── Branch.php
│   │   ├── Schedule.php
│   │   └── ...
│   ├── Services/
│   │   ├── TenantProvisioningService.php
│   │   ├── TenantLimitValidator.php
│   │   ├── PayMongoCheckoutService.php
│   │   ├── GoogleCalendarSyncService.php
│   │   ├── PointsService.php
│   │   └── ...
│   ├── Events/
│   │   ├── AppointmentConfirmedEvent.php
│   │   ├── AppointmentCompleted.php
│   │   └── ...
│   ├── Listeners/
│   │   ├── SendAppointmentConfirmationEmail.php
│   │   ├── SyncAppointmentToGoogleCalendar.php
│   │   ├── AwardServicePoints.php
│   │   └── ...
│   ├── Mail/
│   │   ├── AppointmentConfirmed.php
│   │   ├── LateNotification.php
│   │   └── ...
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── ...
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── tenancy.php
│   ├── google-calendar.php
│   └── ...
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000000_create_users_table.php
│   │   ├── 2024_01_02_000000_create_tenants_table.php
│   │   └── ...
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   └── ...
│   └── factories/
│       ├── UserFactory.php
│       └── ...
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php
│   │   │   └── ...
│   │   ├── customer/
│   │   │   ├── bookings/
│   │   │   ├── dashboard.blade.php
│   │   │   └── ...
│   │   ├── barber/
│   │   │   └── dashboard.blade.php
│   │   ├── manager/
│   │   │   ├── queue/index.blade.php
│   │   │   ├── schedules/index.blade.php
│   │   │   └── ...
│   │   └── admin/
│   │       └── ...
│   ├── css/
│   │   ├── app.css
│   │   └── ...
│   └── js/
│       └── app.js
├── routes/
│   ├── web.php (main routes)
│   ├── auth.php (authentication routes)
│   └── console.php (artisan commands)
├── tests/
│   ├── Feature/
│   │   ├── CustomerBookingTest.php
│   │   └── ...
│   └── Unit/
│       └── ...
├── storage/
│   ├── app/ (local file storage)
│   ├── logs/ (application logs)
│   └── framework/ (Laravel cache/sessions)
├── .env (environment configuration)
├── .env.example (example configuration)
├── composer.json (PHP dependencies)
├── package.json (Node.js dependencies)
├── phpunit.xml (test configuration)
├── README.md (setup instructions)
├── VERSIONING.md (versioning policy)
├── CHANGELOG.md (release notes)
└── PROJECT_DOCUMENTATION.md (this file)
```

### 14.3 Key Development Concepts

#### Multi-Tenant Connection Switching

```php
// Middleware resolves domain to tenant, switches connection
Route::domain('{tenant}.barbershop.test')->group(function () {
    Route::get('/dashboard', function () {
        // Connection automatically switched to tenant's database
        $appointments = Appointment::all();
    });
});
```

#### Trait-Based Query Scoping

```php
use App\Support\Traits\UsesTenantConnection;

class Appointment extends Model {
    use UsesTenantConnection;
    
    // All queries automatically include tenant filtering
}

// Usage:
$bookings = Appointment::where('status', 'completed')->get();
// Becomes: SELECT * FROM appointments WHERE tenant_id = ? AND status = 'completed'
```

#### Event-Driven Notifications

```php
// Event fired after booking creation
event(new AppointmentConfirmedEvent($appointment));

// Listener dispatches email job
Listener::handle() {
    Mail::queue(new AppointmentConfirmed($appointment));
}
```

#### Permission-Based Features

```php
// Check if feature enabled for role
if ($tenant->dashboardFeatureEnabled('branch_manager', 'manage_services')) {
    // Show manage services UI
}
```

### 14.4 Common Development Tasks

#### Add New Subscription Tier

1. Add tier name to `config/services.php`
2. Create checkout route in `routes/web.php`
3. Create controller method in `SubscriptionController.php`
4. Update `TenantLimitValidator.php` with new limits
5. Add migration for new tier-specific settings (if needed)
6. Test checkout flow end-to-end

#### Add New Service

1. Create model in `app/Models/`
2. Create migration in `database/migrations/`
3. Create controller in `app/Http/Controllers/`
4. Add routes to `routes/web.php`
5. Create views in `resources/views/`
6. Add tests in `tests/Feature/`

#### Implement New Notification

1. Create Mail class in `app/Mail/`
2. Create Listener in `app/Listeners/` (if event-driven)
3. Register listener in `AppServiceProvider.php`
4. Add queue configuration to `.env`
5. Test notification content and delivery

---

## 15. Installation & Setup

### 15.1 Development Environment Setup

```bash
# 1. Clone repository
git clone https://github.com/Sciatzy/BarbershopSaas.git
cd barbershop-saas

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Copy environment file
cp .env.example .env

# 5. Generate app key
php artisan key:generate

# 6. Configure database in .env
# Set DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 7. Run migrations
php artisan migrate

# 8. Seed initial data
php artisan db:seed

# 9. Build frontend assets
npm run build
# Or for development with hot reload:
npm run dev

# 10. Start Laravel development server
php artisan serve

# Server runs at http://127.0.0.1:8000
```

### 15.2 PayMongo Webhook Setup

```bash
# 1. Install ngrok to expose local server
ngrok http 8000

# 2. Copy ngrok HTTPS URL (e.g., https://xxxx-xx-xx-xx-xx.ngrok-free.app)

# 3. Add to .env:
PAYMONGO_WEBHOOK_URL=https://xxxx-xx-xx-xx-xx.ngrok-free.app/paymongo/webhook

# 4. In PayMongo dashboard:
# - Add webhook endpoint: {PAYMONGO_WEBHOOK_URL}?token={PAYMONGO_WEBHOOK_TOKEN}
# - Subscribe to events: checkout_session.payment.paid, payment.paid

# 5. Test webhook delivery from PayMongo dashboard
```

---

## 16. Release Management

### 16.1 Current Release: v1.1.0

**Date**: April 27, 2026  
**Type**: MINOR (new feature)

**Features Added**:
- Automatic late-marking after 10-minute grace period
- Attendance tracking fields (arrived_at, late_marked_at, no_show_marked_at)
- Email notifications to customer + managers on late/no-show
- Barber dashboard auto-late detection
- Overlap protection on appointment starts

**Versioning Policy**:
- PATCH (x.x.+1): Bug fixes, UI updates
- MINOR (x.+1.0): New features, non-breaking API additions
- MAJOR (+1.0.0): Breaking changes, incompatible data models

### 16.2 Release Delivery Policy

The platform uses an opt-in SaaS rollout model:
- The platform admin fetches the latest release metadata.
- The platform admin sends the release to selected tenants or all active tenants.
- Tenant owners and branch managers decide whether to apply the update or hold it for later.
- No forced automatic update is applied to tenant dashboards.
- Reminders may be added later, but no update deadline is enforced.

### 16.3 Upcoming Releases

**v1.1.1** (Planned):
- Post-booking customer notification
- New appointment assigned notification to barber

**v1.2.0** (Planned):
- Manager decision-response tracking
- Expired customer decision auto-handling

---

## 17. Support & Contact

### Getting Help

- **Documentation**: See README.md, SYSTEM_OVERVIEW.md
- **Code Examples**: Check tests/ directory for usage patterns
- **Support Tickets**: Submit via `/manager/support-tickets` or admin panel
- **GitHub Issues**: Report bugs at https://github.com/Sciatzy/BarbershopSaas/issues

---

**Document Version**: 1.1.0  
**Last Updated**: April 27, 2026  
**Maintained By**: Development Team
