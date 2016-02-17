<?php

namespace Hyn\LaravelFlarum\Flarum\Forum;

use Flarum\Forum\Server as FlarumServer;

class Server extends FlarumServer {
    protected function getApp()
    {
        return app();
    }
}