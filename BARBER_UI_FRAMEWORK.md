# Barber UI Framework (Task 2: RBAC-Aligned)

Purpose: provide a strict UI/UX blueprint for Barber-role screens so Gemini can generate a modern interface while preserving role permissions and tenant isolation.

## 1. Scope and Guardrails

This framework is for Barber role pages only.

Allowed role routes:
- `/barber` via `barber.dashboard`

RBAC/Middleware constraints to preserve:
- Must require: `auth`, `verified`, `role:Barber`, `active_plan`
- Must not expose manager/admin/customer actions
- Must keep tenant-scoped data only

Do not include in Barber UI:
- Billing controls
- Tenant status updates
- Barber account creation/deletion
- Customer-facing booking creation forms
- Platform-level analytics

## 2. Information Architecture (Barber Dashboard)

Top-level page sections:
1. Header row
- Title: Barber Dashboard
- Subtitle: Today overview and upcoming appointments
- Optional date chip and timezone chip

2. KPI card row (3 cards)
- Total Points
- Today Schedule Blocks
- Today Appointments

3. Two-column operational panels
- Left: Daily Schedule list
- Right: Today Appointments list

4. Optional action strip (future-ready)
- Refresh button
- Filter by status
- Quick jump to next appointment

## 3. Component System

### 3.1 KPI Cards
Use compact cards with:
- Label (xs, muted)
- Value (xl, semibold)
- Optional trend/support text (sm)

States:
- Default
- Loading (skeleton)
- Empty (`0` value shown normally)

### 3.2 Data Panels
Each panel must include:
- Panel header (title + optional context text)
- Content list with dividers
- Empty state tile if no entries

Schedule item shape:
- Start-end time
- Optional status badge (`Working`, `Break`)

Appointment item shape:
- Time + customer name
- Service name
- Status badge (`pending`, `confirmed`, `completed`, `cancelled`)

### 3.3 Badges
Badge variants:
- Pending: amber
- Confirmed: blue
- Completed: green
- Cancelled: red

### 3.4 Feedback Components
- Inline success alert (green)
- Inline error alert (red)
- Empty cards with icon + helper text
- Skeleton loaders for cards and list rows

## 4. Visual Design Tokens (Tailwind-Oriented)

Use this style language for consistency:
- Background: `bg-slate-50`
- Surfaces: `bg-white`
- Borders: `border-slate-200`
- Primary text: `text-slate-900`
- Secondary text: `text-slate-500`
- Primary accent: blue (`bg-blue-600`, `text-blue-600`)

Spacing and radius:
- Page spacing: `py-8`, `space-y-6`
- Card padding: `p-6`
- Radius: `rounded-xl` or `rounded-2xl`

Shadows:
- Cards: `shadow-sm`
- Hoverable cards: `hover:shadow-md` with smooth transition

Typography:
- Page title: `text-2xl font-bold`
- Section title: `text-lg font-semibold`
- KPI value: `text-2xl font-bold`

## 5. Responsive Behavior

Desktop:
- KPI row: 3 columns
- Panels: 2 columns

Tablet:
- KPI row: 2 columns then wrap
- Panels: single column if needed

Mobile:
- KPI row: 1 column
- Panels: 1 column
- Touch targets at least 40px height

## 6. Accessibility Requirements

- All interactive elements must have visible focus states
- Color contrast should pass WCAG AA
- Status must not rely on color only (text labels required)
- Use semantic headings (`h1`, `h2`, `h3`) and list markup
- Keyboard navigation order must match visual order

## 7. RBAC Acceptance Criteria (Task 2 Alignment)

A. Navigation and Actions
- Barber sees only barber-appropriate nav items
- No admin/manager actions visible on barber page

B. Route Protection
- Non-barber user attempting `/barber` is blocked by role middleware
- Barber with inactive plan is blocked by `active_plan`

C. Data Isolation
- Barber sees only own schedule and own appointments in own tenant
- No cross-tenant or other-barber data rendered

D. UI Integrity
- Empty states handled for schedule and appointments
- No broken sections when counts are zero

## 8. Suggested File Targets For Gemini

Primary page:
- `resources/views/barber/dashboard.blade.php`

Reusable layout/component candidates:
- `resources/views/layouts/app.blade.php`
- `resources/css/app.css`

Do not modify RBAC logic in these files unless asked:
- `routes/web.php`
- `app/Http/Controllers/BarberDashboardController.php`
- `app/Http/Middleware/TenantScopeMiddleware.php`

## 9. Gemini Prompt Pack

### Prompt A: Full Barber Dashboard UI Rewrite
"Rewrite `resources/views/barber/dashboard.blade.php` using a modern SaaS card-and-panel layout. Keep all existing Blade variables and loops unchanged. Preserve route/middleware assumptions. Implement 3 KPI cards, Daily Schedule panel, and Today Appointments panel with responsive behavior and empty states. Use Tailwind classes aligned to slate/blue palette and accessible focus states."

### Prompt B: Componentized Styling
"Create reusable utility/component classes in `resources/css/app.css` for barber dashboard cards, panel headers, badge variants, and list rows. Keep classes generic enough for manager/customer reuse later. Do not change business logic."

### Prompt C: RBAC Safety Check
"Before outputting UI code, verify no admin/manager/customer actions are introduced into the barber dashboard. Preserve only barber-safe display and read-only operational content."

## 10. Definition of Done

- Barber dashboard follows modern card-based structure
- Mobile/tablet/desktop layouts are clean
- Empty/loading/error states are present
- Status badges are readable and consistent
- RBAC boundaries remain unchanged
- Existing data bindings continue to work without controller changes
