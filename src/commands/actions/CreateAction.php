<?php
namespace yii\queue\conductor\commands\actions;

use yii\helpers\Console;
use yii\queue\cli\Action;

/**
 * Create/Update task defintion on conductor based on the task deintion in the model.
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
     * Create/Update task defintion on conductor based on the task deintion in the model (see TaskBehavior).
     *
     * @return null|int exit code.
     */
    public function run($model)
    {
        $this->queue->create($model);
    }
}
