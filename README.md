# Fnoon — Global Software & Template Download Platform

A Laravel 11 + Filament 3.3 platform for downloading **software, apps, scripts,
website templates and plugins**, built for a global audience with a heavy-duty
upload engine that handles single files **up to 30 GB**.

- **Public site:** RTL/LTR (English default + Arabic), Tailwind CDN, Alpine — no build step.
- **Admin panel** (`/admin`): manage all content, taxonomy, reviews, users & roles.
- **Upload panel** (`/upload`): resumable chunked uploads straight to Cloudflare R2.

---

## The 30 GB upload engine (the core)

Large files **never pass through PHP**. The browser uploads each chunk directly
to **Cloudflare R2** using short-lived presigned multipart URLs, so a single
object can be far larger than any PHP/Nginx body limit.

```
Browser (Uppy)                Laravel                      Cloudflare R2
   │  create ───────────────▶ MultipartUploadController ─▶ createMultipartUpload
   │ ◀───────────── {key, uploadId, sessionUuid} ─────────────────┘
   │  PUT part 1..N  ──────────────────────────────────▶ (presigned, direct)
   │  sign (per part) ──────▶ presignPart ─────────────▶
   │  complete ─────────────▶ completeMultipartUpload ─▶ object assembled
   │                          dispatch ProcessUploadedFile (queue)
   │                              ├─ streamed SHA-256 / MD5 (O(1) memory)
   │                              ├─ malware scan (ClamAV / VirusTotal / none)
   │                              └─ status: published | failed
```

Key files:
| Concern | File |
|---|---|
| R2 multipart (create/sign/complete/abort/presign-download) | `app/Services/Upload/R2UploadService.php` |
| HTTP endpoints for Uppy | `app/Http/Controllers/Upload/MultipartUploadController.php` |
| Streamed checksum (handles 30 GB) | `app/Services/Upload/ChecksumService.php` |
| Pluggable malware scan | `app/Services/Upload/MalwareScanService.php` |
| Post-upload pipeline (queued) | `app/Jobs/ProcessUploadedFile.php` |
| Uploader UI (Uppy, resumable) | `resources/views/filament/upload/pages/upload-center.blade.php` |
| Abandoned-upload cleanup | `app/Console/Commands/PruneAbandonedUploads.php` (`uploads:prune`, scheduled hourly) |
| Tracking table | `upload_sessions` |

Public downloads are served via **short-lived presigned GET URLs** generated on
click (`DownloadController`), which also logs the hit and bumps counters.

---

## Requirements

- PHP 8.2+
- Composer 2
- A database (SQLite is used out of the box; MySQL recommended in production)
- Redis (production queue/cache; local dev uses the `database` queue driver)
- A Cloudflare R2 bucket (for real uploads/downloads)

## Local setup

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Then:
- Public site → http://localhost:8000
- Admin → http://localhost:8000/admin
- Upload → http://localhost:8000/upload

### Demo accounts (from the seeder)
| Role | Email | Password |
|---|---|---|
| Super admin | `admin@fnoon.test` | `password` |
| Author (uploader) | `author@fnoon.test` | `password` |

> The queue must be running for uploads to be checksummed/scanned/published:
> ```bash
> php artisan queue:work
> ```

---

## Cloudflare R2 configuration

Set these in `.env`:

```dotenv
R2_ACCESS_KEY_ID=...
R2_SECRET_ACCESS_KEY=...
R2_DEFAULT_REGION=auto
R2_BUCKET=fnoon-downloads
R2_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
R2_PUBLIC_URL=https://cdn.yourdomain.com        # optional public/CDN domain

UPLOAD_MAX_BYTES=32212254720                     # 30 GB
UPLOAD_PART_SIZE=16777216                        # 16 MB chunks
UPLOAD_URL_TTL=60                                # presigned URL minutes

UPLOAD_SCANNER=none                              # none | virustotal | clamav
# VIRUSTOTAL_API_KEY=...
```

### Required R2 bucket CORS
The browser PUTs chunks straight to R2, so the bucket must allow your origin and
expose the `ETag` response header:

```json
[
  {
    "AllowedOrigins": ["https://yourdomain.com", "http://localhost:8000"],
    "AllowedMethods": ["GET", "PUT", "POST", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
]
```

---

## Internationalisation

- UI strings: `lang/en/*.php`, `lang/ar/*.php` (English is the default).
- Translatable content (names, descriptions) uses **spatie/laravel-translatable**
  (JSON columns) — add a locale without a migration.
- `app/Http/Middleware/SetLocale.php` resolves locale (session → browser → default)
  and drives `<html dir>` (`ar` → RTL, otherwise LTR).
- Switch language: `/locale/{en|ar}`.

---

## Content types

`software.content_type` is one of `application | script | template | plugin`.
Type-specific fields live in the `meta` JSON column (e.g. `programming_language`,
`framework`, `demo_url`, `platform`) and the admin form adapts to the selected
type.

---

## Roles & permissions (Spatie)

`super_admin` · `editor` · `author` · `moderator`.
Panel access is enforced in `User::canAccessPanel()`:
admin panel → staff roles; upload panel → anyone who can upload.

---

## Production notes

- Run a queue worker (Redis + `php artisan queue:work`, or Horizon).
- Schedule the kernel so `uploads:prune` (hourly) frees orphaned R2 parts.
- Put Cloudflare CDN in front of R2 for global, low-latency downloads.
- ClamAV cannot synchronously scan 30 GB files; the scan runs on the queue and
  large files may rely on checksum + trusted-source verification (`UPLOAD_SCANNER`).
- Switch `DB_CONNECTION` to `mysql` and configure credentials for production.

---

## Tech stack

Laravel 11 · Filament 3.3 · Livewire · Spatie Permission / MediaLibrary /
Translatable · Laravel Sanctum · Flysystem S3 (Cloudflare R2) · Uppy (resumable
multipart) · Tailwind (CDN) · Alpine.js.

The original product/design specification lives in
[`docs/prompt-software-download-site.md`](docs/prompt-software-download-site.md).
