# Customer Dashboard — Build Prompt
## Barbershop SaaS · Laravel Blade + Vanilla CSS/JS

---

## CONTEXT & CONSTRAINTS

You are working inside an **existing Laravel multi-tenant barbershop SaaS**.
The system already has:
- A Platform Admin dashboard at `/admin`
- A Manager/Barber dashboard at `/manager`
- Auth at `/login` with role-based redirect via `/dashboard`
- Customers are **authenticated Laravel users** with role `customer`
- After login, customers are redirected to `/booking` (update this to `/customer/dashboard`)
- All data is tenant-scoped via `tenant_id` on every table
- Tenant is resolved from `auth()->user()->tenant` (local dev, no subdomain)

**Do NOT break** any existing auth, middleware, billing, or tenant lifecycle logic.
**Do NOT** create a separate Customer model — customers are `User` records with role `customer`.

---

## ROUTING

Add these routes inside the existing `auth` + `role:customer` middleware group
in `routes/web.php`. Do not duplicate if they already exist:

```php
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard',           [Customer\DashboardController::class, 'index'])     ->name('dashboard');
    Route::get('/services',            [Customer\ServiceController::class, 'index'])        ->name('services');
    Route::get('/book/{service}',      [Customer\BookingController::class, 'create'])       ->name('book');
    Route::post('/book',               [Customer\BookingController::class, 'store'])        ->name('book.store');
    Route::get('/bookings',            [Customer\BookingController::class, 'index'])        ->name('bookings');
    Route::delete('/bookings/{booking}/cancel', [Customer\BookingController::class, 'cancel']) ->name('bookings.cancel');
    Route::get('/points',              [Customer\PointsController::class, 'index'])         ->name('points');
    Route::get('/profile',             [Customer\ProfileController::class, 'edit'])         ->name('profile');
    Route::put('/profile',             [Customer\ProfileController::class, 'update'])       ->name('profile.update');
    Route::get('/notifications',       [Customer\NotificationController::class, 'index'])   ->name('notifications');
});

// Update the post-login redirect for customers:
// In /dashboard redirect logic, change customer redirect from /booking to /customer/dashboard
```

---

## CONTROLLERS

### A. `app/Http/Controllers/Customer/DashboardController.php`

```php
public function index()
{
    $user   = auth()->user();
    $tenant = $user->tenant;

    // Recent bookings (last 5)
    $recentBookings = Booking::where('customer_id', $user->id)
        ->with(['service', 'staff'])
        ->latest('booked_at')
        ->take(5)
        ->get();

    // Upcoming / active booking
    $activeBooking = Booking::where('customer_id', $user->id)
        ->whereIn('status', ['queued', 'in_progress'])
        ->with(['service', 'staff'])
        ->latest('booked_at')
        ->first();

    // Points summary
    $pointsBalance  = $user->points_balance ?? 0;
    $totalEarned    = PointsLedger::where('customer_id', $user->id)
                        ->where('type', 'earn')->sum('points');
    $totalRedeemed  = abs(PointsLedger::where('customer_id', $user->id)
                        ->where('type', 'redeem')->sum('points'));

    // Visit count
    $totalVisits = Booking::where('customer_id', $user->id)
        ->where('status', 'completed')->count();

    // Featured services (active, limit 4)
    $services = Service::where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->take(4)
        ->get();

    // Leaderboard rank (customer position by points_balance)
    $rank = User::where('tenant_id', $tenant->id)
        ->whereHas('roles', fn($q) => $q->where('name', 'customer'))
        ->where('points_balance', '>', $pointsBalance)
        ->count() + 1;

    return view('customer.dashboard', compact(
        'user', 'tenant', 'recentBookings', 'activeBooking',
        'pointsBalance', 'totalEarned', 'totalRedeemed',
        'totalVisits', 'services', 'rank'
    ));
}
```

---

### B. `app/Http/Controllers/Customer/BookingController.php`

