<?php
namespace yii\queue\conductor\commands\actions;

use yii\console\ExitCode;
use yii\helpers\Console;
use yii\queue\cli\Action;

/**
 * Removes a job by id.
 *
 * @author Charles Delfly <charles@delfly.fr>
 */
class RemoveAction extends Action
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Removes a job by id.
     *
     * @param int $id
     * @return int exit code
     */
    public function run($id)
    {
        if ($this->queue->remove($id)) {
            Console::stdout($this->format('The job has been removed.', Console::FG_GREEN));
            return ExitCode::OK;
        }

        Console::stdout($this->format('The job was not found.', Console::FG_YELLOW));
        return ExitCode::DATAERR;
    }
}
