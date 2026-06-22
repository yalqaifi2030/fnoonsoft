<?php

return [
    'nav' => 'Security',
    'title' => 'Security',

    '2fa_title' => 'Two-factor authentication',
    '2fa_subtitle' => 'Add an extra layer with an authenticator app (Google Authenticator, Authy…).',
    'on' => 'Enabled',
    'off' => 'Disabled',

    'enable_hint' => 'When enabled, you’ll enter a 6-digit code from your authenticator app each time you sign in.',
    'enable' => 'Enable two-factor',

    'setup_steps' => 'Scan this QR with your authenticator app, then enter the 6-digit code it shows to confirm.',
    'manual_key' => 'Or enter this key manually:',
    'enter_code' => 'Verification code',
    'confirm_enable' => 'Confirm & enable',
    'cancel' => 'Cancel',

    'enabled_ok' => 'Two-factor authentication is now enabled.',
    'disabled_ok' => 'Two-factor authentication has been disabled.',
    'bad_code' => 'Invalid code. Please try again.',
    'bad_password' => 'Incorrect password.',

    'disable_label' => 'Disable two-factor (confirm your password)',
    'disable' => 'Disable',
    'password' => 'Your password',

    'recovery_title' => 'Recovery codes',
    'recovery_hint' => 'Save these somewhere safe. Each works once if you lose access to your authenticator app.',
    'copy_codes' => 'Copy codes',
    'copied' => 'Copied',
    'recovery_regenerate_hint' => 'Lost your codes? Generate a new set (the old ones stop working).',
    'recovery_regenerate' => 'Regenerate recovery codes',
    'recovery_regenerate_confirm' => 'Generate new recovery codes? The old ones will stop working.',
    'recovery_regenerated' => 'New recovery codes generated.',

    'challenge_title' => 'Two-step verification',
    'challenge_hint' => 'Enter the 6-digit code from your authenticator app.',
    'verify' => 'Verify',
    'challenge_recovery' => 'Lost your device? Enter one of your recovery codes instead.',
    'sign_out' => 'Sign out',
];