```php
public function index()
{
    $bookings = Booking::where('customer_id', auth()->id())
        ->with(['service', 'staff'])
        ->latest('booked_at')
        ->paginate(10);
    return view('customer.bookings.index', compact('bookings'));
}

public function create(Service $service)
{
    // Guard: service must belong to customer's tenant
    abort_if($service->tenant_id !== auth()->user()->tenant_id, 403);

    $barbers = User::where('tenant_id', auth()->user()->tenant_id)
        ->whereHas('roles', fn($q) => $q->where('name', 'barber'))
        ->where('is_active', true)
        ->get();

    return view('customer.bookings.create', compact('service', 'barbers'));
}

public function store(Request $request)
{
    $validated = $request->validate([
        'service_id' => ['required', 'integer',
            Rule::exists('services', 'id')
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('is_active', true)],
        'staff_id'   => ['nullable', 'integer',
            Rule::exists('users', 'id')
                ->where('tenant_id', auth()->user()->tenant_id)],
        'notes'      => ['nullable', 'string', 'max:300'],
    ]);

    $service = Service::findOrFail($validated['service_id']);

    $booking = Booking::create([
        'tenant_id'   => auth()->user()->tenant_id,
        'customer_id' => auth()->id(),
        'service_id'  => $service->id,
        'staff_id'    => $validated['staff_id'] ?? null,
        'total_price' => $service->base_price,
        'status'      => 'queued',
        'booked_at'   => now(),
        'notes'       => $validated['notes'] ?? null,
        'created_by'  => auth()->id(),
    ]);

    return redirect()->route('customer.dashboard')
        ->with('success', "You're in! Booking #{$booking->id} is confirmed.");
}

public function cancel(Booking $booking)
{
    abort_if($booking->customer_id !== auth()->id(), 403);
    abort_if(!in_array($booking->status, ['queued']), 422,
        'Only queued bookings can be cancelled.');

    $booking->update(['status' => 'cancelled']);

    return redirect()->route('customer.bookings')
        ->with('success', "Booking #{$booking->id} has been cancelled.");
}
```

---

### C. `app/Http/Controllers/Customer/ServiceController.php`

```php
public function index()
{
    $tenant   = auth()->user()->tenant;
    $services = Service::where('tenant_id', $tenant->id)
        ->where('is_active', true)
        ->get();
    return view('customer.services.index', compact('services', 'tenant'));
}
```

---

### D. `app/Http/Controllers/Customer/PointsController.php`

```php
public function index()
{
    $user    = auth()->user();
    $balance = $user->points_balance ?? 0;

    $ledger  = PointsLedger::where('customer_id', $user->id)
        ->with('booking.service')
        ->latest()
        ->paginate(15);

    // Milestones: define redemption thresholds
    $milestones = [
        ['points' => 300,  'reward' => 'Free Beard Lineup'],
        ['points' => 500,  'reward' => 'Free Classic Cut'],
        ['points' => 800,  'reward' => 'Free Skin Fade'],
        ['points' => 1200, 'reward' => 'Free Cut + Beard Combo'],
    ];

    return view('customer.points.index',
        compact('balance', 'ledger', 'milestones'));
}
```

---

### E. `app/Http/Controllers/Customer/ProfileController.php`

```php
public function edit()
{
    return view('customer.profile.edit', ['user' => auth()->user()]);
}

public function update(Request $request)
{
    $validated = $request->validate([
        'name'  => ['required', 'string', 'max:100'],
        'phone' => ['nullable', 'string', 'max:20'],
        'email' => ['required', 'email',
            Rule::unique('users')->ignore(auth()->id())],
    ]);

    auth()->user()->update($validated);

    return redirect()->route('customer.profile')
        ->with('success', 'Profile updated.');
}
```

---

## VIEWS — FILE STRUCTURE

Create all views inside `resources/views/customer/`:

```
resources/views/customer/
├── layouts/
│   └── app.blade.php          ← Customer shell layout
├── partials/
│   ├── sidebar.blade.php
│   ├── topbar.blade.php
│   └── flash.blade.php
├── dashboard.blade.php        ← Main dashboard
├── bookings/
│   ├── index.blade.php        ← All bookings list
│   └── create.blade.php       ← Booking form
├── services/
│   └── index.blade.php        ← Service catalog
├── points/
│   └── index.blade.php        ← Points & rewards
└── profile/
    └── edit.blade.php         ← Profile settings
```

---

## DESIGN SYSTEM

