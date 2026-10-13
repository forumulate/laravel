<?php

return [
    // Your Forumulate address, or the custom domain your spaces are served on.
    'base_url' => env('FORUMULATE_BASE_URL', 'https://forumulate.com'),

    // Defaults to base_url . '/api'.
    'api_url' => env('FORUMULATE_API_URL'),

    // Organization and default space (slug or id). Every component and helper
    // also accepts a space of its own.
    'org' => env('FORUMULATE_ORG'),
    'space' => env('FORUMULATE_SPACE'),

    // 'script': widget.js / comments.js / changelog.js served by the API.
    // 'elements': <forumulate-*> custom elements from the @forumulate/embed package.
    'mode' => env('FORUMULATE_MODE', 'script'),
    'elements_src' => env('FORUMULATE_ELEMENTS_SRC', 'https://cdn.jsdelivr.net/npm/@forumulate/embed@1'),

    // Seconds a content read stays in the cache, and which cache store to use.
    'cache_ttl' => (int) env('FORUMULATE_CACHE_TTL', 300),
    'cache_store' => env('FORUMULATE_CACHE_STORE'),
    'timeout' => 5,
];
