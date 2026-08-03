<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);

$container = $app->getContainer();
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../templates', [
        'cache' => false
    ]);

    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(
        new \Slim\Http\Environment($_SERVER)
    );

    $view->addExtension(
        new \Slim\Views\TwigExtension($router, $uri)
    );

    return $view;
};

$container[\App\Controllers\IndexController::class] = function ($c) {
    return new \App\Controllers\IndexController();
};

$app->get('/', \App\Controllers\IndexController::class . ':getAllMidias');
$app->get('/all', \App\Controllers\IndexController::class . ':getAllMidias');
$app->get('/{sitename}', \App\Controllers\IndexController::class . ':getAllMidias');

$app->run();