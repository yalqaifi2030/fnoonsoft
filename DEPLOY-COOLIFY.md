# Deploying Fnoon on Coolify (VPS)

Fnoon ships as a Docker image (PHP 8.3 + Nginx + Supervisor) with no Node build
step — the front-end uses Tailwind/Alpine via CDN. The same image runs the web,
queue worker and scheduler. MySQL and Redis run alongside it.

## What's in the box
| File | Purpose |
|---|---|
| `Dockerfile` | PHP 8.3-fpm-alpine + Nginx + Supervisor + extensions (gd, intl, bcmath, pdo_mysql, redis, zip, exif, opcache) |
| `docker-compose.yml` | `app` (web) · `worker` · `scheduler` · `mysql` · `redis` + persistent volumes |
| `docker/entrypoint.sh` | `storage:link`, `filament:assets`, migrate (web only), `optimize` |
| `docker/nginx/*`, `docker/php/*`, `docker/supervisor/*` | Server tuning, incl. **64 MB upload limit** |
| `.env.docker.example` | The env vars to set in Coolify |

## Steps

1. **Push this repo to Git** (GitHub/GitLab) that Coolify can reach.

2. **Coolify → New Resource → Docker Compose**, pick this repo.
   Coolify auto-detects `docker-compose.yml`.

3. **Environment variables** (Coolify → the resource → Environment).
   Copy from `.env.docker.example`. Minimum required:
   - `APP_KEY` — run `php artisan key:generate --show` once and paste it.
   - `APP_URL` — `https://your-domain.com`
   - `DB_PASSWORD`, `DB_ROOT_PASSWORD`

4. **Domain** — set your domain on the **`app`** service (port **80**).
   Coolify provisions HTTPS automatically (Traefik + Let's Encrypt).
   > HTTPS also makes the browser clipboard API work natively.

5. **Persistent storage is already declared** as named volumes
   (`fnoon-storage`, `fnoon-db`, `fnoon-redis`). The shared `fnoon-storage`
   keeps uploaded files/media across the web + worker containers and across
   redeploys — don't remove it.

6. **Deploy.** On first boot the `app` container runs migrations
   (`RUN_MIGRATIONS=true`), links storage, publishes Filament assets and caches
   config/routes.

7. **Seed the first admin (one-time).** Open a terminal on the `app` container:
   ```sh
   php artisan db:seed --force        # if you want the demo/roles seeders
   # or create a user manually via tinker
   ```
   Then sign in at `https://your-domain.com/admin`.

8. **Configure cloud storage & mail from the UI** (no redeploy needed):
   `/admin` → **Settings** → **Storage** (R2 / iDrive e2 / S3) and **Email** (SMTP).

## Notes & tuning
- **Large uploads**: Nginx `client_max_body_size` and PHP `post_max_size` are set
  to **64 MB** — enough for media (≤50 MB) and proxied chunks (~16 MB).
  For true multi-GB files, configure **Cloudflare R2** (direct browser→bucket,
  bypasses the server). iDrive e2 proxy mode routes bytes through the server and
  is practically capped near 5 GB.
- **Health check**: the `app` service probes `/up`.
- **Switch to a Coolify-managed database** instead of the bundled MySQL: remove
  the `mysql` service + its `depends_on`, then set `DB_HOST`/`DB_*` to the managed
  instance.
- **Migrations** only run in the `app` container (`RUN_MIGRATIONS=true`); the
  `worker`/`scheduler` never migrate.