### Aesthetic Direction
**Warm, premium, barbershop-editorial.**
Think a high-end grooming magazine meets a modern booking app.
Dark navy base (`#0D1117`) with warm cream text (`#F5EFE0`),
gold accents (`#C9A84C`), rust red CTAs (`#B54B2A`).
Typography: `Bebas Neue` for display headings + `DM Sans` for body.
NOT a generic dashboard — feels like a personal grooming concierge.

### CSS Variables (define in layout `<style>` or `customer.css`)

```css
:root {
  --ink:          #0D1117;
  --surface:      #161B22;
  --surface-2:    #1C2330;
  --surface-3:    #222B3A;
  --border:       rgba(255,255,255,0.07);
  --border-strong:rgba(255,255,255,0.13);
  --cream:        #F5EFE0;
  --muted:        #8B9AAD;
  --gold:         #C9A84C;
  --gold-dim:     rgba(201,168,76,0.15);
  --rust:         #B54B2A;
  --rust-dim:     rgba(181,75,42,0.15);
  --green:        #28C76F;
  --green-dim:    rgba(40,199,111,0.12);
  --radius:       10px;
  --radius-lg:    16px;
  --radius-xl:    22px;
  --font-display: 'Bebas Neue', sans-serif;
  --font-body:    'DM Sans', sans-serif;
  --font-mono:    'DM Mono', monospace;
}
```

Load from Google Fonts in the layout `<head>`:
```html
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
```

---

## LAYOUT SHELL — `customer/layouts/app.blade.php`

Build a **sidebar + main content** shell. Structure:

```
┌─────────────────────────────────────────────────────┐
│  Sidebar (260px, fixed, var(--surface))             │
│  ┌──────────────────────────────────────────────┐  │
│  │  Shop logo + name (from $tenant)             │  │
│  │  ─────────────────────────────────────────   │  │
│  │  User avatar (initials) + name + points pill │  │
│  │  ─────────────────────────────────────────   │  │
│  │  NAV ITEMS:                                  │  │
│  │    Dashboard          /customer/dashboard    │  │
│  │    Book a Service     /customer/services     │  │
│  │    My Bookings        /customer/bookings     │  │
│  │    Points & Rewards   /customer/points       │  │
│  │    Profile            /customer/profile      │  │
│  │  ─────────────────────────────────────────   │  │
│  │  Queue Status widget (live, polls 30s)       │  │
│  │  ─────────────────────────────────────────   │  │
│  │  [Sign Out] at bottom                        │  │
│  └──────────────────────────────────────────────┘  │
│                                                     │
│  Main content area (flex-1, var(--ink) bg)          │
│  ┌──────────────────────────────────────────────┐  │
│  │  Topbar: Page title + greeting + notif bell  │  │
│  │  Flash message area                          │  │
│  │  @yield('content')                           │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

**Sidebar nav active state:** Use a sliding pill indicator with
`position: absolute` background that moves to the active item.
Active item: cream text. Inactive: muted text, hover → cream.

**Points pill in sidebar** (below username):
```html
<span class="points-pill">
  ★ {{ auth()->user()->points_balance ?? 0 }} pts
</span>
```
Style: gold background at 15% opacity, gold text, `border-radius: 999px`,
`font: var(--font-mono)`, `font-size: 11px`.

**Queue Status sidebar widget:**
Small card at bottom of sidebar showing live queue count.
```html
<div class="sidebar-queue-widget">
  <div class="sqw-dot"></div>  <!-- pulsing green dot -->
  <span class="sqw-label">Queue</span>
  <span class="sqw-count" id="sidebar-queue-count">—</span>
  <span class="sqw-sub">ahead · ~<span id="sidebar-wait">?</span> min</span>
