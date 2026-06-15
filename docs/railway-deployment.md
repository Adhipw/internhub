# Railway Deployment

This repository uses a Docker-based Railway deployment because the app is split into `backend` and `frontend`.

## Required Railway variables

Set these variables in the Railway service:

```env
APP_NAME=InternHub
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-railway-domain.up.railway.app
APP_KEY=base64:replace_with_php_artisan_key_generate_show

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=pgsql
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync

BROADCAST_CONNECTION=log
VITE_REVERB_ENABLED=false

FILESYSTEM_DISK=local
MAIL_MAILER=log
AI_PROVIDER=fake
RUN_MIGRATIONS=true
```

Railway's PostgreSQL plugin normally provides `DATABASE_URL` automatically. Keep `DB_CONNECTION=pgsql` so Laravel uses the PostgreSQL driver.

Generate `APP_KEY` locally from `backend`:

```bash
php artisan key:generate --show
```

## Deploy notes

- The Docker build installs frontend dependencies, builds Vite assets, installs Laravel dependencies, and copies the built assets into `backend/public/build`.
- The runtime starts from `backend/public` using Railway's `PORT`.
- On startup, the container runs `php artisan migrate --force`. Set `RUN_MIGRATIONS=false` if you want to run migrations manually.
- Reverb, Horizon, Redis, and queue workers are intentionally disabled for the first stable deploy. Add separate Railway workers later if needed.

## Public user readiness

For real public users, do not keep email in `log` mode. Configure a real mail provider so OTP verification and password reset work:

```env
MAIL_MAILER=resend
RESEND_API_KEY=your_resend_api_key
RESEND_FROM_ADDRESS=no-reply@your-domain.com
RESEND_FROM_NAME=InternHub
MAIL_FROM_ADDRESS=no-reply@your-domain.com
MAIL_FROM_NAME=InternHub
```

If you use a custom domain, update `APP_URL` to that domain and set Google OAuth callback URLs to:

```text
https://your-domain.com/auth/google/callback
```

For durable file uploads across redeploys, configure Cloudflare R2 or S3 and set:

```env
FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_BUCKET=...
R2_ENDPOINT=...
R2_REGION=auto
```
