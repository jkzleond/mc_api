<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    array(
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->pluginsDir
    )
);

/**
 * 注册命名空间
 */
$loader->registerNamespaces(
    array(
        'Twm\Db\Adapter\Pdo' => __DIR__.'/../library/twm/db/adapter',
        'Twm\Db\Dialect' => __DIR__.'/../library/twm/db/dialect',
        'Palm\Utils' => $config->application->libraryDir.'palm/utils',
        'Palm\Exception' => $config->application->libraryDir.'palm/exception'
    )
);

$loader->register();
