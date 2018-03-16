<?php
namespace yii\queue\conductor\commands\actions;

use yii\helpers\Console;
use yii\queue\cli\Action;

/**
 * Runs all jobs from conductor-queue.
 *
 * @author Charles Delfly <charles@delfly.fr>
 */
class RunAction extends Action
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Runs all jobs from conductor-queue.
     * It can be used as cron job.
     *
     * @return null|int exit code.
     */
    public function run()
    {
        $this->queue->run(false);
    }
}
