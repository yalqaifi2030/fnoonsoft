# Security hardening — origin protection

## What happened
An attacker reached the **origin server directly by IP** (`https://62.72.0.162/admin/login`).
Investigation showed `finunsoft.com` currently resolves **straight to the origin
IP with no Cloudflare proxy** (`Server: nginx`, no `cf-ray`). So the origin IP is
public via DNS and there was no edge firewall in front of it.

## What is now in place (app layer)
`App\Http\Middleware\EnforceOrigin` (global middleware, runs on every route incl.
the Filament panels) + `config/security.php`:

| env var | value now | effect |
|---|---|---|
| `SECURITY_ENFORCE_ORIGIN` | `true` | reject any request whose `Host` isn't an allowed host (blocks raw-IP access like the attack) |
| `SECURITY_ALLOWED_HOSTS` | `finunsoft.com,www.finunsoft.com` | the only hostnames the app answers on |
| `SECURITY_REQUIRE_CLOUDFLARE` | `false` | **keep false until the domain is actually behind Cloudflare** |

Plus: repeat critical attackers are now blocked **permanently** (3rd strike), and
the scanner / honeypot signature lists were expanded.

## The real fix — put the domain behind Cloudflare (recommended)
1. Create a free Cloudflare account and add `finunsoft.com`.
2. At your domain registrar, change the **nameservers** to the two Cloudflare
   gives you.
3. In Cloudflare DNS, the `A` record for `finunsoft.com` (→ `62.72.0.162`) must be
   **Proxied (orange cloud)**, not DNS-only.
4. SSL/TLS mode: **Full (strict)**.
5. Turn on: **WAF managed rules**, **Bot Fight Mode**, and a rate-limit rule on
   `/admin/*` and `/upload/*`.
6. Once traffic shows `cf-ray` response headers, set on the server:
   ```
   SECURITY_REQUIRE_CLOUDFLARE=true
   ```
   then `php artisan optimize:clear`. Now anything hitting the origin directly
   (bypassing Cloudflare) is refused.

## Lock the origin to Cloudflare only (server firewall — strongest)
After the domain is proxied, restrict the origin so ONLY Cloudflare can reach it.
In the site's nginx config (aaPanel → site → Config), inside the `server {}` block:

```nginx
# Allow only Cloudflare IP ranges to the origin; everyone else gets 403.
# Full current list: https://www.cloudflare.com/ips/
include /www/server/nginx/conf/cloudflare_ips.conf;  # allow ...; entries
deny all;

# Restore the real visitor IP from Cloudflare.
set_real_ip_from 0.0.0.0/0;  # replace with the CF ranges
real_ip_header CF-Connecting-IP;
```

Reload nginx. Now the origin IP is useless to attackers even if they find it.

## Emergency: disable enforcement
If you ever pause Cloudflare or get locked out, over SSH:
```
cd /www/wwwroot/finunsoft.com
sed -i 's/^SECURITY_REQUIRE_CLOUDFLARE=.*/SECURITY_REQUIRE_CLOUDFLARE=false/' .env
# or fully off:
sed -i 's/^SECURITY_ENFORCE_ORIGIN=.*/SECURITY_ENFORCE_ORIGIN=false/' .env
/www/server/php/83/bin/php artisan optimize:clear
```
Unblock an IP: `php artisan security:unblock <ip>` (or `--all`).
