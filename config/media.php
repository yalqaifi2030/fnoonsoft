<?php

return [
    // Static ffmpeg/ffprobe binaries (installed under /www/server/ffmpeg on the
    // production server). Override via .env on other environments.
    'ffmpeg' => env('FFMPEG_PATH', '/www/server/ffmpeg/ffmpeg'),
    'ffprobe' => env('FFPROBE_PATH', '/www/server/ffmpeg/ffprobe'),
];
