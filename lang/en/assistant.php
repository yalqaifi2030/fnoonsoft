<?php

return [
    'nav' => 'AI Assistant',
    'title' => 'AI Assistant',

    'section' => [
        'connection' => 'Activation & connection',
        'connection_hint' => 'Enable the assistant, connect your Claude API key, and pick a model.',
        'behaviour' => 'Persona & behaviour',
        'behaviour_hint' => 'Control the tone, the welcome message, and the suggested questions.',
        'limits' => 'Usage limits',
        'limits_hint' => 'Keep costs in check with a per-visitor daily cap and recommendation count.',
    ],

    'enabled' => 'Show the assistant to visitors',
    'enabled_hint' => 'When off, the chat bubble never appears on the site.',

    'api_key' => 'Claude API key',
    'api_key_hint' => 'Stored encrypted. Get it from console.anthropic.com — usually starts with sk-ant-.',

    'model' => 'Model',
    'model_hint' => 'Haiku is faster and cheaper (great for a public chat); Opus is the smartest and priciest.',
    'model_haiku' => 'Claude Haiku 4.5 — fast & economical (recommended)',
    'model_sonnet' => 'Claude Sonnet 4.6 — balanced',
    'model_opus' => 'Claude Opus 4.8 — smartest (priciest)',

    'persona' => 'Assistant persona (system prompt)',
    'persona_hint' => 'Describe who the assistant is and how it talks. Leave blank for a sensible default.',
    'persona_ph' => 'You are a friendly, expert assistant who helps shoppers pick the right software…',

    'welcome' => 'Welcome message',
    'welcome_hint' => 'The first message a visitor sees when they open the chat.',
    'welcome_ph' => 'Hi! I am your AI assistant 👋 Tell me what you need and I will recommend the best fit.',

    'suggestions' => 'Suggested questions',
    'suggestions_hint' => 'One per line (up to 4). Shown as quick-tap buttons.',
    'suggestions_ph' => "I need a design app\nI want an e-commerce template\nA ready-made chat script",

    'daily_limit' => 'Daily message cap per visitor',
    'daily_limit_hint' => 'Prevents runaway cost. Set 0 for unlimited.',
    'per_day' => 'msgs/day',

    'max_recs' => 'Max recommendations per reply',
    'max_recs_hint' => 'How many product cards can appear in a single message.',

    'refresh_catalog' => 'Refresh catalog',
    'catalog_refreshed' => 'Assistant catalog refreshed.',
    'clear_test' => 'Clear preview',

    'need_key' => 'Enter an API key first to test the assistant.',
    'error' => 'Could not get a reply right now, please try again.',
    'limit_reached' => 'You have reached today\'s question limit. Come back tomorrow or browse the site 🙏',

    'preview' => [
        'title' => 'Live preview',
        'catalog' => ':count items available to recommend',
        'empty' => 'Try the assistant here before publishing it to visitors.',
        'thinking' => 'Typing…',
        'placeholder' => 'Type a question to test…',
    ],

    'widget' => [
        'title' => 'AI Assistant',
        'subtitle' => 'I recommend the best fit from the store',
        'welcome_default' => 'Hi! 👋 Tell me what you are looking for and I will help you pick the best fit.',
        'placeholder' => 'Type your message…',
        'send' => 'Send',
        'close' => 'Close',
    ],
];
