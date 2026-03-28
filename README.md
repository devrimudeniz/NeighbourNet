# NeighbourNet

A PHP-based community platform that brings together social features, events, local guides, business listings, community tools, and utility services.

<img src="https://komarev.com/ghpvc/?username=devrimudeniz&color=brightgreen" alt="watching_count" /><img src="https://img.shields.io/github/stars/devrimudeniz?label=Stars" alt="stars">

## Overview

NeighbourNet combines a social feed with practical local services. The project includes public community features, business-focused tools, and an admin panel for moderation and platform management.

Recent GitHub-prep improvements include:

- environment-based secret management via `.env`
- a new admin `Site Settings` screen
- cleaner repository defaults for public sharing
- updated setup documentation for local installs

## Core Features

- Social feed with reactions, comments, reposting, saving, hashtags, and translation
- User profiles, messaging, notifications, groups, and member discovery
- Events, guidebooks, jobs, properties, marketplace, and local services
- Business panel with QR menu and listing management flows
- Admin panel for approvals, moderation, and system controls
- Central site branding settings managed from the admin panel

## Repository Cleanup For GitHub

This repository was prepared for public sharing:

- hard-coded API keys, SMTP credentials, OAuth secrets, and push secrets were removed from code
- real `.env` and FTP client files were removed
- log, cache, and user upload output paths are now ignored
- an `.env.example` template was added for new installs

## Stack

- PHP
- MySQL / MariaDB
- Tailwind CSS
- Vanilla JavaScript
- PHPMailer

## Setup

### 1. Clone the project

```bash
git clone <repo-url>
```

Or download the project archive and place it inside your local web root.

### 2. Create your `.env` file

Copy [`.env.example`](./.env.example) to `.env` and fill in your local values.

Example:

```env
APP_URL=http://localhost/NeighbourNet
SITE_NAME=NeighbourNet
SITE_SHORT_NAME=NeighbourNet

DB_HOST=127.0.0.1
DB_NAME=neighbournet
DB_USER=root
DB_PASS=

GEMINI_API_KEY=
OPENWEATHER_API_KEY=
COLLECTAPI_KEY=
EXCHANGERATE_API_KEY=
```

### 3. Prepare the database

This public repository does not include a full production database dump.

If you already have an internal database export:

1. Create an empty database.
2. Import your base schema and data dump.
3. Apply the additional SQL files included in this repository.

At minimum, run:

```sql
sql/site_settings.sql
```

Additional schema changes live in:

```text
sql/
migrations/
```

Notes:

- Some helper endpoints create small support tables on demand if they are missing.
- For a complete local restore, the safest path is to import your internal base schema first and then apply the incremental SQL files from this repo.

### 4. Make runtime folders writable

These folders should be writable by PHP:

```text
cache/
uploads/
```

### 5. Open the admin panel

Admin panel path:

```text
/admin
```

The first screen worth checking after setup:

```text
/admin/site_settings.php
```

From there you can manage:

- site name
- short name
- Turkish tagline
- English tagline
- support email
- contact phone
- application URL

## Important Environment Variables

### Commonly required

- `APP_URL`
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

### Feature-specific

- `GEMINI_API_KEY`
- `OPENWEATHER_API_KEY`
- `COLLECTAPI_KEY`
- `EXCHANGERATE_API_KEY`
- `SMTP_*`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `FACEBOOK_APP_ID`
- `FACEBOOK_APP_SECRET`
- `VAPID_PUBLIC_KEY`
- `VAPID_PRIVATE_KEY`
- `ONESIGNAL_APP_ID`
- `ONESIGNAL_REST_API_KEY`

If these are left empty, the related feature will either stay disabled or fall back gracefully where supported.

## Project Structure

```text
admin/        Admin panel pages
api/          AJAX and service endpoints
includes/     Shared helpers and configuration
lang/         Language files
sql/          SQL patches and support tables
migrations/   Incremental migration files
assets/       Static frontend assets
uploads/      User-generated uploads
cache/        Runtime cache output
```

## ScreenShots

<a href="https://hizliresim.com/sw0wmrw"><img src="https://i.hizliresim.com/sw0wmrw.png" alt="ff"></a>

<a href="https://hizliresim.com/agwg1nu"><img src="https://i.hizliresim.com/agwg1nu.png" alt="ff"></a>

<a href="https://hizliresim.com/njlk4or"><img src="https://i.hizliresim.com/njlk4or.png" alt="ff"></a>