</div>
```
Poll `GET /api/public/queue/status` every 30 seconds via `fetch`.
Pause polling when `document.hidden`.

**Mobile:** sidebar collapses to a slide-in drawer. A hamburger
button appears in the topbar. Bottom nav bar (5 icons) replaces
sidebar for screens under 768px.

---

## VIEW SPECS

---

### 1. `customer/dashboard.blade.php`

This is the hero screen. Everything a customer needs at a glance.

#### Layout (top to bottom):

**A. Welcome strip**
```
Good morning, Juan! ← dynamic greeting based on time of day
Here's what's happening at {{ $tenant->name }}.
```
Font: `var(--font-display)`, size `clamp(28px, 4vw, 48px)`.
Animate in on load: words slide up with stagger (CSS `@keyframes slideUp`
with `animation-delay` per word). Total animation: 0.6s.

**B. KPI cards row (4 cards)**

```
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│  Points Balance  │ │   Total Visits   │ │   Points Earned  │ │  Leaderboard Rank│
│                  │ │                  │ │   (all time)     │ │                  │
│   ★ 420 pts      │ │     18 visits    │ │    640 pts       │ │    #3            │
│  "earn more →"   │ │  "since joining" │ │  "redeemed 220"  │ │  "this month"    │
└──────────────────┘ └──────────────────┘ └──────────────────┘ └──────────────────┘
```

Each card: `var(--surface)` background, `1px solid var(--border)`,
`border-radius: var(--radius-lg)`, `padding: 24px`.
The large number animates counting up from 0 on page load using
a JS counter (`requestAnimationFrame`, duration 1200ms, ease out).
Points Balance card: gold accent border-left `3px solid var(--gold)`.
Leaderboard Rank card: if rank ≤ 3, show a gold crown icon (CSS shape or emoji).

**C. Active Booking banner** ← show ONLY if `$activeBooking` exists

```
┌──────────────────────────────────────────────────────────────────────┐
│  ⏳  You have an active booking                                       │
│  Service: Skin Fade  ·  Barber: Kuya Renz  ·  Status: IN QUEUE #3   │
│  Estimated wait: ~25 min                          [View Details →]   │
└──────────────────────────────────────────────────────────────────────┘
```

Style: `background: var(--gold-dim)`, `border: 1px solid var(--gold)`,
`border-radius: var(--radius-lg)`. Status chip colored by status.
Animate in: slides down from above with `translateY(-16px) → 0` + fade,
`0.4s ease-out`.

**D. Services section — "Book a Service"**

```
Section header: "BOOK A SERVICE"  (Bebas Neue, 32px)
Subtext: "Choose from {{ $tenant->name }}'s menu"

Grid: 2 columns on desktop, 1 on mobile
```

Each service card:
```
┌─────────────────────────────────────┐
│  01                    (large muted │
│                         number BG) │
│  ✂ Classic Cut                      │
│  A timeless cut for any style.      │
│  ─────────────────────────────────  │
│  ₱280          30 min               │
│                    [Book Now →]     │
└─────────────────────────────────────┘
```

Card hover: entire card lifts `translateY(-4px)` + `box-shadow` upgrade.
`[Book Now →]` button: on hover, background sweeps left-to-right
from transparent to `var(--rust)` using CSS `clip-path` or
`::before` pseudo-element `scaleX(0 → 1)` transform.
Clicking navigates to `/customer/book/{service->id}`.

**E. Recent Bookings** (last 5, table or card list)

```
Section header: "RECENT BOOKINGS"

Columns: Date · Service · Barber · Status · Price
```

Status chips:
- `queued`      → amber pill
- `in_progress` → purple pill (pulsing dot)
- `completed`   → green pill
- `cancelled`   → gray pill

Empty state: centered illustration (CSS barbershop pole shape) +
"No bookings yet. Book your first cut above!"

**F. Points progress bar** (teaser, links to full points page)

```
┌──────────────────────────────────────────────────────────────────┐
│  Your Points  ★ 420                           Next reward: 80 away│
│  ██████████████████████░░░░░  420 / 500 pts                       │
│  Free Classic Cut at 500 pts             [See all rewards →]      │
└──────────────────────────────────────────────────────────────────┘
```

Progress bar: `var(--surface-3)` track, gold fill,
`border-radius: 999px`, height `8px`.
Fill width animates from 0% to correct % on page load (CSS transition
triggered by adding a class via JS after 200ms delay).

---

### 2. `customer/services/index.blade.php`

Full service catalog page.

**Header:**
```
BOOK A SERVICE        ← Bebas Neue, large
Pick your service and preferred barber.
```

**Service grid:** 3 columns desktop, 2 tablet, 1 mobile.

Each card is larger than dashboard version — includes:
- Service number (large muted background)
- Icon glyph (use a relevant emoji for each service type, sized 28px)
- Service name (Bebas Neue, 26px)
- Description (DM Sans, 13px, muted)
- Duration + price row
- `[Book This Service]` button — full width, rust background

Cards stagger in on load: `animation-delay: calc(var(--i) * 60ms)`.
Set `--i` via inline `style="--i:0"`, `style="--i:1"` etc. in Blade loop.

No services empty state: "Services coming soon. Check back later!"

---

### 3. `customer/bookings/create.blade.php`

Single-focus booking form. No distractions.

**Layout:** Centered, max-width `560px`, card container.

```
BOOK: [SERVICE NAME]         ← Pre-filled heading
₱[price]  ·  [duration] min ← Service summary pill row

