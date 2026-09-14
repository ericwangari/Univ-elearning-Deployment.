# Deployment

This app is a PHP/MySQL application. Netlify cannot run it as-is because Netlify deploys static sites and serverless functions in JavaScript, TypeScript, and Go, not PHP runtime apps.

## Free Hosting Options

For this codebase, free shared PHP hosting is the best fit.

### Option 1: InfinityFree

- Official site: https://www.infinityfree.com/
- Supports PHP 8.3, MySQL/MariaDB, free SSL, and custom domains.
- Best when you want fully free PHP hosting and can tolerate basic shared-hosting limits.

### Option 2: AwardSpace

- Official site: https://www.awardspace.com/free-hosting/
- Supports PHP and MySQL on a free shared-hosting plan.
- Best when you want a simpler control panel and a smaller starter site.

### Option 3: x10Hosting

- Official site: https://x10hosting.com/
- Offers free hosting with PHP and MySQL support.
- Best as a backup option if the first two do not fit your signup or region.

## Shared Hosting Setup

1. Create the free hosting account.
2. Create a MySQL database from the hosting control panel.
3. Copy [hosting.local.php.example](C:\xampp\htdocs\univ_elearning\config\hosting.local.php.example) to `config/hosting.local.php`.
4. Fill in the database host, database name, username, password, and site URL from the hosting provider.
5. Upload the project files to the hosting document root such as `htdocs` or `public_html`.
6. Open `https://your-site.example.com/deploy_setup.php`.

That page checks the database connection and imports `database/schema.sql` and `database/seed.sql` when needed.

## Optional: Railway

1. Push this repository to GitHub.
2. In Railway, create a new project from the GitHub repo.
3. Add a MySQL database service to the same Railway project.
4. In the web service variables, set:

```text
BASE_URL=https://your-railway-app-url/
```

Railway's MySQL service exposes variables such as `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, and `MYSQLPASSWORD`. The app now reads those automatically. It also supports `DATABASE_URL`, `MYSQL_URL`, or the existing `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` variables.

5. Deploy the web service.
6. Open:

```text
https://your-railway-app-url/deploy_setup.php
```

## Vercel Deployment

This project includes a `vercel.json` configured with `vercel-php` runtime builder.

### Prerequisites
1. A managed PostgreSQL database (e.g., [Supabase](https://supabase.com)) or MySQL database (e.g., [PlanetScale](https://planetscale.com) or [Aiven](https://aiven.io)).
2. [Vercel CLI](https://vercel.com/cli) installed (`npm i -g vercel`) or a linked GitHub repository connected to Vercel.

### Step 1: Deploy via Vercel CLI or GitHub
- **Via Vercel CLI**:
  Run inside project directory:
  ```bash
  npx vercel --prod
  ```
- **Via Vercel Web Dashboard**:
  Import your repository into Vercel.

### Step 2: Set Environment Variables on Vercel
In your Vercel Project Settings -> **Environment Variables**, add:

- **For PostgreSQL / Supabase:**
  - `DATABASE_URL`: `postgres://[user]:[password]@[host]:5432/[database]` (or `POSTGRES_URL`)
  - `DB_DRIVER`: `pgsql`

- **For MySQL / PlanetScale / Aiven:**
  - `DB_HOST`: your database host
  - `DB_PORT`: `3306`
  - `DB_NAME`: database name
  - `DB_USER`: database username
  - `DB_PASS`: database password

### Step 3: Run Database Migration & Initial Setup
After deploying, visit:
```text
https://<your-vercel-app>.vercel.app/deploy_setup.php
```
This utility connects to your remote database and automatically imports the database schema (`schema.sql` or `schema_pgsql.sql`) and seed data.

---

## Local Environment

For XAMPP, no environment variables are required. The app still defaults to:

```text
DB_HOST=localhost
DB_PORT=3306
DB_NAME=univ_elearning
DB_USER=root
DB_PASS=
BASE_URL=http://localhost/univ_elearning/
```

## About Netlify

You can use Netlify only for a separate static frontend, or as a static landing page that links to this PHP app hosted elsewhere. The current repo renders pages through PHP controllers and connects directly to MySQL, so a full Netlify-only deploy would require rebuilding the backend as Netlify Functions or moving the app to a JavaScript/API architecture.
