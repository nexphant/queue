<?php

namespace nexphant\Queue;

use nexphant\Runtime\Runtime;

class CoroutineConsumer
{
    private int $concurrency;
    private bool $running = false;
    private $receiver = null;

    public function __construct(int $concurrency = 256, ?callable $receiver = null)
    {
        $this->concurrency = $concurrency;
        $this->receiver = $receiver;
    }

    public function consume(callable $handler): void
    {
        $this->running = true;
        for ($i = 0; $i < $this->concurrency; $i++) {
            $this->spawn(function() use ($handler): void {
                while ($this->running) {
                    $job = $this->receiveJob();
                    if ($job === null) {
                        break;
                    }
                    JobLifecycle::execute($job, $handler);
                }
            });
        }
        if (Runtime::available() && !Runtime::isRunning()) {
            Runtime::run();
        }
    }

    public function stop(): void
    {
        $this->running = false;
    }

    private function spawn(callable $fn): void
    {
        if (Runtime::available()) {
            Runtime::spawn($fn);
            return;
        }
        $fn();
    }

    private function receiveJob(): mixed
    {
        if (!$this->receiver) {
            return null;
        }
        return ($this->receiver)();
    }
}