─────────────────────────────

Preferred Barber  [Dropdown ▾]
  Options: "Any available barber" + each barber name

Notes (optional)  [Textarea]
  Placeholder: "e.g. Low fade, keep top long"

─────────────────────────────

        [  Confirm Reservation  ]
        No account changes · Free to cancel
```

**Validation:**
- `service_id`: hidden input, pre-filled from route parameter
- `staff_id`: optional select
- `notes`: optional, max 300 chars
- Show inline error below each field on validation failure
- Laravel `@error` directives for server-side errors

**Submit button states:**
- Default: `var(--rust)` background, cream text
- Loading: disabled + spinner (CSS border-spin animation)
- On error: shake animation `translateX(-6px → 6px → -4px → 4px → 0)`

**Back link:** `← Back to services` above the card, muted.

---

### 4. `customer/bookings/index.blade.php`

Full booking history page.

**Header:**
```
MY BOOKINGS           ← Bebas Neue
All your visits and upcoming reservations.
```

**Filter tabs** (client-side JS, no page reload):
`All` | `Active` | `Completed` | `Cancelled`

Clicking a tab filters the visible cards by status.
Active tab: cream text + `var(--rust)` bottom border indicator
that slides to the active tab (CSS `left` transition).

**Booking cards** (not a table — cards for mobile-friendliness):

```
┌────────────────────────────────────────────────────────┐
│  #42                  QUEUED ●             Jan 15 2025 │
│  ──────────────────────────────────────────────────── │
│  Skin Fade                            ₱350             │
│  Barber: Kuya Renz                    30 min           │
│  Notes: Low fade please                                │
│                                       [Cancel]         │
└────────────────────────────────────────────────────────┘
```

Show `[Cancel]` button ONLY if status is `queued`.
Cancel triggers a `<form method="POST">` with `@method('DELETE')` + confirm dialog.
Completed bookings show `+N pts earned` in green at bottom right.

**Pagination:** Laravel Blade `{{ $bookings->links() }}` with custom
styling to match the dark theme.

**Empty state:** Center-aligned, muted icon, "No bookings yet."
with a link `→ Book your first service`.

---

### 5. `customer/points/index.blade.php`

The most delightful screen — gamified rewards tracker.

**Header section:**
```
YOUR POINTS           ← Bebas Neue display
★ 420 pts             ← huge number, Bebas Neue, 72px, gold color
                        counts up from 0 on load (JS counter)
```

**Milestone progress track:**

Horizontal timeline showing 4 milestones. Customer's current
balance determines how far along the track they are.

```
  0 ──●──────────────────────────────────────────── 1200
      │                                               │
     [300]          [500]          [800]           [1200]
   Beard Lineup  Classic Cut   Skin Fade      Cut + Beard
   ✓ Unlocked   ← NEXT (80 away)   Locked         Locked
```

Each milestone node: circle, `40px`, border.
- Unlocked (balance ≥ threshold): gold fill, checkmark inside
- Next target: pulsing gold border ring animation, label shows "N pts away"
- Locked: `var(--surface-3)` fill, lock icon (CSS shape)

Progress bar connecting the nodes: fills up to the customer's
current position. Gold fill, animated on load.

**Points ledger table:**

```
Section: "POINTS HISTORY"

