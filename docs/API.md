# TBS System - Palm Oil Mill Management API

> Complete Laravel 12 REST API for managing palm oil mill (PKS - Pabrik Kelapa Sawit) operations including queue management, weighbridge system, sortation, production tracking, inventory management, and sales.

## 📋 Table of Contents

- [Features](#features)
- [System Flow](#system-flow)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database](#database)
- [API Documentation](#api-documentation)
- [User Roles](#user-roles)
- [Testing](#testing)

## ✨ Features

### Core Modules
- **Queue Management** - Truck arrival, queue numbering, bank assignment, wait time estimation
- **Weighbridge (WBS)** - First weighing (bruto), second weighing (tara), automatic netto calculation
- **Sortation** - TBS grading (ripe, unripe, overripe, empty bunch, loose fruit, garbage)
- **Production** - Batch processing, OER/KER calculation, shift management
- **Stock Management** - CPO (by tank), Kernel, Shell with opname and adjustments
- **Sales** - Multi-product sales, delivery workflow, stock reservation

### Supporting Features
- **Authentication** - Laravel Sanctum token-based API auth
- **Role-based Access** - Owner, Manager, Supervisor, Operator, Staff, Mandor
- **Dashboard & Reports** - Real-time statistics, daily/weekly/monthly reports
- **Polling Endpoints** - Real-time updates for display screens
- **Price Management** - Daily TBS prices by supplier type, auto-price for weighings, online price fetching

## 🔄 System Flow

```
Truck Arrival → Queue System → Weighing (Bruto) → Sortation → Weighing (Tara)
     ↓                                                              ↓
Queue Number                                               Stock TBS Created
Generated                                                          ↓
                                                          Production Process
                                                                   ↓
                                            Stock CPO/Kernel/Shell Created
                                                                   ↓
                                                    Sales & Delivery
```

## 📌 Requirements

- PHP >= 8.2
- MySQL >= 8.0 (recommended) or SQLite
- Composer >= 2.0
- Node.js >= 18 (for frontend assets)

## 🚀 Installation

### Quick Setup

```bash
# Clone the repository
git clone <repository-url> tbs-system
cd tbs-system

# Run the setup script (creates .env, installs dependencies, generates key)
composer run-script setup
```

### Manual Setup

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env then run migrations
php artisan migrate

# Seed the database with default data
php artisan db:seed

# Install frontend dependencies (optional)
npm install
npm run build
```

## ⚙️ Configuration

### Environment Variables

Key configuration in `.env`:

```env
# Application
APP_NAME="TBS System"
APP_TIMEZONE=Asia/Jakarta

# Database
DB_CONNECTION=mysql
DB_DATABASE=tbs_system
DB_USERNAME=root
DB_PASSWORD=

# Sanctum (API tokens)
SANCTUM_TOKEN_EXPIRATION=0  # 0 = never expires
```

## 💾 Database

### Running Migrations

```bash
php artisan migrate
```

### Seeding Default Data

```bash
php artisan db:seed
```

This creates:
- 6 default users (one per role)
- 5 suppliers (inti, plasma, umum)
- 5 customers
- 10 trucks
- 30 days of TBS price history

### Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Owner | admin@tbs-system.local | password |
| Manager | manager@tbs-system.local | password |
| Supervisor | supervisor@tbs-system.local | password |
| Operator | operator@tbs-system.local | password |
| Staff | staff@tbs-system.local | password |
| Mandor | mandor@tbs-system.local | password |

## 📚 API Documentation

### Base URL
```
http://localhost/api
```

### Authentication

All protected routes require Bearer token:
```
Authorization: Bearer {token}
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "admin@tbs-system.local",
    "password": "password"
}
```

Response:
```json
{
    "success": true,
    "data": {
        "user": {...},
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### API Endpoints Overview

#### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /auth/register | Register new user |
| POST | /auth/login | Login |
| POST | /auth/logout | Logout (protected) |
| GET | /auth/user | Get current user (protected) |
| PUT | /auth/profile | Update profile (protected) |
| PUT | /auth/password | Change password (protected) |

#### Master Data
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | /trucks | List/Create trucks |
| GET/PUT/DELETE | /trucks/{id} | Show/Update/Delete truck |
| GET/POST | /suppliers | List/Create suppliers |
| GET | /suppliers/by-type/{type} | Get suppliers by type |
| GET/POST | /customers | List/Create customers |
| GET/POST | /tbs-prices | List/Create TBS prices |
| GET | /tbs-prices/latest | Get latest prices by type |
| GET | /tbs-prices/today | Get today's prices |
| GET | /tbs-prices/by-date/{date} | Get prices by date |
| GET | /tbs-prices/history | Get price history |
| GET | /tbs-prices/sources | List available online sources |
| POST | /tbs-prices/fetch-online | Fetch prices from online source (admin) |

#### Queue Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | /queues | List/Create queue |
| GET | /queues/active | Get waiting queues |
| GET | /queues/processing | Get processing queues |
| GET | /queues/today | Get today's queues |
| GET | /queues/statistics | Get queue statistics |
| PUT | /queues/{id}/status | Update queue status |

#### Weighing
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | /weighings | List/Create weighing |
| POST | /weighings/{id}/weigh-in | Record first weight (bruto) |
| POST | /weighings/{id}/weigh-out | Record second weight (tara) |
| POST | /weighings/{id}/complete | Complete weighing |
| POST | /weighings/{id}/refresh-price | Refresh price from TBS prices |
| POST | /weighings/bulk-refresh-prices | Bulk refresh pending weighings |
| GET | /weighings/today | Get today's weighings |
| GET | /weighings/pending | Get pending weighings |

#### Sortation
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | /sortations | List/Create sortation |
| GET | /sortations/today | Get today's sortations |
| GET | /sortations/performance | Get sortation performance |
| GET | /sortations/by-weighing/{id} | Get by weighing ID |

#### Production
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | /productions | List/Create production batch |
| GET | /productions/today | Get today's production |
| GET | /productions/statistics | Get production stats |
| GET | /productions/efficiency | Get efficiency metrics |

#### Stock Management
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | /stock/cpo | CPO stock list/create |
| GET | /stock/cpo/summary | CPO stock summary |
| GET | /stock/cpo/by-tank | CPO by tank |
| GET/POST | /stock/kernel | Kernel stock |
| GET/POST | /stock/shell | Shell stock |
| GET/POST | /stock-opnames | Stock opname operations |
| POST | /stock-opnames/{id}/verify | Verify opname |
| GET/POST | /stock-adjustments | Stock adjustments |
| POST | /stock-adjustments/{id}/approve | Approve adjustment |
| POST | /stock-adjustments/{id}/reject | Reject adjustment |

#### Sales
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST | /sales | List/Create sales order |
| POST | /sales/{id}/deliver | Mark as delivered |
| POST | /sales/{id}/complete | Complete sale |
| GET | /sales/today | Today's sales |
| GET | /sales/pending | Pending sales |
| GET | /sales/statistics | Sales statistics |

#### Dashboard & Reports
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /dashboard | All dashboard metrics |
| GET | /dashboard/queue-stats | Queue statistics |
| GET | /dashboard/production-stats | Production stats |
| GET | /dashboard/stock-summary | Stock summary |
| GET | /dashboard/margin | Margin calculation |
| GET | /reports/daily | Daily report |
| GET | /reports/weekly | Weekly report |
| GET | /reports/monthly | Monthly report |
| GET | /reports/stock-movement | Stock movement report |

#### Polling (Real-time Updates)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /polling/queue | Queue display data |
| GET | /polling/weighing | Weighing status |
| GET | /polling/stock | Stock levels |
| GET | /polling/dashboard | Dashboard metrics |
| GET | /polling/production | Production status |

## 👥 User Roles

| Role | Description | Access Level |
|------|-------------|--------------|
| **owner** | System owner | Full access |
| **manager** | Mill manager | All operations + user management |
| **supervisor** | Production supervisor | Production, stock, reports |
| **operator** | Weighbridge operator | Queue, weighing, sortation |
| **staff** | Admin staff | Data entry, reports |
| **mandor** | Field supervisor | Sortation input |

## 🧪 Testing

```bash
# Run all tests
composer test

# Or directly with artisan
php artisan test

# Run specific test
php artisan test --filter=ExampleTest
```

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/       # API Controllers
│   ├── Middleware/        # Role middleware
│   ├── Requests/          # Form validation
│   └── Resources/         # API Resources
├── Models/                # Eloquent models
└── Traits/                # ApiResponse trait

database/
├── factories/             # Model factories
├── migrations/            # Database migrations
└── seeders/               # Database seeders

routes/
├── api.php                # API routes
└── web.php                # Web routes
```

## 📄 License

This project is proprietary software.

---

Built with ❤️ using Laravel 12
