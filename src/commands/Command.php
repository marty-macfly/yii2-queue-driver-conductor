<?php
namespace yii\queue\conductor\commands;

use yii\base\NotSupportedException;
use yii\queue\cli\Command as CliCommand;

use yii\queue\conductor\Task;
use yii\queue\conductor\Workflow;

/**
 * Manages application conductor-queue.
 *
 * @author Charles Delfly <charles@delfly.fr>
 */
class Command extends CliCommand
{
    /**
     * @var Queue
     */
    public $queue;
    /**
     * @var string
     */
    public $defaultAction = 'info';
    /**
     * @var string reason of job removal.
     */
    public $reason;

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = [];
        if ($this->queue instanceof Workflow) {
            $actions['create'] = actions\CreateAction::class;
            $actions['remove'] = actions\RemoveAction::class;
            $actions['clear'] = actions\ClearAction::class;
        } elseif ($this->queue instanceof Task) {
            $actions['create'] = actions\CreateAction::class;
            $actions['remove'] = actions\RemoveAction::class;
            $actions['clear'] = actions\ClearAction::class;
            $actions['run'] = actions\RunAction::class;
            $actions['listen'] = actions\ListenAction::class;
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected function isWorkerAction($actionID)
    {
        return in_array($actionID, array_keys($this->actions()));
    }
}
