# Fix Login Issue

## Issues Identified
1. Frontend API base URL is pointing to localhost:5173 (frontend port) instead of localhost:8000 (backend port)
2. Frontend calls /auth/login but backend route is /login
3. Frontend expects response with direct 'token' and 'user' keys, but backend returns them under 'data' object
4. Frontend calls /auth/profile but backend route is /user
5. Backend does not have a register endpoint

## Tasks
- [ ] Update frontend-web/.env to set VITE_API_URL=http://localhost:8000/api
- [ ] Update AuthContext.jsx to use correct endpoints (/login instead of /auth/login, /user instead of /auth/profile)
- [ ] Update AuthContext.jsx to parse response correctly (response.data.data.token, response.data.data.user)
- [ ] Remove or comment out register functionality since backend doesn't support it
- [ ] Test login functionality
