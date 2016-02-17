<?php

namespace Hyn\LaravelFlarum\Flarum\Api;

use Flarum\Api\JsonApiResponse;
use Flarum\Api\Server as FlarumServer;
use Flarum\Event\ConfigureMiddleware;
use Illuminate\Contracts\Foundation\Application;
use Zend\Stratigility\MiddlewarePipe;

class Server extends FlarumServer {
    protected function getApp()
    {
        return app();
    }

    /**
     * {@inheritdoc}
     */
    protected function getMiddleware(Application $app)
    {
        $pipe = new MiddlewarePipe;

        $path = config('hyn.laravel-flarum.paths.api');

//        if ($app->isInstalled() && $app->isUpToDate()) {
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\ParseJsonBody'));
            $pipe->pipe($path, $app->make('Flarum\Api\Middleware\FakeHttpMethods'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\StartSession'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\RememberFromCookie'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\AuthenticateWithSession'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\AuthenticateWithHeader'));
            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\SetLocale'));

            event(new ConfigureMiddleware($pipe, $path, $this));

            $pipe->pipe($path, $app->make('Flarum\Http\Middleware\DispatchRoute', ['routes' => $app->make('flarum.api.routes')]));
            $pipe->pipe($path, $app->make('Flarum\Api\Middleware\HandleErrors'));
//        } else {
//            $pipe->pipe($path, function () {
//                $document = new Document;
//                $document->setErrors([
//                    [
//                        'code' => 503,
//                        'title' => 'Service Unavailable'
//                    ]
//                ]);
//
//                return new JsonApiResponse($document, 503);
//            });
//        }

        return $pipe;
    }
}