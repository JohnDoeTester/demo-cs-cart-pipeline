<?php

$config = \Tygh\Registry::get('config');

$connect_info = explode(':', $config['db_host']);

if ($config['database_backend'] == 'pdo' && class_exists('PDO') && in_array('mysql', \PDO::getAvailableDrivers(), true)) {
    $adapter = 'mysql';
} else {
    $adapter = 'mysqli';
}

$options = array(
    'paths' => array(
        'migrations' => $config['dir']['migrations'],
    ),
    'environments' => array(
        'default_migration_table' => 'phinxlog' . TIME,
        'default_database' => 'development',
        'development' => array(
            'dir_root' => DIR_ROOT,
            'adapter' => $adapter,
            'host' => $connect_info[0],
            'name' => $config['db_name'],
            'user' => $config['db_user'],
            'pass' => $config['db_password'],
            'charset' => 'utf8',
            'prefix' => $config['table_prefix'],
        ),
    ),
);

if (isset($connect_info[1])) {
    $options['environments']['development']['port'] = $connect_info[1];
}

return $options;
