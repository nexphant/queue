<?php

namespace Nexph\Queue;

class CoroutineConsumer
{
    private int $concurrency;

    public function __construct(int $concurrency = 256)
    {
        $this->concurrency = $concurrency;
    }

    public function consume(callable $handler): void
    {
        for ($i = 0; $i < $this->concurrency; $i++) {
            $this->spawn(function() use ($handler) {
                while ($job = $this->receiveJob()) {
                    $handler($job);
                }
            });
        }
    }

    private function spawn(callable $fn): void
    {
    }

    private function receiveJob(): mixed
    {
        return null;
    }
}
