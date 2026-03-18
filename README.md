# YouTube Channel Manager

A PHP web application that allows authenticated users to sync YouTube channels, store their data in a MySQL database, and browse up to 100 videos per channel with pagination.

---

## Entry Point

After setup, open your browser and go to:

```
http://localhost/youtube-channel-manager/
```

This redirects to the login page if unauthenticated, or to the dashboard if already logged in.

---

## Credentials

All API credentials are **pre-configured** in the `.env` file included in this repository. You do not need to create or register any external services.

The following are already set and ready to use:

| Setting | Status |
|---|---|
| YouTube Data API v3 Key | ✅ Included in `.env` |
| Google OAuth Client ID | ✅ Included in `.env` |
| Google OAuth Client Secret | ✅ Included in `.env` |
| OAuth Redirect URI | ✅ Set to `http://localhost/project_youtube_api_v2/auth/callback.php` |
| Database settings | ✅ Set to XAMPP defaults (`root`, no password) |

> **Note:** The OAuth redirect URI is fixed to `http://localhost/project_youtube_api_v2/auth/callback.php/`. If your local setup uses a different port or path, update `GOOGLE_REDIRECT_URI` in `.env` accordingly.

---

## Quick Setup (3 steps)

### 1. Place the project in your web root

Copy or clone the project folder into your XAMPP `htdocs` directory:

```
C:/xampp/htdocs/youtube-channel-manager/
```

### 2. Create the database

- Start **Apache** and **MySQL** in XAMPP Control Panel
- Open `http://localhost/phpmyadmin`
- Click **Import**, select `sql/schema.sql`, click **Go**

That's it — the `youtube_manager` database and all tables are created automatically.

### 3. Open the app

```
http://localhost/youtube-channel-manager/
```

Log in with your Google account and start syncing channels.

---

## How It Works

1. **Login** — The user signs in with Google via OAuth 2.0. Basic profile info (name, email, avatar) is stored in the database.
2. **Sync a Channel** — From the dashboard, enter a valid YouTube Channel ID. The app fetches the channel details and up to 100 videos from the YouTube Data API v3 and saves them to the database.
3. **Browse Videos** — Click any saved channel to view its details and paginated video list (20 per page). Videos are always scoped to their specific channel.
4. **Multiple Channels** — Any number of channels can be saved. Syncing a channel that already exists shows a warning instead of inserting a duplicate.

---

## Project Structure

```
youtube-channel-manager/
├── config/          # Database, Google OAuth, YouTube API configuration
├── auth/            # Login, OAuth callback, logout
├── api/             # JSON endpoints for channel sync and video fetch
├── includes/        # Shared helpers (auth, Google client, YouTube client)
├── pages/           # Dashboard and channel view pages
├── assets/          # CSS and JavaScript
├── sql/             # Database schema (run once)
├── index.php        # Entry point
├── .env             # Credentials
└── README.md
```

---

## Important Notes

- **No Composer needed** — zero external dependencies, pure PHP.
- **Duplicate prevention** — re-syncing an existing channel shows a warning message; videos use `INSERT IGNORE` to skip duplicates.
- **Pagination** — 20 videos per page; works correctly for channels with fewer than 20 or fewer than 100 videos.
- **Error handling** — invalid channel IDs, empty channels, API quota errors, and network failures all show clear user-friendly messages.
- **Security** — all queries use PDO prepared statements; sessions are `httponly` and `SameSite=Lax`; OAuth uses a state token to prevent CSRF.
- **YouTube API quota** — the API allows ~10,000 units per day. Each channel sync uses roughly 100–200 units.
