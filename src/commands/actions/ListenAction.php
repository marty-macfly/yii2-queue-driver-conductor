<?php
namespace yii\queue\conductor\commands\actions;

use yii\helpers\Console;
use yii\queue\cli\Action;

/**
 * Listens condcutor-queue and runs new jobs.
 *
 * @author Charles Delfly <charles@delfly.fr>
 */
class ListenAction extends Action
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Listens condcutor-queue and runs new jobs.
     * It can be used as daemon process.
     *
     * @param int $timeout number of seconds to sleep before next reading of the queue.
     * @return null|int exit code.
     */
    public function run($timeout = 3)
    {
        return $this->queue->run(true, $timeout);
    }
}
