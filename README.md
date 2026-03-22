# GUVI Login + Habit Tracker

This project implements:

- Phase 1: Login page
- Phase 2: Habit Tracker with analytics (gym, study, water)

Tech mapping used in the app:

- Separate files for HTML, CSS, JS, and PHP
- jQuery for DOM and AJAX interactions
- Bootstrap for responsive UI
- MySQL for login/registered credentials (using prepared statements)
- MongoDB for daily habit logs (time-series style)
- Redis for backend session token storage and streak summary cache
- Browser `localStorage` for client-side login session state (no PHP sessions)

## Project Structure

- `public/index.html` → Login page
- `public/dashboard.html` → Habit tracker dashboard
- `public/assets/css/styles.css` → UI styles
- `public/assets/js/login.js` → Login logic (jQuery AJAX)
- `public/assets/js/dashboard.js` → Habit logs, streaks, chart, logout
- `public/api/*` → Web-accessible PHP endpoint entry files
- `api/*` → Core backend logic/config files
- `scripts/schema.sql` → MySQL schema and sample user

## Local Setup

1. Install dependencies:

   ```bash
   composer install
   ```

2. Configure env:

   ```bash
   cp .env.example .env
   ```

   Fill your MySQL/MongoDB/Redis values in `.env`.

3. Create MySQL schema/table/sample user:

   ```bash
   mysql -u root -p < scripts/schema.sql
   ```

4. Insert sample habit logs in MongoDB (optional starter data):

   ```javascript
   use guvi_app
   db.habit_logs.insertMany([
     { user_id: 1, habit: "gym", date: "2026-03-20", value: 1, created_at: 0, updated_at: 0 },
     { user_id: 1, habit: "study", date: "2026-03-21", value: 2, created_at: 0, updated_at: 0 },
     { user_id: 1, habit: "water", date: "2026-03-22", value: 8, created_at: 0, updated_at: 0 }
   ])
   ```

5. Start app locally:

   ```bash
   php -S localhost:8000 -t public
   ```

6. Open:

   - `http://localhost:8000`

## Test Credentials

- Email: `demo@guvi.in`
- Password: `Pass@123`

## Habit Tracker Features

- Daily log entry for `gym`, `study`, `water`
- Streak counters per habit
- Last 14 days line chart (Chart.js)
- Recent logs table
- Redis cache for streak summary to reduce repeated calculations

## Deployment

### Heroku

1. Create app and push:

   ```bash
   heroku create <your-app-name>
   git push heroku main
   ```

2. Set config vars from `.env` in Heroku dashboard (`Settings` → `Config Vars`).

3. Provision services:

   - MySQL (ClearDB or external MySQL)
   - MongoDB Atlas
   - Redis (Heroku Redis)

4. Open deployed app:

   ```bash
   heroku open
   ```



## Notes

- No classic HTML form submission is used for login; only jQuery AJAX.
- Authentication token is saved in browser `localStorage` and validated against Redis on backend.
- User credentials are queried via MySQL **prepared statement** in `api/auth/login.php`.
- Habit logs are stored in MongoDB collection `habit_logs`.
