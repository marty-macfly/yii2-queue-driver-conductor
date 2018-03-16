<?php
namespace yii\queue\conductor;

use Yii;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\serializers\JsonSerializer;

/**
 * Conductor Queue
 *
 * @link https://netflix.github.io/conductor/
 * @author Charles Delfly <mmacfly@gmail.com>
 */
abstract class Queue extends CliQueue
{
    /**
     * @var Conductor|array|string
     */
    public $conductor = 'conductor';
    /**
     * @var string workflow or task name
     */
    public $name = null;
    /**
     * @var string
     */
    public $commandClass = commands\Command::class;
    /**
     * @inheritdoc
     */
    public $serializer = JsonSerializer::class;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->name === null) {
            throw new InvalidConfigException('Queue configuration should contain a "name" element.');
        }

        parent::init();
        $this->conductor = Instance::ensure($this->conductor, components\Conductor::class);
    }
}
