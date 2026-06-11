<?php

namespace App\Support;

/**
 * A curated set of Font Awesome (solid) icons offered by the icon picker.
 * Each entry is the full class string stored on the model, e.g. "fa-solid fa-globe".
 */
class Icons
{
    /** @return list<string> */
    public static function list(): array
    {
        $names = [
            // General / web
            'globe', 'house', 'compass', 'magnifying-glass', 'link', 'sitemap', 'window-maximize', 'window-restore',
            // Dev / software
            'code', 'file-code', 'terminal', 'bug', 'microchip', 'gears', 'gear', 'wrench', 'screwdriver-wrench',
            'puzzle-piece', 'layer-group', 'cubes', 'cube', 'box', 'boxes-stacked', 'plug', 'robot', 'brain',
            // Platforms / devices
            'desktop', 'laptop', 'mobile-screen', 'tablet-screen-button', 'server', 'database', 'hard-drive',
            'memory', 'network-wired', 'wifi', 'cloud', 'cloud-arrow-down', 'cloud-arrow-up',
            // Design / media
            'palette', 'paintbrush', 'brush', 'pen-nib', 'pen', 'crop', 'vector-square', 'shapes', 'swatchbook',
            'image', 'images', 'photo-film', 'camera', 'video', 'film', 'clapperboard', 'music', 'headphones',
            'microphone', 'volume-high', 'tv', 'font', 'heading',
            // Files / docs
            'file', 'file-pdf', 'file-word', 'file-excel', 'file-zipper', 'file-lines', 'folder', 'folder-open',
            'book', 'book-open', 'newspaper', 'table', 'list', 'clipboard',
            // Gaming / fun
            'gamepad', 'dice', 'chess', 'trophy', 'medal', 'crown', 'gift', 'fire', 'bolt', 'rocket', 'star', 'heart',
            // Commerce
            'store', 'cart-shopping', 'bag-shopping', 'credit-card', 'money-bill', 'tag', 'tags', 'percent', 'gem',
            // Data / analytics
            'chart-line', 'chart-pie', 'chart-bar', 'chart-column', 'gauge-high',
            // Security
            'shield-halved', 'lock', 'unlock', 'key', 'fingerprint', 'user-shield',
            // Education
            'graduation-cap', 'school', 'lightbulb', 'flask', 'atom', 'microscope',
            // People / comms
            'user', 'users', 'user-gear', 'id-card', 'envelope', 'paper-plane', 'comment', 'comments', 'bell', 'phone',
            // Misc
            'map', 'location-dot', 'calendar', 'clock', 'leaf', 'tree', 'seedling', 'sun', 'moon', 'snowflake',
            'plane', 'car', 'truck', 'anchor', 'magnet', 'wand-magic-sparkles', 'sparkles', 'circle-nodes',
        ];

        return array_values(array_unique(array_map(fn (string $n) => 'fa-solid fa-'.$n, $names)));
    }
}
