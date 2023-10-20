<?php namespace Zephyrus\Utilities;

class Stopwatch
{
    private ?float $start;
    private bool $isRunning;
    private float $elapsed;

    public function __construct()
    {
        $this->isRunning = false;
        $this->elapsed = 0;
    }

    public function start(): void
    {
        $this->start = microtime(true);
        $this->elapsed = 0;
        $this->isRunning = true;
    }

    public function stop(): float
    {
        $this->elapsed = $this->getElapsed();
        $this->isRunning = false;
        return $this->getElapsed();
    }

    public function getElapsed(): float
    {
        if ($this->isRunning()) {
            return microtime(true) - $this->start;
        }
        return $this->elapsed;
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    public function __toString(): string
    {
        $elapsed = $this->getElapsed();
        return format('decimal', $elapsed, 2, 2);
    }
}