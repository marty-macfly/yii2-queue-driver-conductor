<?php
namespace yii\queue\conductor;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
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
     * @var string workflow or task name to bind
     */
    public $name = null;
    /**
     * @var Conductor|array|string
     */
    public $conductor = 'conductor';
    /**
     * @var string
     */
    public $commandClass = commands\Command::class;
    /**
     * @inheritdoc
     */
    public $serializer = JsonSerializer::class;
    /**
     * @var string model with workflowDef or TaskDef.
     */
    public $model;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->name === null && class_exists($this->model)) {
            $model = Yii::createObject($this->model);
            if (self::isDefInterface($model)) {
                $this->name = $model->name;
            }
        }

        if ($this->name === null) {
            throw new InvalidConfigException(sprintf("%s configuration should contain a 'name' or a model with %sDefInterface element.", self::class, self::class));
        }

        parent::init();
        $this->conductor = Instance::ensure($this->conductor, components\Conductor::class);
    }

    public static function isDefInterface($model)
    {
        $class = self::class . 'DefInterface';
        if ($model instanceof $class) {
            return true;
        } elseif ($model instanceof Model) {
            foreach ($model->getBehaviors() as $behavior) {
                if ($behavior instanceof $class) {
                    return true;
                }
            }
        }
        return false;
    }
}
