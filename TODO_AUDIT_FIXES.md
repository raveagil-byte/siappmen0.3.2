# CSSD App Audit & Fix TODO List

## Database Fixes
- [ ] Update instrument_unit_status.status to enum('steril','unit','kotor','cssd')
- [ ] Create InstrumentUnitStatus model
- [ ] Update TransactionService to properly manage status transitions

## Backend Fixes
- [ ] Fix TransactionService status updates
- [ ] Add missing model relationships
- [ ] Verify middleware application

## Mobile Fixes
- [ ] Implement offline transaction storage
- [ ] Fix API baseURL configuration
- [ ] Add sync mechanism

## Frontend Fixes
- [ ] Add QR display to Unit and Transaction views
- [ ] Remove unused Scanner.jsx component
- [ ] Verify scanner functionality

## Security & Testing
- [ ] Verify rate limiting works
- [ ] Test complete workflow
- [ ] Add input validation
