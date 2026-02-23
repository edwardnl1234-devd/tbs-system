# TBS System - Application Flowchart

> **Sistem Manajemen Pabrik Kelapa Sawit (PKS)**  
> Flowchart Dokumentasi

---

## 📋 Daftar Isi

1. [Gambaran Umum Sistem](#1-gambaran-umum-sistem)
2. [Alur Utama (Main Flow)](#2-alur-utama-main-flow)
3. [Alur Antrian & Penimbangan](#3-alur-antrian--penimbangan)
4. [Alur Sortasi & Stock TBS](#4-alur-sortasi--stock-tbs)
5. [Alur Produksi](#5-alur-produksi)
6. [Alur Stock Management](#6-alur-stock-management)
7. [Alur Penjualan](#7-alur-penjualan)
8. [Role-based Access Flow](#8-role-based-access-flow)

---

## 1. Gambaran Umum Sistem

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                          TBS SYSTEM - PALM OIL MILL                             │
│                         Sistem Manajemen PKS Terintegrasi                       │
└─────────────────────────────────────────────────────────────────────────────────┘
                                        │
        ┌───────────────────────────────┼───────────────────────────────┐
        │                               │                               │
        ▼                               ▼                               ▼
┌───────────────┐              ┌───────────────┐              ┌───────────────┐
│  MASTER DATA  │              │  OPERASIONAL  │              │   REPORTING   │
├───────────────┤              ├───────────────┤              ├───────────────┤
│ • Suppliers   │              │ • Queue Mgmt  │              │ • Dashboard   │
│ • Customers   │              │ • Weighing    │              │ • Daily Report│
│ • Trucks      │              │ • Sortation   │              │ • Weekly      │
│ • TBS Prices  │              │ • Production  │              │ • Monthly     │
│ • Users       │              │ • Stock       │              │ • Margin      │
│               │              │ • Sales       │              │ • Efficiency  │
└───────────────┘              └───────────────┘              └───────────────┘
```

---

## 2. Alur Utama (Main Flow)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              MAIN OPERATIONAL FLOW                              │
└─────────────────────────────────────────────────────────────────────────────────┘

    ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
    │  TRUCK   │     │  QUEUE   │     │ WEIGHING │     │ SORTASI  │     │ WEIGHING │
    │ ARRIVAL  │────►│  SYSTEM  │────►│ (BRUTO)  │────►│   TBS    │────►│  (TARA)  │
    └──────────┘     └──────────┘     └──────────┘     └──────────┘     └──────────┘
         │                │                │                │                │
         │                │                │                │                │
         ▼                ▼                ▼                ▼                ▼
    ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
    │ Register │     │ Generate │     │  Record  │     │  Grade   │     │  Record  │
    │  Truck   │     │  Queue # │     │   Bruto  │     │   TBS    │     │   Tara   │
    │ & Driver │     │ + Bank   │     │  Weight  │     │ Quality  │     │  Weight  │
    └──────────┘     └──────────┘     └──────────┘     └──────────┘     └──────────┘
                                                                             │
                                                                             ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                                                                                 │
│    ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐             │
│    │  STOCK   │◄────│PRODUCTION│◄────│ STOCK    │◄────│ CALCULATE│             │
│    │   CPO    │     │ PROCESS  │     │   TBS    │     │  NETTO   │◄────────────┘
│    │  KERNEL  │     │  (Mill)  │     │ Created  │     │  WEIGHT  │
│    │  SHELL   │     │          │     │          │     │ + PRICE  │
│    └────┬─────┘     └──────────┘     └──────────┘     └──────────┘
│         │                                                                       │
└─────────┼───────────────────────────────────────────────────────────────────────┘
          │
          ▼
    ┌──────────┐     ┌──────────┐     ┌──────────┐
    │  SALES   │────►│ DELIVERY │────►│ COMPLETE │
    │  ORDER   │     │          │     │          │
    └──────────┘     └──────────┘     └──────────┘
```

---

## 3. Alur Antrian & Penimbangan

### 3.1 Queue Management Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    QUEUE MANAGEMENT FLOW                        │
└─────────────────────────────────────────────────────────────────┘

                        ┌───────────────┐
                        │  TRUCK ARRIVES│
                        │   at Gate     │
                        └───────┬───────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Check Truck Data    │
                    │   (Plate Number)      │
                    └───────────┬───────────┘
                                │
                    ┌───────────┴───────────┐
                    │                       │
                    ▼                       ▼
            ┌───────────────┐       ┌───────────────┐
            │  TRUCK EXISTS │       │  NEW TRUCK    │
            └───────┬───────┘       └───────┬───────┘
                    │                       │
                    │                       ▼
                    │               ┌───────────────┐
                    │               │ Register New  │
                    │               │ Truck + Driver│
                    │               └───────┬───────┘
                    │                       │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │  Select Supplier      │
                    │  (Inti/Plasma/Umum)   │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │  Generate Queue       │
                    │  Number + Assign Bank │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   STATUS: WAITING     │
                    │   (Queue Created)     │
                    └───────────┬───────────┘
                                │
                                ▼
            ┌───────────────────────────────────────┐
            │  Display Queue on Polling Screen      │
            │  /api/polling/queue                   │
            └───────────────────────────────────────┘


        QUEUE STATUS FLOW:
        ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
        │ WAITING  │───►│PROCESSING│───►│ WEIGHING │───►│COMPLETED │
        └──────────┘    └──────────┘    └──────────┘    └──────────┘
              │                                              │
              └──────────────────► CANCELLED ◄───────────────┘
```

### 3.2 Weighing (Weighbridge) Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     WEIGHING PROCESS FLOW                       │
└─────────────────────────────────────────────────────────────────┘

                        ┌───────────────┐
                        │ Queue Called  │
                        │ for Weighing  │
                        └───────┬───────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Create Weighing     │
                    │   Record + Ticket #   │
                    │  (WB-YYYYMMDD-XXXX)   │
                    └───────────┬───────────┘
                                │
                                ▼
              ┌─────────────────────────────────────┐
              │        FIRST WEIGHING (BRUTO)       │
              ├─────────────────────────────────────┤
              │  • Truck WITH load on scale         │
              │  • Record bruto_weight              │
              │  • Record first_weighing_time       │
              │  • Operator: operator_first_id      │
              │  • Status: weighed_in               │
              └─────────────────┬───────────────────┘
                                │
                                ▼
              ┌─────────────────────────────────────┐
              │          UNLOADING & SORTATION      │
              │     (See Sortation Flow Below)      │
              └─────────────────┬───────────────────┘
                                │
                                ▼
              ┌─────────────────────────────────────┐
              │       SECOND WEIGHING (TARA)        │
              ├─────────────────────────────────────┤
              │  • Truck WITHOUT load on scale      │
              │  • Record tara_weight               │
              │  • Record second_weighing_time      │
              │  • Operator: operator_second_id     │
              │  • Status: weighed_out              │
              └─────────────────┬───────────────────┘
                                │
                                ▼
              ┌─────────────────────────────────────┐
              │         AUTO CALCULATION            │
              ├─────────────────────────────────────┤
              │  netto = bruto - tara               │
              │  price_per_kg = TBS Price (by       │
              │                 supplier type)      │
              │  total_price = netto × price_per_kg │
              └─────────────────┬───────────────────┘
                                │
                                ▼
              ┌─────────────────────────────────────┐
              │        COMPLETE WEIGHING            │
              ├─────────────────────────────────────┤
              │  • Generate Stock TBS entry         │
              │  • Update Queue to COMPLETED        │
              │  • Print Ticket                     │
              │  • Status: completed                │
              └─────────────────────────────────────┘


        WEIGHING STATUS FLOW:
        ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
        │ PENDING  │───►│WEIGHED_IN│───►│WEIGHED_  │───►│COMPLETED │
        │          │    │ (Bruto)  │    │OUT (Tara)│    │          │
        └──────────┘    └──────────┘    └──────────┘    └──────────┘
              │                                              │
              └──────────────────► CANCELLED ◄───────────────┘
```

---

## 4. Alur Sortasi & Stock TBS

### 4.1 Sortation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                      SORTATION FLOW                             │
└─────────────────────────────────────────────────────────────────┘

                        ┌───────────────┐
                        │  TBS Unloaded │
                        │  from Truck   │
                        └───────┬───────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Mandor Inspects     │
                    │   TBS Quality         │
                    └───────────┬───────────┘
                                │
                                ▼
        ┌───────────────────────────────────────────────────────┐
        │                   TBS GRADING                         │
        ├───────────────────────────────────────────────────────┤
        │                                                       │
        │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐  │
        │  │  RIPE   │  │ UNRIPE  │  │OVERRIPE │  │  EMPTY  │  │
        │  │ (Matang)│  │ (Mentah)│  │(Lewat   │  │  BUNCH  │  │
        │  │         │  │         │  │ Matang) │  │(Jangkos)│  │
        │  └─────────┘  └─────────┘  └─────────┘  └─────────┘  │
        │                                                       │
        │  ┌─────────┐  ┌─────────┐                            │
        │  │ LOOSE   │  │ GARBAGE │                            │
        │  │ FRUIT   │  │(Sampah) │                            │
        │  │(Brondol)│  │         │                            │
        │  └─────────┘  └─────────┘                            │
        │                                                       │
        └───────────────────────┬───────────────────────────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │  Calculate Final      │
                    │  Weight (after reject)│
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │  Sortation Record     │
                    │  Created              │
                    │  Status: completed    │
                    └───────────────────────┘
```

### 4.2 Stock TBS Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     STOCK TBS CREATION                          │
└─────────────────────────────────────────────────────────────────┘

        ┌───────────────┐     ┌───────────────┐
        │   Weighing    │     │   Sortation   │
        │  Completed    │     │  Completed    │
        └───────┬───────┘     └───────┬───────┘
                │                     │
                └──────────┬──────────┘
                           │
                           ▼
                ┌───────────────────────┐
                │   CREATE STOCK TBS    │
                ├───────────────────────┤
                │  • weighing_id        │
                │  • sortation_id       │
                │  • quantity (kg)      │
                │  • quality grade      │
                │  • status: available  │
                │  • location           │
                └───────────┬───────────┘
                            │
                            ▼
            ┌───────────────────────────────────┐
            │    Ready for Production Process   │
            └───────────────────────────────────┘


        STOCK TBS STATUS:
        ┌───────────┐    ┌───────────┐    ┌───────────┐
        │ AVAILABLE │───►│PROCESSING │───►│ PROCESSED │
        └───────────┘    └───────────┘    └───────────┘
```

---

## 5. Alur Produksi

```
┌─────────────────────────────────────────────────────────────────┐
│                     PRODUCTION FLOW                             │
└─────────────────────────────────────────────────────────────────┘

                        ┌───────────────┐
                        │  Stock TBS    │
                        │  Available    │
                        └───────┬───────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Create Production   │
                    │   Batch               │
                    ├───────────────────────┤
                    │  • batch_number       │
                    │  • production_date    │
                    │  • shift (1/2/3)      │
                    │  • supervisor_id      │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   TBS Input           │
                    │   (from Stock TBS)    │
                    │   tbs_input_kg        │
                    └───────────┬───────────┘
                                │
                                ▼
        ┌───────────────────────────────────────────────────────┐
        │                    MILL PROCESS                       │
        │                  (Palm Oil Mill)                      │
        ├───────────────────────────────────────────────────────┤
        │                                                       │
        │   TBS ──► STERILIZER ──► THRESHER ──► DIGESTER       │
        │                                          │            │
        │                              ┌───────────┴────────┐   │
        │                              ▼                    ▼   │
        │                         ┌────────┐          ┌────────┐│
        │                         │ PRESS  │          │ KERNEL ││
        │                         │ STATION│          │STATION ││
        │                         └───┬────┘          └───┬────┘│
        │                             │                   │     │
        │                             ▼                   ▼     │
        │                         ┌────────┐          ┌────────┐│
        │                         │  CPO   │          │KERNEL+ ││
        │                         │OUTPUT  │          │ SHELL  ││
        │                         └────────┘          └────────┘│
        │                                                       │
        └───────────────────────────┬───────────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────────────┐
                    │        PRODUCTION OUTPUT          │
                    ├───────────────────────────────────┤
                    │  • cpo_output_kg      → STOCK CPO │
                    │  • kernel_output_kg   → STOCK     │
                    │                         KERNEL    │
                    │  • shell_output_kg    → STOCK     │
                    │                         SHELL     │
                    └───────────────────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────────────┐
                    │     EFFICIENCY CALCULATION        │
                    ├───────────────────────────────────┤
                    │  OER = (cpo_output / tbs_input)   │
                    │        × 100%                     │
                    │                                   │
                    │  KER = (kernel_output / tbs_input)│
                    │        × 100%                     │
                    └───────────────────────────────────┘


        PRODUCTION STATUS:
        ┌──────────┐    ┌──────────┐    ┌──────────┐
        │ PENDING  │───►│PROCESSING│───►│COMPLETED │
        └──────────┘    └──────────┘    └──────────┘
```

---

## 6. Alur Stock Management

### 6.1 Stock Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    STOCK MANAGEMENT FLOW                        │
└─────────────────────────────────────────────────────────────────┘

                                    │
            ┌───────────────────────┼───────────────────────┐
            │                       │                       │
            ▼                       ▼                       ▼
    ┌───────────────┐       ┌───────────────┐       ┌───────────────┐
    │   STOCK CPO   │       │ STOCK KERNEL  │       │  STOCK SHELL  │
    ├───────────────┤       ├───────────────┤       ├───────────────┤
    │ • Tank Number │       │ • Location    │       │ • Location    │
    │ • Quantity    │       │ • Quantity    │       │ • Quantity    │
    │ • Quality     │       │ • Quality     │       │ • Status      │
    │ • FFA Level   │       │ • Status      │       │               │
    │ • Status      │       │               │       │               │
    └───────┬───────┘       └───────┬───────┘       └───────┬───────┘
            │                       │                       │
            └───────────────────────┼───────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────┐
                    │     STOCK OPERATIONS      │
                    ├───────────────────────────┤
                    │  • Stock Opname (Audit)   │
                    │  • Stock Adjustment       │
                    │  • Stock Purchase         │
                    │  • Sales Deduction        │
                    └───────────────────────────┘
```

### 6.2 Stock Opname (Physical Inventory)

```
┌─────────────────────────────────────────────────────────────────┐
│                     STOCK OPNAME FLOW                           │
└─────────────────────────────────────────────────────────────────┘

                        ┌───────────────┐
                        │  Schedule     │
                        │  Stock Opname │
                        └───────┬───────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Physical Count      │
                    │   by Staff            │
                    │   (counted_by)        │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Record Results      │
                    ├───────────────────────┤
                    │  • product_type       │
                    │  • physical_stock     │
                    │  • system_stock       │
                    │  • variance           │
                    └───────────┬───────────┘
                                │
                                ▼
                ┌───────────────┴───────────────┐
                │                               │
                ▼                               ▼
        ┌───────────────┐               ┌───────────────┐
        │  NO VARIANCE  │               │   VARIANCE    │
        │  (Match)      │               │   DETECTED    │
        └───────┬───────┘               └───────┬───────┘
                │                               │
                ▼                               ▼
        ┌───────────────┐               ┌───────────────┐
        │   Verify by   │               │   Create      │
        │   Supervisor  │               │   Adjustment  │
        │   (verified_by)               │   Request     │
        └───────────────┘               └───────────────┘


        OPNAME STATUS:
        ┌──────────┐    ┌──────────┐    ┌──────────┐
        │ PENDING  │───►│ VERIFIED │───►│ APPROVED │
        └──────────┘    └──────────┘    └──────────┘
```

### 6.3 Stock Adjustment

```
┌─────────────────────────────────────────────────────────────────┐
│                   STOCK ADJUSTMENT FLOW                         │
└─────────────────────────────────────────────────────────────────┘

                        ┌───────────────┐
                        │  Variance     │
                        │  Detected     │
                        └───────┬───────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │  Create Adjustment    │
                    │  Request              │
                    ├───────────────────────┤
                    │  • product_type       │
                    │  • adjustment_type    │
                    │    (increase/decrease)│
                    │  • quantity           │
                    │  • reason             │
                    │  • adjusted_by        │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   STATUS: PENDING     │
                    │   (Awaiting Approval) │
                    └───────────┬───────────┘
                                │
                                ▼
                ┌───────────────┴───────────────┐
                │                               │
                ▼                               ▼
        ┌───────────────┐               ┌───────────────┐
        │   APPROVED    │               │   REJECTED    │
        │  (by Admin)   │               │  (by Admin)   │
        └───────┬───────┘               └───────────────┘
                │
                ▼
        ┌───────────────┐
        │  Update Stock │
        │  Quantity     │
        └───────────────┘
```

---

## 7. Alur Penjualan

```
┌─────────────────────────────────────────────────────────────────┐
│                       SALES FLOW                                │
└─────────────────────────────────────────────────────────────────┘

                        ┌───────────────┐
                        │   Customer    │
                        │   Order       │
                        └───────┬───────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Check Available     │
                    │   Stock               │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Create Sales Order  │
                    ├───────────────────────┤
                    │  • so_number          │
                    │  • customer_id        │
                    │  • product_type       │
                    │    (CPO/Kernel/Shell) │
                    │  • quantity           │
                    │  • price_per_kg       │
                    │  • total_amount       │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   Create Sales Detail │
                    ├───────────────────────┤
                    │  • Link to Stock      │
                    │    (stock_cpo_id,     │
                    │     stock_kernel_id,  │
                    │     stock_shell_id)   │
                    │  • qty_sold           │
                    │  • Reserve Stock      │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │   STATUS: PENDING     │
                    └───────────┬───────────┘
                                │
                                ▼
              ┌─────────────────────────────────────┐
              │           DELIVERY PROCESS          │
              ├─────────────────────────────────────┤
              │  • Assign truck                     │
              │  • Load products                    │
              │  • Delivery note                    │
              │  • STATUS: DELIVERING               │
              └─────────────────┬───────────────────┘
                                │
                                ▼
              ┌─────────────────────────────────────┐
              │           COMPLETE SALE             │
              ├─────────────────────────────────────┤
              │  • Confirm delivery                 │
              │  • Deduct from stock                │
              │  • Update stock status: sold        │
              │  • STATUS: COMPLETED                │
              └─────────────────────────────────────┘


        SALES STATUS FLOW:
        ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
        │ PENDING  │───►│DELIVERING│───►│DELIVERED │───►│COMPLETED │
        └──────────┘    └──────────┘    └──────────┘    └──────────┘
              │                                              │
              └──────────────────► CANCELLED ◄───────────────┘
```

---

## 8. Role-based Access Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER ROLE ACCESS FLOW                        │
└─────────────────────────────────────────────────────────────────┘


        ┌──────────────────────────────────────────────────────────────┐
        │                         ADMIN                                │
        ├──────────────────────────────────────────────────────────────┤
        │  FULL ACCESS: Create, Read, Update, Delete ALL modules       │
        │  • User Management                                           │
        │  • Master Data (Suppliers, Customers, Trucks, TBS Prices)    │
        │  • Operational (Queue, Weighing, Sortation, Production)      │
        │  • Stock Management (Opname, Adjustment, Purchase)           │
        │  • Sales Management                                          │
        │  • Activity Logs                                             │
        │  • Reports & Dashboard                                       │
        └──────────────────────────────────────────────────────────────┘

        ┌──────────────────────────────────────────────────────────────┐
        │                        MANAGER                               │
        ├──────────────────────────────────────────────────────────────┤
        │  VIEW ONLY: Can view all data but cannot modify              │
        │  • View Dashboard & Reports                                  │
        │  • View Master Data                                          │
        │  • View Operational Data                                     │
        │  • View Stock & Sales Data                                   │
        └──────────────────────────────────────────────────────────────┘

        ┌──────────────────────────────────────────────────────────────┐
        │                        MANDOR                                │
        ├──────────────────────────────────────────────────────────────┤
        │  OPERATIONAL: Can add operational data                       │
        │  • Add Queue entries                                         │
        │  • Add Weighing records                                      │
        │  • Add Sortation records                                     │
        │  • Add Production records                                    │
        │  • Add new Trucks                                            │
        │  • View all data                                             │
        └──────────────────────────────────────────────────────────────┘

        ┌──────────────────────────────────────────────────────────────┐
        │                      ACCOUNTING                              │
        ├──────────────────────────────────────────────────────────────┤
        │  FINANCIAL: Can manage financial-related data                │
        │  • Add Suppliers & Customers                                 │
        │  • Add Sales orders                                          │
        │  • Add Stock Purchases                                       │
        │  • Add Stock Opname & Adjustments                            │
        │  • Add Stock (CPO, Kernel, Shell)                            │
        │  • View all financial data                                   │
        │  • Cannot delete anything                                    │
        └──────────────────────────────────────────────────────────────┘

        ┌──────────────────────────────────────────────────────────────┐
        │                   OPERATOR TIMBANGAN                         │
        ├──────────────────────────────────────────────────────────────┤
        │  WEIGHBRIDGE OPERATOR: Focused on weighing operations        │
        │  • Add Queue entries                                         │
        │  • Add Weighing records (Bruto & Tara)                       │
        │  • Process Weigh-in & Weigh-out                              │
        │  • Add Sortation records                                     │
        │  • Add Production records                                    │
        │  • Add new Trucks                                            │
        │  • Cannot edit or delete                                     │
        └──────────────────────────────────────────────────────────────┘


        ┌──────────────────────────────────────────────────────────────┐
        │                    AUTHENTICATION FLOW                       │
        └──────────────────────────────────────────────────────────────┘

                           ┌─────────────┐
                           │   LOGIN     │
                           │ POST /auth  │
                           │   /login    │
                           └──────┬──────┘
                                  │
                                  ▼
                      ┌───────────────────────┐
                      │   Validate Email &    │
                      │   Password            │
                      └───────────┬───────────┘
                                  │
                  ┌───────────────┴───────────────┐
                  │                               │
                  ▼                               ▼
          ┌───────────────┐               ┌───────────────┐
          │   SUCCESS     │               │    FAILED     │
          │               │               │   (401)       │
          └───────┬───────┘               └───────────────┘
                  │
                  ▼
          ┌───────────────┐
          │  Generate     │
          │  Sanctum      │
          │  Token        │
          └───────┬───────┘
                  │
                  ▼
          ┌───────────────┐
          │  Return User  │
          │  + Token      │
          └───────┬───────┘
                  │
                  ▼
    ┌─────────────────────────────────────────┐
    │   Use Token in Header for API calls:   │
    │   Authorization: Bearer {token}        │
    └─────────────────────────────────────────┘
```

---

## 9. Integration & Polling Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                 POLLING & DISPLAY INTEGRATION                   │
└─────────────────────────────────────────────────────────────────┘

    PUBLIC ENDPOINTS (No Auth Required):

    ┌─────────────────────────────────────────────────────────────┐
    │  /api/polling/queue                                         │
    │  • Active queue display for waiting area                    │
    │  • Shows queue numbers, estimated wait time                 │
    └─────────────────────────────────────────────────────────────┘
              │
              ▼
    ┌─────────────────────────────────────────────────────────────┐
    │  /api/polling/weighing                                      │
    │  • Current weighing status display                          │
    │  • Shows truck on scale, weights                            │
    └─────────────────────────────────────────────────────────────┘
              │
              ▼
    ┌─────────────────────────────────────────────────────────────┐
    │  /api/polling/stock                                         │
    │  • Real-time stock levels                                   │
    │  • CPO tanks, Kernel, Shell quantities                      │
    └─────────────────────────────────────────────────────────────┘
              │
              ▼
    ┌─────────────────────────────────────────────────────────────┐
    │  /api/polling/production                                    │
    │  • Current production batch status                          │
    │  • OER/KER efficiency                                       │
    └─────────────────────────────────────────────────────────────┘
              │
              ▼
    ┌─────────────────────────────────────────────────────────────┐
    │  /api/polling/dashboard                                     │
    │  • Overall mill statistics                                  │
    │  • Daily summary for display screens                        │
    └─────────────────────────────────────────────────────────────┘
```

---

## 10. Complete System Overview

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         TBS SYSTEM - COMPLETE FLOW                              │
│                      Palm Oil Mill Management System                            │
└─────────────────────────────────────────────────────────────────────────────────┘

  INBOUND                     PROCESSING                        OUTBOUND
  ═══════                     ══════════                        ════════

  ┌─────────┐                 ┌─────────┐                      ┌─────────┐
  │SUPPLIER │                 │   PKS   │                      │CUSTOMER │
  │ (Inti/  │                 │ (Mill)  │                      │         │
  │ Plasma/ │                 │         │                      │         │
  │ Umum)   │                 │         │                      │         │
  └────┬────┘                 └────┬────┘                      └────┬────┘
       │                           │                                │
       ▼                           │                                │
  ┌─────────┐                      │                                │
  │  TRUCK  │                      │                                │
  │ ARRIVAL │                      │                                │
  └────┬────┘                      │                                │
       │                           │                                │
       ▼                           │                                │
  ┌─────────┐                      │                                │
  │  QUEUE  │                      │                                │
  │ SYSTEM  │                      │                                │
  └────┬────┘                      │                                │
       │                           │                                │
       ▼                           │                                │
  ┌─────────┐    ┌─────────┐       │                                │
  │ WEIGH   │───►│ SORTASI │       │                                │
  │ BRUTO   │    │   TBS   │       │                                │
  └────┬────┘    └────┬────┘       │                                │
       │              │            │                                │
       │              ▼            │                                │
       │         ┌─────────┐       │                                │
       │         │  STOCK  │       │                                │
       │         │   TBS   │       │                                │
       │         └────┬────┘       │                                │
       │              │            │                                │
       ▼              ▼            ▼                                │
  ┌─────────┐    ┌──────────────────────┐                           │
  │ WEIGH   │    │     PRODUCTION       │                           │
  │  TARA   │    │  ┌────┬────┬────┐    │                           │
  └────┬────┘    │  │CPO │KRNL│SHLL│    │                           │
       │         │  └─┬──┴─┬──┴─┬──┘    │                           │
       │         └────┼────┼────┼───────┘                           │
       │              │    │    │                                   │
       ▼              ▼    ▼    ▼                                   │
  ┌─────────┐    ┌─────────────────┐                                │
  │ TICKET  │    │   STOCK MGMT   │                                 │
  │ PRINTED │    │ ┌───┐┌───┐┌───┐│         ┌─────────┐            │
  └─────────┘    │ │CPO││KNL││SHL││────────►│  SALES  │────────────►
                 │ └───┘└───┘└───┘│         │  ORDER  │
                 └────────────────┘         └────┬────┘
                        │                        │
                        ▼                        ▼
                 ┌─────────────┐          ┌─────────┐
                 │STOCK OPNAME │          │DELIVERY │
                 │& ADJUSTMENT │          └────┬────┘
                 └─────────────┘               │
                                               ▼
                                          ┌─────────┐
                                          │COMPLETE │
                                          └─────────┘

─────────────────────────────────────────────────────────────────────────────────
                            SUPPORTING SYSTEMS
─────────────────────────────────────────────────────────────────────────────────

  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
  │ TBS PRICES   │  │  DASHBOARD   │  │   REPORTS    │  │ACTIVITY LOGS │
  │ (Daily Rate) │  │ (Real-time)  │  │(Daily/Weekly │  │ (Audit Trail)│
  └──────────────┘  └──────────────┘  │  /Monthly)   │  └──────────────┘
                                      └──────────────┘
```

---

> **Document Version**: 1.0  
> **Last Updated**: January 2026  
> **System**: TBS System - Palm Oil Mill Management