Columns: Date · Description · Type · Points · Balance
```

Type chips:
- `earn`       → green pill `+N pts`
- `redeem`     → rust pill `-N pts`
- `adjustment` → gray pill

Description examples:
- "Earned from booking #42 (Skin Fade)"
- "Redeemed for discount on booking #45"

**Redeem CTA box:**

```
┌────────────────────────────────────────────────────────┐
│  Ready to redeem?                                      │
│  Tell your barber your points balance when you book.   │
│  They will apply the discount for you.                 │
│                                                        │
│  Your balance: ★ 420 pts                               │
│  Equivalent to: ₱210 discount (₱0.50 per point)        │
└────────────────────────────────────────────────────────┘
```

Style: gold dim background, gold border, `border-radius: var(--radius-lg)`.

**Leaderboard teaser:**

Show top 5 customers by `points_balance` for this tenant.
Highlight the current user's row with a `var(--gold-dim)` background.

```
Rank │ Name          │ Points
─────┼───────────────┼────────
 1   │ Maria A.      │ 1,240
 2   │ Juan D.       │ 820
→ 3  │ You           │ 420   ← highlighted row
 4   │ Rico L.       │ 380
 5   │ Ana C.        │ 310
```

---

### 6. `customer/profile/edit.blade.php`

Clean, minimal settings form.

**Header:** `MY PROFILE`

**Form fields:**
- Full Name (text input)
- Email (email input)
- Phone (text input, optional)
- Current Password / New Password / Confirm Password
  (password change section, collapsible — "Change password" toggle link)

**Avatar display** (above form):
Large initials circle, `80px`, gradient background derived from
name (use PHP `crc32($name) % 6` to pick from 6 preset gradients).
Not editable (no file upload needed).

**Stats row** (read-only, below avatar):
```
18 visits  ·  420 pts  ·  Member since Jan 2025
```

**Save button:** `var(--rust)` background, full width on mobile.

**Danger zone** (at bottom, collapsed by default):
"Account & Data" section with `[Request Account Deletion]` button
(links to an email or just shows a toast: "Contact us at support@...").

---

## ANIMATIONS & MICRO-INTERACTIONS

Use **pure CSS animations only** (no JS animation libraries).
JS is only used for: counters, polling, tab filtering, form states.

### Page load sequence (dashboard):
```css
@keyframes slideUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Apply to sections with staggered delay */
.welcome-strip   { animation: slideUp 0.5s 0.05s both; }
.kpi-cards       { animation: slideUp 0.5s 0.15s both; }
.active-booking  { animation: slideUp 0.5s 0.22s both; }
.services-section{ animation: slideUp 0.5s 0.30s both; }
.bookings-section{ animation: slideUp 0.5s 0.38s both; }
.points-teaser   { animation: slideUp 0.5s 0.46s both; }
```

### KPI number counter (JS):
```js
function animateCount(el, target, isDecimal = false, prefix = '', suffix = '') {
    const dur = 1200, start = performance.now();
    const ease = t => 1 - Math.pow(1 - t, 4); // quartic ease out
    (function tick(now) {
        const p = Math.min((now - start) / dur, 1);
        const val = isDecimal
            ? (ease(p) * target).toFixed(1)
            : Math.floor(ease(p) * target);
        el.textContent = prefix + (val >= 1000 ? Number(val).toLocaleString() : val) + suffix;
        if (p < 1) requestAnimationFrame(tick);
    })(start);
}

