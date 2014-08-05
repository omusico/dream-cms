<?php

return [
    'module_listener_options' => [
        'config_cache_enabled' => true,
        'config_cache_key' => APPLICATION_ENV,
        'module_map_cache_enabled' => true,
        'module_map_cache_key' => APPLICATION_ENV,
        'cache_dir' => APPLICATION_ROOT . '/data/cache/config'
    ]
];