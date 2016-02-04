<?php

namespace Hyn\LaravelFlarum\Flarum\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Event\ConfigureForumRoutes;

class PrefixesForumRoutes {

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureForumRoutes::class, [$this, 'addPrefix']);
    }

    /**
     * @param ConfigureForumRoutes $event
     */
    public function addPrefix(ConfigureForumRoutes $event)
    {
        dd($event->routes);
    }
}