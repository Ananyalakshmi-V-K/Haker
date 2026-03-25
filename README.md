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

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Composer
- Redis (optional, can be on EC2)
- MongoDB (optional, can use MongoDB Atlas)

### Steps

1. **Install dependencies:**
   ```bash
   composer install
   ```


   ```

3. **Initialize database:**
   ```bash
   php -r "
   \$pdo = new PDO('mysql:host=localhost', 'root', '');
   \$pdo->exec('CREATE DATABASE IF NOT EXISTS guvi_app DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
   \$pdo = new PDO('mysql:host=localhost;dbname=guvi_app', 'root', '');
   \$schema = file_get_contents('scripts/schema.sql');
   \$pdo->exec(\$schema);
   echo 'Database initialized successfully!';
   "
   ```

4. **Start PHP development server:**
   ```bash
   php -S localhost:8000 -t public
   ```

5. **Open in browser:**
   - `http://localhost:8000`


## Habit Tracker Features

- Daily log entry for `gym`, `study`, `water`
- Streak counters per habit
- Last 14 days line chart (Chart.js)
- Recent logs table
- Redis cache for streak summary to reduce repeated calculations


## Notes

- No classic HTML form submission is used for login; only jQuery AJAX.
- Authentication token is saved in browser `localStorage` and validated against Redis on backend.
- User credentials are queried via MySQL **prepared statement** in `api/auth/login.php`.
- Habit logs are stored in MongoDB collection `habit_logs`.
