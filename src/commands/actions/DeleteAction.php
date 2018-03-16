<?php
namespace yii\queue\conductor\commands\actions;

use yii\helpers\Console;
use yii\queue\cli\Action;

/**
 * Delete task defintion on conductor based on the task/workflow deintion in the model (see TaskDefBehavior).
 *
 * @author Charles Delfly <charles@delfly.fr>
 */
class DeleteAction extends Action
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Delete/Update task defintion on conductor based on the task/workflow deintion in the model (see TaskDefBehavior).
     *
     * @return null|int exit code.
     */
    public function run($modelName)
    {
        $this->queue->delete($modelName, $this->queue->conductor);
    }
}
