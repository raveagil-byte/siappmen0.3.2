# CSSD System Conflict Fixes - Implementation Plan

## Issues Identified
1. **API Endpoint Mismatch**: Frontend calls /auth/login, backend has /login
2. **Response Parsing Error**: Frontend expects response.data.token, backend returns response.data.data.token
3. **Database Schema Mismatch**: Migration creates 'quantity'/'status', code uses 'stock_steril'/'stock_kotor'/'stock_in_use'
4. **TransactionService Logic Errors**: Missing CSSD stock updates, invalid column references

## Implementation Steps

### Phase 1: Frontend API Fixes
- [ ] Update AuthContext.jsx to use correct endpoints (/login instead of /auth/login)
- [ ] Fix response parsing to handle response.data.data structure
- [ ] Remove register functionality (not supported by backend)

### Phase 2: Database Schema Fix
- [ ] Create migration to add stock_steril, stock_kotor, stock_in_use columns
- [ ] Run migration to update existing database

### Phase 3: Backend Logic Fixes
- [ ] Fix TransactionService createKotorTransaction to increment CSSD stock
- [ ] Fix TransactionService cancelTransaction to remove stock_cssd references
- [ ] Ensure proper stock reversal logic

### Phase 4: Testing & Validation
- [ ] Run test_comprehensive.php to verify fixes
- [ ] Test login functionality
- [ ] Test transaction creation and validation
- [ ] Verify stock tracking works correctly

## Files to Modify
- frontend-web/src/contexts/AuthContext.jsx
- backend/database/migrations/ (new migration file)
- backend/app/Services/TransactionService.php

## Risk Assessment
- **High Risk**: Database migration may affect existing data
- **Medium Risk**: API changes may break frontend functionality
- **Low Risk**: TransactionService fixes are isolated to business logic

## Rollback Plan
- Database: Create down() method in migration
- Frontend: Keep backup of original AuthContext.jsx
- Backend: Version control allows easy rollback