// On DOMContentLoaded:
document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseFloat(el.dataset.count);
    animateCount(el, target, false, el.dataset.prefix || '', el.dataset.suffix || '');
});
```

Usage in Blade: `<span data-count="{{ $pointsBalance }}" data-suffix=" pts">0 pts</span>`

### Points progress bar (CSS transition):
```js
// After 300ms delay, set the width to trigger CSS transition
setTimeout(() => {
    const bar = document.getElementById('points-fill');
    if (bar) bar.style.width = bar.dataset.pct + '%';
}, 300);
```

```css
#points-fill {
    width: 0%;
    transition: width 1s cubic-bezier(0.16, 1, 0.3, 1);
    background: var(--gold);
    height: 8px;
    border-radius: 999px;
}
```

### Card hover lift:
```css
.service-card, .booking-card {
    transition: transform 0.25s ease, box-shadow 0.25s ease,
                border-color 0.25s ease;
}
.service-card:hover, .booking-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(0,0,0,0.3);
    border-color: var(--border-strong);
}
```

### Button Book Now sweep:
```css
.btn-book {
    position: relative; overflow: hidden;
    background: transparent;
    border: 1px solid var(--border-strong);
    color: var(--cream);
    transition: color 0.25s, border-color 0.25s;
}
.btn-book::before {
    content: '';
    position: absolute; inset: 0;
    background: var(--rust);
    transform: scaleX(0); transform-origin: left;
    transition: transform 0.28s ease;
    z-index: 0;
}
.btn-book:hover::before  { transform: scaleX(1); }
.btn-book:hover          { border-color: var(--rust); color: #fff; }
.btn-book span           { position: relative; z-index: 1; }
```

### Status chip pulse (in_progress):
```css
.chip-in-progress::before {
    content: '';
    display: inline-block;
    width: 6px; height: 6px;
    background: #a78bfa;
    border-radius: 50%;
    margin-right: 5px;
    animation: pulse-dot 1.5s ease-in-out infinite;
}
@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: 0.5; transform: scale(1.5); }
}
```

### Form submit shake on error:
```css
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%       { transform: translateX(-6px); }
    40%       { transform: translateX(6px); }
    60%       { transform: translateX(-4px); }
    80%       { transform: translateX(4px); }
}
.btn-submit.error { animation: shake 0.4s ease; }
```

Trigger via JS: `btn.classList.add('error'); setTimeout(() => btn.classList.remove('error'), 400);`

### Sidebar nav active indicator:
```css
.nav-list { position: relative; }
.nav-indicator {
    position: absolute; left: 0; right: 0;
    height: 44px;
    background: var(--surface-3);
    border-radius: var(--radius);
    transition: top 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 0;
}
.nav-item { position: relative; z-index: 1; }
```

Move the indicator via JS on page load: find the active nav item's
`offsetTop` and set `indicator.style.top = offsetTop + 'px'`.

---

## RESPONSIVE RULES

```
Desktop (≥1024px): sidebar visible, 3-col service grid
Tablet  (768–1023px): sidebar visible (narrower 200px), 2-col grid
Mobile  (<768px):
  - Sidebar collapses, hamburger button in topbar
  - Drawer slides in from left (transform: translateX(-100% → 0))
  - Overlay backdrop (rgba(0,0,0,0.5)) behind drawer
  - Bottom nav bar: 5 icons (Home, Book, History, Points, Profile)
  - 1-col service grid
  - KPI cards: 2×2 grid
```

Bottom nav bar (mobile only):
```html
<nav class="bottom-nav">
  <a href="/customer/dashboard" class="{{ request()->is('customer/dashboard') ? 'active' : '' }}">
    <span class="bn-icon">⌂</span><span class="bn-label">Home</span>
  </a>
  <a href="/customer/services">
    <span class="bn-icon">✂</span><span class="bn-label">Book</span>
  </a>
  <a href="/customer/bookings">
    <span class="bn-icon">☰</span><span class="bn-label">History</span>
  </a>
  <a href="/customer/points">
    <span class="bn-icon">★</span><span class="bn-label">Points</span>
  </a>
  <a href="/customer/profile">
    <span class="bn-icon">◯</span><span class="bn-label">Profile</span>
  </a>
</nav>
```

---

## FLASH MESSAGES

In `customer/partials/flash.blade.php`:

```blade
@if(session('success'))
<div class="flash flash-success" id="flash-msg">
    <span class="flash-icon">✓</span>
    {{ session('success') }}
    <button class="flash-close" onclick="this.parentElement.remove()">×</button>
</div>
@endif

@if(session('error'))
<div class="flash flash-error" id="flash-msg">
    <span class="flash-icon">!</span>
    {{ session('error') }}
    <button class="flash-close" onclick="this.parentElement.remove()">×</button>
</div>
@endif
```

Style: fixed top-right, `min-width: 320px`, slides in from right
(`translateX(120%) → 0`), auto-dismiss after 4 seconds via JS.

```css
.flash {
    position: fixed; top: 24px; right: 24px; z-index: 500;
    padding: 14px 20px; border-radius: var(--radius);
    display: flex; align-items: center; gap: 10px;
    font-size: 14px; font-family: var(--font-body);
    animation: slideInRight 0.35s ease forwards;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}
