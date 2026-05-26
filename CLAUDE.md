# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- **Backend:** PHP with PDO (MySQL) — no framework, no Composer
- **Frontend:** Vanilla HTML5/CSS3/JavaScript — no build step, no bundler, no npm
- **Database:** MySQL hosted on Hostinger
- **UI:** Font Awesome 6.4.0, Google Fonts (Inter), CSS custom properties for theming
- **Security:** Config-based credentials, ESLint for JS validation

## Project Structure

| File/Dir | Purpose |
|---|---|
| `api.php` | All backend API endpoints (GET/POST, JSON responses) |
| `includes/db_connect.php` | PDO connection — loads from config.php |
| `config.php` | Configuration file (DB & FTP credentials) — **NOT in git** |
| `database/schema.sql` | Canonical DB schema |
| `upload.sh` | FTP deployment script to Hostinger — loads from config.php |
| `style.css` | Global styles with CSS variables (`--primary`, `--secondary`, `--accent`) |
| `app.js` | Main dashboard JS logic |
| `index.html` | Reception dashboard (main entry point) |
| `patients.html` | Patient management page |
| `intake.html` | Digital patient intake form |
| `patient-details.html` | Individual patient view |
| `specialist.html` | Specialist team management |
| `reminders.html` | Automation/reminder system |

## Current Project State

**Completed:**
- Basic UI structure for all main pages (dashboard, patients, intake, specialist, reminders)
- Database schema with core tables (patients, medical_history, appointments, specialists)
- Basic API endpoints (get_patients, add_patient, get_appointments)
- Security improvements (config file, .gitignore, removed setup_db.php)
- ESLint setup for JavaScript validation

**Still Needed (High Priority):**
- Authentication system (login/logout, role-based access)
- Real database connectivity (pages currently show mock data)
- Appointment booking system with calendar
- Interactive body map for cupping points
- Arabic/RTL support throughout
- Reminder system implementation (email/SMS)
- Inventory management module

## Development Priorities

### Phase 1: Core Functionality (Week 1)
1. **Authentication System**
   - Create login page with role-based access (Reception, Specialist, Management, Purchasing)
   - Implement session management
   - Add logout functionality
   - Protect all pages with authentication

2. **Real Database Integration**
   - Connect dashboard stats to actual database queries
   - Replace mock patient data with real API calls
   - Connect waiting room to actual appointments
   - Update specialist status from database

3. **Appointment Booking**
   - Create appointment booking interface
   - Add calendar view with drag-and-drop
   - Implement appointment CRUD operations
   - Add check-in/check-out functionality

### Phase 2: Clinical Features (Week 2)
4. **Digital Intake Form**
   - Connect intake.html to API
   - Implement form validation
   - Add medical history saving
   - Create patient file generation

5. **Specialist Interface**
   - Interactive body map for cupping points
   - Treatment note system
   - Medical history review
   - Follow-up scheduling

### Phase 3: Automation (Week 3)
6. **Reminder System**
   - Email integration (SendGrid or similar)
   - SMS integration (Twilio or similar)
   - Automated daily briefing emails
   - 24-hour appointment reminders
   - Follow-up alerts (7/14 days)

7. **Inventory Management**
   - Stock tracking interface
   - Low-stock alerts
   - Reorder point management
   - Supply usage tracking

### Phase 4: Polish (Week 4)
8. **Arabic/RTL Support**
   - Add language toggle
   - Implement RTL layouts
   - Translate all UI text
   - Test Arabic input

9. **UI/UX Improvements**
   - Mobile responsiveness
   - Loading states
   - Error handling
   - User feedback

## Deployment

Deploy to Hostinger via FTP:
```bash
bash upload.sh
```
This uploads changed files directly to the live server. There is no staging environment.

**Important:** Before deploying, ensure `config.php` exists on the server with production credentials.

## Database

- Schema lives in `database/schema.sql` — update it when adding/changing tables
- Tables: `patients`, `medical_history`, `appointments`, `specialists`
- All queries in `api.php` must use PDO prepared statements (already the pattern)
- Credentials loaded from `config.php` (not in git)

## Security

- All credentials moved to `config.php` (excluded via .gitignore)
- `setup_db.php` has been deleted for security
- No authentication system yet — **HIGH PRIORITY**
- No input validation on forms — **HIGH PRIORITY**
- No CSRF protection — **MEDIUM PRIORITY**

## Coding Conventions

- PHP: PDO prepared statements for all DB queries (no raw string interpolation into SQL)
- JS: Vanilla ES6+, no imports/modules (plain `<script>` tags)
- CSS: Extend the existing CSS variable system (`--primary`, `--secondary`, etc.) for new colors/values
- Arabic/RTL: Plan for RTL support from the start (use flexbox, avoid fixed widths)
- API: All endpoints return JSON, use proper HTTP status codes

## Development Workflow

1. Run ESLint before committing: `npm run lint`
2. Test changes locally in browser
3. Check browser console for errors
4. Deploy via `bash upload.sh`
5. Test on live server

## Design Guidelines

- **Color Palette:** Soft Sage (#8FBC8F), Gold accents (#D4AF37), Clean White
- **Typography:** Inter font family
- **Layout:** Responsive, mobile-first
- **Language:** Arabic-first with RTL support
- **Style:** Feminine, professional, medical-grade
