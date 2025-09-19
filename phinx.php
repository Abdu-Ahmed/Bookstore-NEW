<?php
declare(strict_types=1);

return [
    'paths' => [
        'migrations' => __DIR__ . '/database/migrations',
        'seeds'      => __DIR__ . '/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_database' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host'    => '127.0.0.1',
            'name'    => 'bookstore',   
            'user'    => 'root',        
            'pass'    => '',            
            'port'    => 3306,
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
