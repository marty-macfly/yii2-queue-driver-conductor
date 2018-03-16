<?php
namespace yii\queue\conductor\commands\actions;

use yii\helpers\Console;
use yii\queue\cli\Action;

/**
 * Create/Update task/workflow defintion on conductor based on the task/workflow deintion in the model (see TaskDefBehavior or WorkflowDefBehavior).
 *
 * @author Charles Delfly <charles@delfly.fr>
 */
class CreateAction extends Action
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Create/Update task/workflow defintion on conductor based on the task/workflow deintion in the model (see TaskDefBehavior or WorkflowDefBehavior).
     *
     * @return null|int exit code.
     */
    public function run($modelName)
    {
        $this->queue->create($modelName, $this->queue->conductor);
    }
}
