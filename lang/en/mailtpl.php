<?php

return [
    'nav' => 'Email templates',
    'title' => 'Email templates',

    'section' => [
        'choose' => 'Choose a template',
    ],
    'choose' => 'Template',

    'tpl' => [
        'verify' => 'Verify email',
        'reset' => 'Password reset',
        'welcome' => 'Welcome email',
    ],

    'subject' => 'Subject',
    'heading' => 'Heading',
    'body' => 'Body',
    'button_label' => 'Button text',
    'footer' => 'Footer',
    'placeholders' => 'Available variables:',

    'welcome_enabled' => 'Send a welcome email after verification',
    'welcome_enabled_hint' => 'Sent automatically once a member verifies their email. Turn off to stop it.',

    'preview' => 'Preview',
    'close' => 'Close',
    'test' => 'Send test',
    'test_prefix' => 'Test',
    'test_ok' => 'Test email sent to :email',
    'test_fail' => 'Could not send the test email',
    'no_recipient' => 'Your account has no email to send the test to.',

    'sample_name' => 'Sam',
    'fallback' => 'If the button doesn’t work, copy and paste this link into your browser:',

    'defaults' => [
        'verify' => [
            'subject' => 'Verify your email at {site_name}',
            'heading' => 'Welcome, {name} 👋',
            'body' => "Thanks for joining {site_name}!\nClick the button below to verify your email and activate your account.",
            'button_label' => 'Verify email',
            'footer' => 'If you didn’t create this account, you can ignore this email.',
        ],
        'reset' => [
            'subject' => 'Reset your {site_name} password',
            'heading' => 'Password reset request',
            'body' => "We received a request to reset your account password.\nClick the button below to choose a new password. This link expires in {minutes} minutes.",
            'button_label' => 'Reset password',
            'footer' => 'If you didn’t request this, ignore this email — your password stays the same.',
        ],
        'welcome' => [
            'subject' => 'Welcome to {site_name} 🎉',
            'heading' => 'Your account is active, {name}!',
            'body' => "Welcome to {site_name}!\nYou can now upload and share your files and manage your content from your dashboard.",
            'button_label' => 'Go to my dashboard',
            'footer' => 'We’re glad to have you on board.',
        ],
    ],
];
