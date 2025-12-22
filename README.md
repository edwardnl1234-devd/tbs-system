# TBS System - Palm Oil Mill Management API

> Complete Laravel 12 REST API for managing palm oil mill (PKS - Pabrik Kelapa Sawit) operations including queue management, weighbridge system, sortation, production tracking, inventory management, and sales.

## ✨ Features

- **Queue Management** - Truck arrival, queue numbering, wait time estimation
- **Weighbridge (WBS)** - Bruto/tara weighing, automatic netto calculation
- **Sortation** - TBS grading and quality assessment
- **Production** - Batch processing, OER/KER calculation
- **Stock Management** - CPO, Kernel, Shell with opname and adjustments
- **Sales** - Multi-product sales with delivery workflow
- **Dashboard & Reports** - Real-time statistics and comprehensive reports
- **Authentication** - Laravel Sanctum token-based API

## 🚀 Quick Start

```bash
# Install dependencies and setup
composer run-script setup

# Configure database in .env
# Then run migrations and seeders
php artisan migrate --seed

# Start the development server
composer run dev
```

## 📚 Documentation

See [docs/API.md](docs/API.md) for complete API documentation.

## 🔑 Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Owner | admin@tbs-system.local | password |
| Manager | manager@tbs-system.local | password |
| Operator | operator@tbs-system.local | password |

## 🧪 Testing

```bash
composer test
```

## 📄 License

Proprietary software.
