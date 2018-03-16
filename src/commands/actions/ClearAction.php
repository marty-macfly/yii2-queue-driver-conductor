<?php
namespace yii\queue\conductor\commands\actions;

use yii\helpers\Console;
use yii\queue\cli\Action;

/**
 * Clears the queue.
 *
 * @author Charles Delfly <charles@delfly.fr>
 */
class ClearAction extends Action
{
    /**
     * @var Queue
     */
    public $queue;


    /**
     * Clears the queue.
     */
    public function run()
    {
        if (Console::confirm('Are you sure?')) {
            $this->queue->clear();
            Console::stdout("Queue has been cleared.\n");
        }
    }
}
