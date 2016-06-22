<?php

return new \Phalcon\Config(array(
    'database' => array(
        'adapter'     => 'Twm\Db\Adapter\Pdo\Mssql',
        'host'        => '116.55.248.76:31433',
        'username'    => 'sa_iamis',
        'password'    => 'pl0871iamis',
        'dbname'      => 'IAMisDB',
        'pdoType'     => 'dblib',
        'dialectClass' => 'Twm\Db\Dialect\Mssql',
        'charset' => 'UTF-8'
    ),
    'application' => array(
        'controllersDir' => __DIR__ . '/../../app/controllers/',
        'modelsDir'      => __DIR__ . '/../../app/models/',
        'migrationsDir'  => __DIR__ . '/../../app/migrations/',
        'viewsDir'       => __DIR__ . '/../../app/views/',
        'pluginsDir'     => __DIR__ . '/../../app/plugins/',
        'libraryDir'     => __DIR__ . '/../../app/library/',
        'cacheDir'       => __DIR__ . '/../../app/cache/',
        'baseUri'        => '',
    ),
    'cors' => array(
        'allow_hosts' => array('http://localhost:8801', 'http://116.55.248.76', 'http://192.168.31.247:8801', 'http://192.168.100.248:8801', 'http://116.55.248.76:8090')
    )
));
