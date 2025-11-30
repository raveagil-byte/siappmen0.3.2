# Medical Instrument Distribution System (CSSD QR-Based)

Sistem distribusi instrumen medis berbasis QR Unit dengan fitur transaksi distribusi steril dan pengambilan kotor.

## 🏗️ Architecture

- **Backend**: Laravel 10/11 + MySQL + Sanctum Auth
- **Web Admin**: Vue 3 + Vite + Chart.js
- **Mobile App**: Ionic + Vue + Capacitor
- **QR System**: Unit-based QR codes (UNIT:{uuid}) dan Transaction QR (TRANS:{uuid})

## 📋 Features

### Core Features
1. **Asset Management** (Admin CSSD)
   - CRUD Instruments & Tray/Packages
   - Generate QR Unit per ruangan
   - Stock management (steril/kotor/in-use)

2. **Distribusi Steril** (CSSD → Unit)
   - Scan QR Unit (rear camera)
   - Select instruments & quantity
   - Generate Transaction QR
   - Validation by nurse (scan TRANS QR)

3. **Pengambilan Kotor** (Unit → CSSD)
   - Scan QR Unit
   - View dirty instruments in unit
   - Pick quantity to retrieve
   - Generate Transaction QR
   - Validation updates stock

4. **Audit & Reporting**
   - Activity logs (all actions tracked)
   - Dashboard with charts
   - Excel export (transactions, stock, activity)

### Security
- Laravel Sanctum token authentication
- RBAC: admin_cssd, petugas_cssd, perawat_unit, supervisor
- Transaction sequence validation
- Camera permission management for rear camera usage

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- Composer
- Node.js 18+
- MySQL 8.0+
- Docker (optional)

### Backend Setup

```bash
cd backend
composer install
cp .env.example .env
# Edit .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

### Web Admin Setup

```bash
cd frontend-web
npm install
cp .env.example .env
# Edit .env: VITE_API_URL=http://localhost:8000
npm run dev
```

### Mobile App Setup

```bash
cd mobile
npm install
ionic serve
# For native build:
ionic cap add android
ionic cap add ios
ionic cap sync
ionic cap open android
```

## 🐳 Docker Setup (Recommended)

```bash
docker-compose up -d
docker-compose exec app php artisan migrate --seed
```

Access:
- Backend API: http://localhost:8000
- Web Admin: http://localhost:5173
- phpMyAdmin: http://localhost:8080

## 👥 Default Users (after seeding)

| Role | Email | Password |
|------|-------|----------|
| Admin CSSD | admin@cssd.com | password |
| Petugas CSSD | petugas@cssd.com | password |
| Perawat Unit | perawat@unit.com | password |
| Supervisor | supervisor@cssd.com | password |

## 📱 QR Formats

- **QR Unit**: `UNIT:{uuid}` - Identifies room/unit
- **QR Transaction**: `TRANS:{uuid}` - Identifies distribution/pickup transaction

## 🔄 Transaction Flow

### Distribusi Steril
1. Petugas CSSD scan QR Unit (mobile, rear camera)
2. System shows available steril instruments
3. Petugas selects instruments + quantity
4. System creates transaction (pending) + generates QR Transaction
5. Perawat scan QR Transaction to validate
6. Validation updates stock (unit +, cssd -)

### Pengambilan Kotor
1. Petugas CSSD scan QR Unit
2. System shows dirty instruments in unit
3. Petugas selects quantity to pick
4. System creates transaction + generates QR Transaction
5. Perawat scan QR Transaction to validate
6. Validation updates stock (unit -, cssd dirty +)

## 📊 Database Schema

Main tables:
- `users`
- `units`
- `instruments`
- `instrument_unit_status`
- `transactions`
- `transaction_items`
- `activity_logs`

## 🔌 API Endpoints & Permissions

- Authentication: login, logout, user info
- Units: CRUD + QR generation
- Instruments: CRUD
- Transactions: create, scan, validate, cancel
- Reports: Excel export
- Activity Logs: view

## 📦 Dependencies

Key backend packages: laravel/sanctum, maatwebsite/excel, simplesoftwareio/simple-qrcode

Frontend web: vue-router, pinia, axios, chart.js, @zxing/browser, qrcode

Mobile: ionic/vue, @capacitor/core, @capacitor/camera, @zxing/browser, capacitor-mlkit-barcode-scanning, qrcode

## 🔒 Security

- Sanctum token auth with RBAC
- Input and route validation
- CSRF, XSS, and SQL injection mitigations

## 🧪 Testing

- Backend tests included with transaction flow tests
- Frontend and mobile UI manual and automated testing recommended

## 📝 Notes

- Enforce rear camera for scanning (fallback to MLKit on mobile)
- Offline support for mobile transaction queue and sync
- Activity logging for audit trail of user actions

## 📄 License

Proprietary - Hospital CSSD Management System

---
