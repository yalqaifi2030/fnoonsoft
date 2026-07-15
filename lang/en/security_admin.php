<?php

return [
    // Security events log
    'events_nav' => 'Security events',
    'events_single' => 'Security event',
    'events_plural' => 'Security events',
    'ip' => 'IP address',
    'type' => 'Type',
    'severity' => 'Severity',
    'method' => 'Method',
    'path' => 'Path',
    'detail' => 'Detail',
    'member' => 'Member',
    'guest' => 'Guest',
    'country' => 'Country',
    'agent' => 'User agent',
    'when' => 'When',
    'blocked_flag' => 'Blocked?',
    'events_empty' => 'No security events — all quiet. 🛡️',

    'sev' => [
        'critical' => 'Critical',
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
    ],
    'types' => [
        'sqli' => 'SQL injection',
        'xss' => 'XSS',
        'traversal' => 'Path traversal',
        'lfi' => 'File inclusion',
        'rce' => 'Code execution',
        'scanner_ua' => 'Scanner tool',
        'honeypot' => 'Honeypot',
        'bruteforce' => 'Brute force',
    ],

    'filter_severity' => 'Severity',
    'filter_type' => 'Type',
    'block_ip' => 'Block this IP',
    'block_ip_done' => 'IP blocked.',

    // Blocked IPs
    'blocked_nav' => 'Blocked IPs',
    'blocked_single' => 'Blocked IP',
    'blocked_plural' => 'Blocked IPs',
    'reason' => 'Reason',
    'auto' => 'Auto',
    'manual' => 'Manual',
    'source' => 'Source',
    'hits' => 'Hits',
    'expires' => 'Expires',
    'permanent' => 'Permanent',
    'expired' => 'Expired',
    'unblock' => 'Unblock',
    'blocked_empty' => 'No blocked IPs.',
    'add_block_hint' => 'Enter an IP to block it manually (permanent block).',

    // Auto-generated reasons / alerts / block page
    'reason_auto' => 'Auto-block: :type',
    'reason_bruteforce' => 'Auto-block: repeated failed logins',
    'alert_title' => '🛡️ An attacker was auto-blocked',
    'alert_body' => 'Attack type: :type — IP: :ip',
    'alert_open' => 'View security events',

    'blocked_title' => 'Access blocked',
    'blocked_body' => 'Unusual activity was detected from your connection, so access has been temporarily blocked to protect the site.',
    'blocked_contact' => 'If you believe this is a mistake, contact us and we’ll review it.',
];
