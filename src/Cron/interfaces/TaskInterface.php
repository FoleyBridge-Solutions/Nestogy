<?php

namespace Twetech\Nestogy\Interfaces;

/**
 * Interface for all tasks.
 */
interface TaskInterface
{
    /**
     * Execute the task.
     */
    public function execute(): void;
}
