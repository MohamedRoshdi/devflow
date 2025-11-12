# 🏠 Public Marketing Home

## Feature Overview

A fully redesigned marketing-focused landing page lives at the root URL and showcases running projects while telling the DevFlow Pro story. The authenticated dashboard remains separate at `/dashboard`.

---

## ✨ Highlights

### Public Landing Experience
- **URL:** `/`
- **Access:** Public (no authentication required)
- **Purpose:** Present DevFlow Pro capabilities, highlight live projects, and funnel visitors to “Request Access”.

### Authenticated Dashboard
- **URL:** `/dashboard`
- **Access:** Authenticated users only
- **Purpose:** Full project/server/deployment management

---

## 🎨 Layout & Sections

### Floating Navigation Capsule
- Centered, rounded capsule with subtle shadow & blur
- Contains logo, quick anchors (`Projects`, `Platform`, `Workflow`), and the `Open Dashboard` CTA
- Restored theme toggle (`#theme-toggle`) so visitors can switch light/dark modes before sign-in

### Hero Experience
- Cinematic gradient backdrop (slate → blue) with blurred blobs
- Pulsing “Projects Live Now” badge reflects real project count
- Headline: “Deploy production apps in minutes, not days.”
- Subtext explains orchestration & monitoring benefits
- Auth users see **Launch Control Center** (dashboard link)
- Guests see **Request Access** (links to login + status message)
- Glassmorphism “Deployment Insights” card surfaces environment health, average deployment time, and security messaging

### Platform Highlights
- Three marketing tiles: Infrastructure Ready, Continuous Delivery, Operations Visibility
- Icon badges with supportive copy

### Workflow Timeline
- Gradient-backed four-step journey (01–04): Connect repo → Define environments → Deploy confidently → Monitor & iterate
- Reinforces DevFlow Pro pipeline at a glance

### Projects Grid
- Wide responsive grid (up to 1560px) displaying only `status = 'running'` projects
- Cards include:
  - Framework icon capsule (Laravel icon, code brackets, or globe)
  - Animated “Live” pill with pulsing dot
  - Environment badge text (“Production environment” etc.)
  - Domain preferred; safe fallback to `server.ip[:port]`
  - “Visit project” CTA with hover translation on icon

### Call-to-Action Banner
- Full-width gradient banner (`from-blue-600 via-indigo-600 to-purple-600`)
- Authenticated users → “Manage Projects”; guests → “Request Access” (login)

### Footer
- Wide 1560px container, stacked layout on mobile
- Copy: “Powered by DevFlow Pro — Professional Deployment Management” + copyright year

---

## 🔒 Registration Closure
- `/register` now redirects to `/login` with a flash message explaining registration is closed
- Login screen surfaces the status banner and asks users to contact an administrator for access
- All public CTAs now point to sign-in/request access instead of self-service registration

---

## 🔗 URL Handling
- Domains preserved exactly; `http(s)://{domain}`
- Fallback: `http://{server_ip}:{port}` when no domain exists
- All external links open in a new tab (`target="_blank"`)

---

## 📱 Responsiveness & Visual Effects
- Layout containers expanded to `max-w-[1560px]` (CTA banner `max-w-[1440px]`)
- Smooth transitions (`transition`, `hover:-translate-y-0.5`, shadow intensifiers)
- Animated elements: live badge pulse, hero blobs, CTA icon translation
- Full dark-mode parity via Tailwind dark classes

---

## 🧱 Technical Implementation

### Component
```php
app/Livewire/Home/HomePublic.php
- Fetches running projects with server relation
- Orders alphabetically
- Uses new `layouts.marketing` wrapper (no `max-w-md` constraint)
```

### Layout
```blade
resources/views/layouts/marketing.blade.php
- Minimal shell with theme-init script and slot
- Provides global theme toggle JS hook
```

### View
```blade
resources/views/livewire/home/home-public.blade.php
- Floating nav capsule with theme toggle and CTA logic
- Hero + insight card + stats
- Platform highlights, workflow timeline
- Projects grid / empty state
- Gradient CTA banner and footer
```

### Routes
```php
routes/web.php -> Route::get('/', HomePublic::class)->name('home');
routes/auth.php -> guest registration redirects to login with status message
```

---

## 🧪 Testing Checklist
- [x] Guest access renders landing page
- [x] Authenticated users see dashboard CTAs
- [x] Theme toggle switches modes without reload glitches
- [x] Hero content not obscured by fixed nav (`mt-28` offset)
- [x] Project URLs open in new tab with correct fallback
- [x] Empty state friendly for zero running projects
- [x] Dark mode verified end-to-end

---

## 📚 Related Docs
- [README.md](README.md)
- [CHANGELOG.md](CHANGELOG.md)
- [FEATURES.md](FEATURES.md)
- [ENVIRONMENT_MANAGEMENT_FEATURE.md](ENVIRONMENT_MANAGEMENT_FEATURE.md)

---

## ✅ Status
- [x] Implemented
- [x] Deployed
- [x] Documented
- [x] Mobile-friendly
- [x] Dark mode supported
- [x] Invite-only workflow enforced

