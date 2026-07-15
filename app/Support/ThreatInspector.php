<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * App-layer attack-signature detector. Pure & side-effect-free: given a request
 * it returns a list of detections (type + severity + a sanitized snippet). The
 * SecurityGuard decides what to do with them (log / block / alert).
 *
 * Severities: critical = confirmed attack (block on sight), high = strong signal
 * (block on repeat), medium = suspicious (block only if it keeps happening).
 */
class ThreatInspector
{
    /** Paths a human never requests — hitting one is a near-certain probe/attack. */
    private const HONEYPOTS = [
        '.env', '.git', '.aws', '.ssh', 'id_rsa', 'wp-login', 'wp-admin', 'wp-config',
        'xmlrpc.php', 'phpmyadmin', 'phpmyadmin', 'adminer', 'phpunit', 'eval-stdin',
        'administrator/index', 'cgi-bin', 'shell.php', 'c99.php', 'r57.php', 'alfa.php',
        '.htaccess', 'backup.sql', 'database.sql', 'dump.sql', 'server-status',
        'solr/admin', 'struts', 'hnap1', 'boaform', 'goform', '/.well-known/../',
    ];

    /** Known offensive scanners / exploitation tools (User-Agent). */
    private const SCANNERS = [
        'sqlmap', 'nikto', 'nmap', 'masscan', 'acunetix', 'nessus', 'dirbuster',
        'gobuster', 'wpscan', 'fimap', 'netsparker', 'zgrab', 'nuclei', 'hydra',
        'jsql', 'arachni', 'w3af', 'skipfish', 'openvas', 'metasploit',
    ];

    /** [regex, type, severity] — matched against the decoded path + query + body. */
    private const SIGNATURES = [
        // SQL injection (critical — confirmed)
        ['/union\s+select/i', 'sqli', 'critical'],
        ['/information_schema/i', 'sqli', 'critical'],
        ['/\bsleep\s*\(\s*\d/i', 'sqli', 'critical'],
        ['/\bbenchmark\s*\(/i', 'sqli', 'critical'],
        ['/\bload_file\s*\(/i', 'sqli', 'critical'],
        ['/into\s+(out|dump)file/i', 'sqli', 'critical'],
        ['/waitfor\s+delay/i', 'sqli', 'critical'],
        ['/concat\s*\(\s*0x/i', 'sqli', 'critical'],
        ['/;\s*(drop|truncate|delete|insert|update)\s+/i', 'sqli', 'critical'],
        // SQL injection (high — needs a repeat to block)
        ['/[\'"\)]\s*or\s+[\'"\d]+\s*=\s*[\'"\d]+/i', 'sqli', 'high'],
        ['/\bor\s+1\s*=\s*1\b/i', 'sqli', 'high'],

        // Path traversal / local file inclusion (high)
        ['#\.\./\.\./#', 'traversal', 'high'],
        ['/%2e%2e[%2f5c]/i', 'traversal', 'high'],
        ['#/etc/passwd#i', 'lfi', 'high'],
        ['#/etc/shadow#i', 'lfi', 'high'],
        ['/\bwin\.ini\b/i', 'lfi', 'high'],
        ['#/proc/self/environ#i', 'lfi', 'high'],
        ['#(php|file|expect|phar|zip)://#i', 'lfi', 'high'],

        // Remote code / template / injection (high)
        ['/\bbase64_decode\s*\(/i', 'rce', 'high'],
        ['/\b(system|exec|passthru|shell_exec|popen|proc_open)\s*\(/i', 'rce', 'high'],
        ['/\beval\s*\(/i', 'rce', 'high'],
        ['/\bphpinfo\s*\(/i', 'rce', 'high'],
        ['/\$\{jndi:/i', 'rce', 'critical'],        // Log4Shell

        // Cross-site scripting (medium)
        ['/<script\b/i', 'xss', 'medium'],
        ['/on(error|load|mouseover|focus)\s*=/i', 'xss', 'medium'],
        ['/javascript:/i', 'xss', 'medium'],
        ['/<svg[^>]*\bon/i', 'xss', 'medium'],
        ['/document\.cookie/i', 'xss', 'medium'],
    ];

    /** @return array<int, array{type:string, severity:string, detail:string}> */
    public static function inspect(Request $request): array
    {
        $found = [];

        $path = rawurldecode($request->path());
        $ua = (string) $request->userAgent();

        // 1) Scanner tool in the User-Agent (critical).
        $uaLower = mb_strtolower($ua);
        foreach (self::SCANNERS as $tool) {
            if (str_contains($uaLower, $tool)) {
                $found[] = ['type' => 'scanner_ua', 'severity' => 'critical', 'detail' => 'UA: '.self::snippet($ua)];
                break;
            }
        }

        // 2) Honeypot path (critical).
        $pathLower = mb_strtolower($path);
        foreach (self::HONEYPOTS as $trap) {
            if (str_contains($pathLower, $trap)) {
                $found[] = ['type' => 'honeypot', 'severity' => 'critical', 'detail' => 'path: '.self::snippet($path)];
                break;
            }
        }

        // 3) Signature scan over the decoded path + query + body + UA (payloads
        //    are commonly smuggled through headers like the User-Agent too).
        $haystack = $path.' '.rawurldecode((string) $request->getQueryString()).' '.$ua;

        // Include submitted values so payloads in POST/GET fields are caught too.
        foreach (self::flatten($request->input()) as $value) {
            $haystack .= ' '.$value;
        }
        $haystack = mb_substr($haystack, 0, 8000);

        foreach (self::SIGNATURES as [$rx, $type, $sev]) {
            if (preg_match($rx, $haystack, $m)) {
                $found[] = ['type' => $type, 'severity' => $sev, 'detail' => self::snippet($m[0])];
            }
        }

        return $found;
    }

    /** Flatten request input to scalar strings (skips uploaded files, caps size). */
    private static function flatten(array $input, int $depth = 0): array
    {
        if ($depth > 4) {
            return [];
        }
        $out = [];
        foreach ($input as $v) {
            if (is_array($v)) {
                $out = array_merge($out, self::flatten($v, $depth + 1));
            } elseif (is_scalar($v)) {
                $out[] = (string) $v;
            }
            if (count($out) > 200) {
                break;
            }
        }

        return $out;
    }

    /** Trim + strip control chars so stored detail is safe and short. */
    private static function snippet(string $v): string
    {
        $v = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $v) ?? $v;

        return mb_substr(trim($v), 0, 180);
    }
}