.flash-success { background: rgba(40,199,111,0.12); border: 1px solid var(--green); color: var(--green); }
.flash-error   { background: var(--rust-dim); border: 1px solid var(--rust); color: #ff8a6e; }
@keyframes slideInRight {
    from { opacity: 0; transform: translateX(40px); }
    to   { opacity: 1; transform: translateX(0); }
}
```

Auto-dismiss:
```js
document.addEventListener('DOMContentLoaded', () => {
    const flash = document.getElementById('flash-msg');
    if (flash) setTimeout(() => {
        flash.style.opacity = '0';
        flash.style.transition = 'opacity 0.3s';
        setTimeout(() => flash.remove(), 300);
    }, 4000);
});
```

---

## QUEUE STATUS POLLING

In `customer/layouts/app.blade.php` `<script>` block:

```js
function updateQueueStatus() {
    fetch('/api/public/queue/status', {
        headers: { 'X-Tenant-ID': '{{ auth()->user()->tenant_id }}' }
    })
    .then(r => r.json())
    .then(data => {
        const countEl = document.getElementById('sidebar-queue-count');
        const waitEl  = document.getElementById('sidebar-wait');
        if (countEl) countEl.textContent = data.in_queue ?? '—';
        if (waitEl)  waitEl.textContent  = data.estimated_wait_min ?? '?';
    })
    .catch(() => {}); // fail silently
}

updateQueueStatus();
const queueInterval = setInterval(updateQueueStatus, 30000);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) updateQueueStatus();
});
```

If `/api/public/queue/status` does not exist yet, create a simple
public route that returns:

```php
// routes/api.php — no auth middleware
Route::get('/public/queue/status', function (Request $request) {
    $tenantId = $request->header('X-Tenant-ID');
    $count = \App\Models\Booking::where('tenant_id', $tenantId)
        ->whereIn('status', ['queued', 'in_progress'])
        ->count();
    return response()->json([
        'in_queue'             => $count,
        'estimated_wait_min'  => $count * 10,
        'shop_open'           => true,
    ]);
});
```

---

## IMPLEMENTATION CHECKLIST

Run through this after building all views:

### Routes & Controllers
- [ ] All 9 routes registered inside customer middleware group
- [ ] Login redirect for `customer` role updated to `/customer/dashboard`
- [ ] `DashboardController` passes all 7 required variables to view
- [ ] `BookingController@store` validates `service_id` belongs to customer's tenant
- [ ] `BookingController@cancel` guards against non-owner and non-queued status
- [ ] `PointsController` loads ledger with `booking.service` eager-load
- [ ] Queue status API endpoint returns `in_queue` + `estimated_wait_min`

### Views & Layout
- [ ] Layout shell renders for all 5 pages
- [ ] Active nav item highlighted correctly on each page
- [ ] Flash messages appear and auto-dismiss
- [ ] Mobile sidebar drawer opens/closes correctly
- [ ] Bottom nav visible only on mobile

### Dashboard
- [ ] Welcome greeting changes based on time of day (morning/afternoon/evening)
- [ ] All 4 KPI cards render with correct data
- [ ] KPI numbers count up on page load
- [ ] Active booking banner shows ONLY when `$activeBooking` is not null
- [ ] Service cards link to correct booking route
- [ ] Points progress bar fills to correct percentage
- [ ] Recent bookings table shows correct status chips

### Booking Flow
- [ ] Service catalog lists only active services for customer's tenant
- [ ] Booking form pre-fills service name and price from URL param
- [ ] Barber dropdown loads only barbers from customer's tenant
- [ ] Successful booking redirects to dashboard with success flash
- [ ] Cancel button only shows on `queued` bookings
- [ ] Cancel form uses `@method('DELETE')` + CSRF

### Points Page
- [ ] Balance counter animates on load
- [ ] Milestone track shows correct unlocked/locked state
- [ ] Ledger table paginates correctly
- [ ] Leaderboard highlights current user's row
- [ ] Redeem equivalent calculation is correct (adjust rate per tenant config)

### Design
- [ ] Google Fonts loaded (`Bebas Neue`, `DM Sans`, `DM Mono`)
- [ ] CSS variables defined and used consistently
- [ ] Dark background throughout (no white/light surfaces)
- [ ] All animations complete within 600ms
- [ ] No layout shift on page load
- [ ] Readable on mobile (minimum `44px` tap targets)
- [ ] Status chips are color-coded and pill-shaped
