<?php

namespace Nexph\Queue;

use Nexph\Runtime\Channel;
use Nexph\Runtime\Runtime;
use Nexph\Runtime\Backpressure\BoundedExecutor;
use Nexph\Lifecycle\JobOwner;

class QueueRuntime
{
    private Channel $channel;
    private BoundedExecutor $executor;
    private int $concurrency;
    private int $backpressure;
    private bool $running = false;

    public function __construct(
        int $concurrency = 256,
        int $backpressure = 10000
    ) {
        $this->concurrency = $concurrency;
        $this->backpressure = $backpressure;
        $this->channel = new Channel($backpressure);
        $this->executor = new BoundedExecutor($concurrency, $backpressure);
    }

    public function concurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;
        $this->executor = new BoundedExecutor($concurrency, $this->backpressure);
        return $this;
    }

    public function backpressure(int $backpressure): self
    {
        $this->backpressure = $backpressure;
        $this->channel = new Channel($backpressure);
        $this->executor = new BoundedExecutor($this->concurrency, $backpressure);
        return $this;
    }

    public function push(callable $job): void
    {
        $this->channel->send($job);
    }

    public function consume(callable $handler): void
    {
        if ($this->running) {
            return;
        }
        $this->running = true;

        $worker = function () use ($handler): void {
            while ($this->running) {
                $job = $this->channel->receive();
                if ($job === null) {
                    break;
                }

                $this->executor->submit(function () use ($job, $handler): void {
                    $ctx = new JobOwner($job);
                    try {
                        $handler($job, $ctx);
                    } finally {
                        $ctx->cancel();
                        $ctx->close();
                    }
                });
            }
        };

        if (Runtime::available()) {
            Runtime::spawn($worker);
        } else {
            $worker();
        }
    }

    public function stop(): void
    {
        $this->running = false;
        $this->channel->close();
    }

    public function metrics(): array
    {
        return $this->executor->metrics();
    }
}
