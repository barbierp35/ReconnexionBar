<?php

use Cake\Event\EventManager;
use ReconnexionBar\Middleware\BarMiddleware;

EventManager::instance()->on('Server.buildMiddleware', function ($event, $queue)  {
    $middleware = new BarMiddleware();
    $queue->insertAt(0, $middleware);
});