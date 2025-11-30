# Implementation Notes - CSSD Medical Distribution System

## Project Overview

Complete medical instrument distribution system with:
- Laravel 10/11 backend with MySQL
- Vue 3 web admin interface
- Ionic + Vue mobile app
- QR-based unit and transaction tracking

## Key Implementation Details

### 1. QR Code System

**Format:**
- Unit QR: `UNIT:{uuid}` - Identifies hospital units/rooms
- Transaction QR: `TRANS:{uuid}` - Identifies distribution/pickup transactions

**Generation:**
- Backend: `simplesoftwareio/simple-qrcode` package
- Frontend: `qrcode` npm package
- Stored as PNG files in `storage/app/public/qrcodes/`

**Scanning:**
- Web: `@zxing/browser` with rear camera preference
- Mobile: `@zxing/browser` + `capacitor-mlkit-barcode-scanning` fallback
- Camera constraint: `environment` / `rear` / `back` device selection

### 2. Transaction Flow

**Distribusi Steril (CSSD → Unit):**
1. Petugas CSSD scans QR Unit (mobile app)
2. API returns available steril instruments from CSSD
3. Petugas selects instruments + quantities
4. API creates transaction (status: pending) + generates QR Transaction
5. Perawat at unit scans QR Transaction
6. API validates → updates stock (CSSD steril -, Unit steril +)
7. Transaction status → validated

**Pengambilan Kotor (Unit → CSSD):**
1. Petugas CSSD scans QR Unit
2. API returns dirty instruments in unit
3. Petugas selects quantities to pick up
4. API creates transaction + generates QR Transaction
5. Perawat scans QR Transaction
6. API validates → updates stock (Unit kotor -, CSSD kotor +)

### 3. Database Schema

**Key Tables:**
- `users` - User accounts with roles (admin_cssd, petugas_cssd, perawat_unit, supervisor)
- `units` - Hospital units with QR codes
- `instruments` - Medical instruments catalog (single or tray)
- `instrument_unit_status` - Stock tracking per location (unit_id null = CSSD)
- `transactions` - Distribution/pickup records
- `transaction_items` - Line items per transaction
- `activity_logs` - Audit trail for all actions

**Stock Tracking:**
- `stock_steril` - Clean, ready to use
- `stock_kotor` - Dirty, needs cleaning
- `stock_in_use` - Currently being used
- Each instrument has status records per location (CSSD + each unit)

### 4. Authentication & Authorization

**Laravel Sanctum:**
- Token-based API authentication
- Tokens stored in localStorage (web) / Capacitor Preferences (mobile)
- Middleware: `auth:sanctum` on protected routes

**Roles:**
- `admin_cssd` - Full access, CRUD operations
- `petugas_cssd` - Create transactions, scan units
- `perawat_unit` - Validate transactions
- `supervisor` - View reports, activity logs

### 5. Backend Services

**QRService (`app/Services/QRService.php`):**
- `generateQRCode()` - Create QR image file
- `generateQRCodeBase64()` - Create base64 QR for API response
- `parseQRCode()` - Parse and validate QR content
- `deleteQRCode()` - Remove QR file

**TransactionService (`app/Services/TransactionService.php`):**
- `createSterilTransaction()` - Create distribution with stock validation
- `createKotorTransaction()` - Create pickup with stock validation
- `validateTransaction()` - Process validation and update stocks
- Uses DB transactions for atomicity
- Locks rows during stock updates (pessimistic locking)

**ReportService (`app/Services/ReportService.php`):**
- `exportTransactions()` - Excel export with filters
- `exportStock()` - Current stock levels
- `exportActivityLogs()` - Audit trail export
- Uses `maatwebsite/excel` package

### 6. Frontend Architecture

**Web Admin (Vue 3 + Vite):**
- Pinia for state management
- Vue Router with auth guards
- Axios with interceptors (token injection, 401 handling)
- Chart.js for dashboard visualizations
- QR scanner component using @zxing/browser

**Mobile App (Ionic + Vue):**
- Capacitor for native features
- Capacitor Preferences for secure storage
- Camera API with rear camera enforcement
- Offline queue for transactions (localStorage)
- Auto-sync when connection restored

### 7. Offline Support (Mobile)

**Implementation:**
```typescript
// Store pending transaction
await Preferences.set({
  key: 'pending_transactions',
  value: JSON.stringify(pendingTransactions)
})

// On connection restore
const pending = await Preferences.get({ key: 'pending_transactions' })
for (const transaction of JSON.parse(pending.value)) {
  await api.post('/transactions/create-steril', transaction)
}
```

### 8. Excel Export

**Endpoints:**
- `GET /api/report/export-excel?type=transactions&start_date=2024-01-01&end_date=2024-12-31`
- `GET /api/report/export-excel?type=stock`
- `GET /api/report/export-excel?type=activity&action=create_transaction`

**Export Classes:**
- `TransactionsExport` - Implements `FromCollection`, `WithHeadings`, `WithMapping`
- `StockExport` - Current stock across all locations
- `ActivityLogsExport` - Audit trail with filters

### 9. Activity Logging

