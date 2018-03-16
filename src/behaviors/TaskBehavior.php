<?php
namespace yii\queue\conductor\behaviors;

use Yii;
use yii\base\Behavior;

use yii\queue\conductor\TaskDefInterface;

/**
 * TaskBehavior provide all the attribute needed to define a task definition based on Yii model
 *
 * To use TaskBehavior, insert the following code to your Model class:
 *
 * ```php
 * use yii\queue\conductor\behaviors\TaskBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         TaskBehavior::className(),
 *     ];
 * }
 * ```
 *
 * By default, TaskBehavior will fill the `name` attribute with the model class
 * `ownerApp` will be `Yii::$app->name` and task `inputKeys` will be the model attributes.
 *
 * If want to change the definition attribute, you may configure all the properties like the following:
 *
 * ```php
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => TaskBehavior::className(),
 *             'timeoutSeconds' => 300,
 *             'inputKeys' => ['attrbute1', 'attribute2'],
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Charles Delfly <charles@delfly.fr>
 * @link https://netflix.github.io/conductor/metadata/#task-definition
 */
class TaskBehavior extends Behavior implements TaskDefInterface
{
    /**
    * @var string Task unique name
    */
    private $name = null;
    /**
    * @var int No. of retries to attempt when a task is marked as failure
    */
    public $retryCount = 0;
    /**
    * @var string Mechanism for the retries
    */
    public $retryLogic = self::RETRY_LOGIC_FIXED;
    /**
    * @var int Time to wait in seconds, before retry
    */
    public $retryDelaySeconds = 1;
    /**
    * @var int Time in milliseconds, after which the task is marked as TIMED_OUT if not completed after transiting to IN_PROGRESS status
    */
    public $timeoutSeconds = 0;
    /**
    * @var string Task's timeout policy
    */
    public $timeoutPolicy = self::TIMEOUT_POLICY_TIME_OUT_WF;
    /**
    * @var int if greater than 0, the task is rescheduled if not updated with a status after this time. Useful when the worker polls for the task but fails to complete due to errors/network failure.
    */
    public $responseTimeoutSeconds = 0;
    /**
    * @var string Task's onwer app
    */
    private $ownerApp = null;
    /**
    * @var int if greater than 0, only the number of task can be run in parallels.
    */
    public $concurrentExecLimit = 0;

    /**
    * @var array|null List of input attributes
    */
    private $inputKeys = null;
    /**
    * @var array|null List of output attributes
    */
    private $outputKeys = [];

    /**
     * Returns inputs values.
     * @return array inputs name
     */
    public function getInputKeys()
    {
        if ($this->inputKeys === null) {
            $this->inputKeys = $this->owner->attributes();
            $this->inputKeys[] = 'class'; // add class has a task input so we don't need to specify the model in the worker.
        }

        return $this->inputKeys;
    }

    /**
     * Returns outputs values.
     * @return array outputs name
     */
    public function getOutputKeys()
    {
        return $this->outputKeys;
    }

    /**
     * Set task name.
     */
    public function setName($value)
    {
        $this->name = $value;
    }

    /**
     * Returns task name.
     * @return string task name
     */
    public function getName()
    {
        if ($this->name === null) {
            $this->name = $this->owner->className();
        }
        return $this->name;
    }

    /**
     * Returns the value for ownerApp.
     * @return string application owner
     */
    public function setOwnerApp($value)
    {
        $this->ownerApp =$value;
    }

    /**
     * Returns the value for ownerApp.
     * @return string application owner
     */
    public function getOwnerApp()
    {
        if ($this->ownerApp === null) {
            $this->ownerApp = Yii::$app->name;
        }

        return $this->ownerApp;
    }

    /**
     * Returns task defintion.
     * @return array task definition
     */
    public function getDef()
    {
        $def = [
            'ownerApp' => $this->getOwnerApp(),
            'name' => $this->getName(),
            'retryCount' => $this->retryCount,
            'timeoutSeconds' =>  $this->timeoutSeconds,
            'inputKeys' =>  $this->getInputKeys(),
            'outputKeys' =>  $this->getOutputKeys(),
            'timeoutPolicy' =>  $this->timeoutPolicy,
            'retryLogic' =>  $this->retryLogic,
            'retryDelaySeconds' =>  $this->retryDelaySeconds,
            'responseTimeoutSeconds' =>  $this->responseTimeoutSeconds,
        ];

        if ($this->concurrentExecLimit > 0) {
            $def['concurrentExecLimit'] = $this->concurrentExecLimit;
        }

        return $def;
    }
}
