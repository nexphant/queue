<?php

/**
 * This file is part of the nexphant Framework.
 *
 * (c) nexphant <https://github.com/nexphant>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Nexphant\Queue\Drivers;

use Nexphant\Queue\QueueDriver;
use Nexphant\Queue\Job;

/**
 * In-memory queue driver.
 * 
 * Fast, no persistence. Good for testing and development.
 */
class MemoryDriver implements QueueDriver
{
    private array $jobs = [];
    private array $deadLetters = [];

    public function push(Job $job): void
    {
        Job::assertValidId($job->id);
        $this->jobs[$job->id] = $job;
    }

    public function pop(): ?Job
    {
        $now = time();

        foreach ($this->jobs as $id => $job) {
            if ($job->status === 'pending' && $job->available_at <= $now) {
                // Mark as taken by changing status
                $job->status = 'reserved';
                return $job;
            }
        }

        return null;
    }

    public function update(Job $job): void
    {
        Job::assertValidId($job->id);
        $this->jobs[$job->id] = $job;
    }

    public function get(string $id): ?Job
    {
        Job::assertValidId($id);
        return $this->jobs[$id] ?? null;
    }

    public function delete(string $id): void
    {
        Job::assertValidId($id);
        unset($this->jobs[$id]);
    }

    public function depth(): int
    {
        $count = 0;
        $now = time();

        foreach ($this->jobs as $job) {
            if ($job->status === 'pending' && $job->available_at <= $now) {
                $count++;
            }
        }

        return $count;
    }

    public function pushDeadLetter(Job $job): void
    {
        Job::assertValidId($job->id);
        $this->deadLetters[$job->id] = $job;
    }

    public function getDeadLetters(int $limit = 100): array
    {
        return array_values(array_slice($this->deadLetters, 0, $limit));
    }

    public function clear(): void
    {
        $this->jobs = [];
        $this->deadLetters = [];
    }
}
