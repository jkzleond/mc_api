<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Mvc\Dispatcher;
use Twm\Db\Adapter\Pdo\Mssql as DbAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
}, true);

/**
 * The Dispatcher component
 */
$di->setShared('dispatcher', function() use ($di) {
    $eventsManager = $di->getShared('eventsManager');
    $dispatcher = new Dispatcher();
    $dispatcher->setEventsManager($eventsManager);
    return $dispatcher;
});

/**
 * Setting up the view component
 */
$di->set('view', function () use ($config) {

    $view = new View();

    $view->setViewsDir($config->application->viewsDir);
    $view->setRenderLevel(View::LEVEL_ACTION_VIEW);

    $view->registerEngines(array(
        '.volt' => function ($view, $di) use ($config) {

            $volt = new VoltEngine($view, $di);

            $volt->setOptions(array(
                'compiledPath' => $config->application->cacheDir,
                'compiledSeparator' => '_',
                'compileAlways' => true
            ));

            //add filter functions
            $compiler = $volt->getCompiler();
            $compiler->addFilter('uniform_time', function($resolvedArgs, $exprAgs){
                return "date('Y-m-d H:i:s', strtotime(".$resolvedArgs."))";
            });
            $compiler->addFilter('number_format', function($resolveArgs, $exprAgs){
                return 'number_format('.$resolveArgs.')';
            });
            $compiler->addFilter('urlencode', function($resolveArgs, $exprAgs){
                return 'urlencode('.$resolveArgs.')';
            });

            return $volt;
        },
        '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
    ));

    return $view;
}, true);

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function () use ($config) {
    return new DbAdapter(array(
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname,
        'charset' => $config->database->charset,
        'pdoType' => $config->database->pdoType,
        'dialectClass' => $config->database->dialectClass,
    ));
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    $session = new SessionAdapter();
    $session->start();
    //TODO set Expire infinity
    return $session;
});

/**
 * set flash component
 */
$di->setShared('flashSession', function() use($di) {
    $di->getShared('session');
    $flashSession = new \Phalcon\Flash\Session(array(
        'error' => 'alert alert-error',
        'success' => 'alert alert-success',
        'notice' => 'alert alert-info'
    ));
    return $flashSession;
});

/**
 * Set Crypt Component use to Cookies
 */

$di->set('crypt', function () {
    $crypt = new Phalcon\Crypt();
    $crypt->setKey('car_mate_local_favour');
    return $crypt;
});

/**
 * Set Config Component
 */
$di->set('config', function()use($config){
    return $config;
});
