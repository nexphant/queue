<?php

namespace Nexph\Queue;

use Nexph\Lifecycle\Lifecycle;

class JobLifecycle
{
    public static function execute($job, callable $handler): void
    {
        $ctx = Lifecycle::job($job);
        try {
            $handler($job, $ctx);
        } finally {
            $ctx->close();
        }
    }
}