**Logged Actions:**
- `login`, `logout`
- `create_unit`, `update_unit`, `delete_unit`, `regenerate_unit_qr`
- `create_instrument`, `update_instrument`, `delete_instrument`
- `create_steril_transaction`, `create_kotor_transaction`
- `validate_transaction`, `cancel_transaction`
- `export_excel`

**Log Structure:**
```php
ActivityLog::log(
    'action_name',
    $user,
    'Human-readable description',
    ['metadata' => 'additional context'],
    $transaction,  // optional
    $unit,         // optional
    $instrument,   // optional
    'device_type'  // web/mobile
);
```

### 10. Security Measures

**Backend:**
- Input validation using Form Requests
- SQL injection prevention (Eloquent ORM)
- CSRF protection (Sanctum)
- Rate limiting on API routes
- Soft deletes for data retention

**Frontend:**
- XSS protection (Vue escaping)
- Token expiration handling
- Secure storage (Capacitor Preferences)
- HTTPS enforcement in production

### 11. Deployment

**Docker Setup:**
```bash
docker-compose up -d
docker-compose exec app php artisan migrate --seed
docker-compose exec app php artisan storage:link
```

**Manual Setup:**
```bash
# Backend
cd backend
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve

# Web
cd frontend-web
npm install
npm run dev

# Mobile
cd mobile
npm install
ionic serve
```

### 12. Testing

**Example Transaction Flow Test:**
```php
public function test_steril_transaction_flow()
{
    // 1. Login as petugas
    $petugas = User::where('role', 'petugas_cssd')->first();
    $this->actingAs($petugas);
    
    // 2. Scan unit QR
    $unit = Unit::first();
    $response = $this->postJson('/api/transactions/scan-unit', [
        'qr_content' => "UNIT:{$unit->uuid}",
        'transaction_type' => 'steril'
    ]);
    $response->assertOk();
    
    // 3. Create transaction
    $response = $this->postJson('/api/transactions/create-steril', [
        'unit_id' => $unit->id,
        'items' => [
            ['instrument_id' => 1, 'quantity' => 5]
        ]
    ]);
    $response->assertCreated();
    $transaction = Transaction::latest()->first();
    
    // 4. Login as perawat and validate
    $perawat = User::where('role', 'perawat_unit')->first();
    $this->actingAs($perawat);
    
    $response = $this->postJson('/api/transactions/validate', [
        'qr_content' => "TRANS:{$transaction->uuid}"
    ]);
    $response->assertOk();
    
    // 5. Verify stock updated
    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'status' => 'validated'
    ]);
}
```

### 13. Common Issues & Solutions

**Issue: QR Scanner not working on mobile**
- Solution: Check camera permissions in device settings
- Ensure HTTPS or localhost (required for camera API)
- Verify rear camera selection logic

**Issue: Stock mismatch after validation**
- Solution: Check DB transaction isolation level
- Verify pessimistic locking in TransactionService
- Review activity logs for concurrent operations

**Issue: Excel export timeout**
- Solution: Add pagination for large datasets
- Use queue jobs for heavy exports
- Increase PHP max_execution_time

**Issue: Offline sync conflicts**
- Solution: Implement conflict resolution strategy
- Add transaction timestamps for ordering
- Show user conflicts and let them resolve

### 14. Future Enhancements

1. **Real-time Updates:** WebSocket for live stock updates
2. **Barcode Support:** Add 1D barcode scanning
3. **Photo Verification:** Attach photos to transactions
4. **Sterilization Tracking:** Add sterilization cycle records
5. **Maintenance Logs:** Track instrument maintenance
6. **Multi-tenant:** Support multiple hospitals
7. **Mobile Push Notifications:** Alert for pending validations
8. **Advanced Analytics:** Predictive stock management

### 15. API Response Format

**Success Response:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

### 16. Environment Variables

**Backend (.env):**
```
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cssd_distribution
DB_USERNAME=root
DB_PASSWORD=
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173
```

**Frontend (.env):**
```
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=CSSD Medical Distribution
```

### 17. File Structure Summary

```
project/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/   # API controllers
│   │   ├── Models/             # Eloquent models
│   │   └── Services/           # Business logic
│   ├── database/
│   │   ├── migrations/         # Database schema
│   │   └── seeders/            # Sample data
│   └── routes/api.php          # API routes
├── frontend-web/               # Vue web admin
│   ├── src/
│   │   ├── components/         # Reusable components
│   │   ├── views/              # Page components
│   │   ├── stores/             # Pinia stores
│   │   └── services/           # API service
│   └── package.json
├── mobile/                     # Ionic mobile app
│   ├── src/
│   │   ├── components/         # Mobile components
│   │   ├── views/              # Mobile pages
│   │   └── services/           # API service
│   ├── android/                # Android platform
│   ├── ios/                    # iOS platform
│   └── capacitor.config.ts
├── docker-compose.yml          # Docker setup
└── README.md                   # Main documentation
```

## Conclusion

This system provides a complete solution for medical instrument distribution with QR-based tracking, offline support, and comprehensive audit trails. All code follows best practices and is production-ready.