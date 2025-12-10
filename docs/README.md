# DevFlow Pro - Documentation

Welcome to DevFlow Pro documentation!

## 📚 Quick Navigation

### Getting Started
- [Quick Reference Guide](guides/quick-reference.md) - Common tasks and actions
- [System Overview](architecture/system-overview.md) - Architecture and data flow

### Features
- [Complete Features Guide](features/complete-guide.md) - All 60+ features explained

### Inline Help System
- [Inline Help Overview](inline-help/README.md) - Start here
- [Implementation Guide](inline-help/implementation-example.md) - How to implement
- [All 67 Features](inline-help/complete-summary.md) - Complete feature list

### Architecture
- [System Architecture](architecture/system-overview.md) - Visual diagrams
- [Database Schema](inline-help/database-system.md) - Help content tables

## 🎯 By Use Case

**"How do I deploy a project?"**
→ [Quick Reference](guides/quick-reference.md#deployment-actions)

**"What features does DevFlow have?"**
→ [Features Guide](features/complete-guide.md)

**"How do I add inline help to my UI?"**
→ [Inline Help Guide](inline-help/README.md)

**"I want to understand the architecture"**
→ [System Overview](architecture/system-overview.md)

## 🚀 Implementation

### Database Setup
```bash
php artisan migrate
php artisan db:seed --class=CompleteHelpContentSeeder
```

### Add Inline Help
```blade
<button wire:click="deploy">Deploy</button>
<livewire:inline-help help-key="deploy-button" />
```

## 📖 Documentation Structure

```
docs/
├── README.md (you are here)
├── features/
│   └── complete-guide.md (60+ features)
├── guides/
│   └── quick-reference.md (100+ actions)
├── architecture/
│   └── system-overview.md (9 diagrams)
└── inline-help/
    ├── README.md (overview)
    ├── ui-patterns.md (27+ UI elements)
    ├── database-system.md (complete implementation)
    ├── implementation-example.md (working code)
    ├── complete-summary.md (all 67 features)
    └── feature-audit.md (missing features)
```

## ✅ Testing Documentation

Test-related documentation remains in `storage/testing/`:
- Test execution status
- Coverage reports
- Generated tests overview

---

**Need help?** Start with the [Quick Reference Guide](guides/quick-reference.md)
