<?php

return [
    /**
     * Prefix the Flarum installation with this prefix.
     */
    'route-prefix' => null,

    /**
     * The Laravel database connection to be used by Flarum.
     *
     * If set to null will use 'default', see the
     * config/database.php configuration file
     * of your laravel installation.
     */
    'database-connection' => null,

    /**
     * The url paths for the specific backends.
     */
    'paths' => [
        'api' => 'api',
        'admin' => 'admin'
    ]
];