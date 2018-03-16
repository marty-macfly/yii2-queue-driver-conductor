<?php
namespace yii\queue\conductor;

use Yii;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\queue\serializers\JsonSerializer;

/**
 * Workflow Queue
 *
 * @link https://netflix.github.io/conductor/intro/concepts/#workflow-definition
 * @author Charles Delfly <mmacfly@gmail.com>
 */
class Workflow extends Queue
{
    /**
     * @inheritdoc
     */
    public $strictJobType = false;
    /**
     * @var array Define the domain to use, Array of task domain mapping ('taskName' => 'domain')
     * @link https://netflix.github.io/conductor/domains/
     */
    public $taskToDomain = null;
    /**
     * @var int Define the workflow version to run should be > 0
     * @link https://netflix.github.io/conductor/runtime/#start-a-workflow
     */
    public $version = null;
    /**
     * @var string Define workflow correlation id.
     * @link https://netflix.github.io/conductor/runtime/#start-a-workflow
     */
    public $correlationId = null;
    /**
     * @var bool whether the workflow and the worker are supporting ttr and delay.
     * Note that in order to enable ttr and delay support, the only usable serializer is Json.
     */
    public $supportDelayTtr = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->version !== null && (!is_int($this->version) || $this->version <= 0)) {
            throw new InvalidParamException('Version should be a positive integer');
        }

        parent::init();
    }

    /**
      * @inheritdoc
      */
    public function status($id)
    {
        $rs = $this->conductor->get([
            'api/workflow/' . $id,
            'includeTasks' => 'false',
            ])->send();

        if ($rs->statusCode == 404) {
            throw new InvalidParamException("Unknown workflow ID: $id.");
        } elseif (!$rs->isOk) {
            throw new NotSupportedException($rs);
        }

        if (($status = ArrayHelper::getValue($rs->data, 'status')) !== null) {
            if (strcasecmp($status, 'PAUSED') === 0) {
                return self::STATUS_WAITING;
            } elseif (strcasecmp($status, 'RUNNING') === 0) {
                return self::STATUS_RESERVED;
            }

            return self::STATUS_DONE;
        } else {
            throw new NotSupportedException('No "status" field found' . $rs);
        }
    }

    /**
     * Clears the queue
     */
    public function clear($reason = '')
    {
        $rs = $this->conductor->get([
            'api/workflow/running/' . $this->name,
            ])->send();

        if (!$rs->isOk) {
            throw new NotSupportedException($rs);
        }

        foreach ($rs->data as $id) {
            $this->remove($id, $reason);
        }
    }

    /**
     * Removes a job by ID
     *
     * @param int $id of a job
     * @param int $reason to stop the job
     * @return bool
     */
    public function remove($id, $reason = '')
    {
        $rs = $this->conductor->delete([
            'api/workflow/' . $id,
            'reason' => $reason,
            ])->send();

        if ($rs->isOk) {
            return true;
        } elseif ($rs->statusCode == 404) {
            return false;
        }

        throw new NotSupportedException($rs);
    }

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        if ($priority !== null) {
            throw new NotSupportedException('Job priority is not supported in the driver.');
        }

        $data = [
            'name' => $this->name,
        ];

        if ($this->version !== null) {
            $data['version'] = $this->version;
        }
        if ($this->correlationId !== null) {
            $data['correlationId'] = $this->correlationId;
        }
        if (is_array($this->taskToDomain)) {
            $data['taskToDomain'] = $this->taskToDomain;
        }

        if ($this->serializer instanceof JsonSerializer) {
            $message = Json::decode($message);
            if ($this->supportDelayTtr) {
                if (!isset($message['delay'])) {
                    $message['delay'] = $delay;
                }
                if (!isset($message['ttr'])) {
                    $message['ttr'] = $ttr;
                }
            }
        } else {
            if ($delay) {
                throw new NotSupportedException('Job ttr is not supported with that serializer.');
            }
            if ($ttr) {
                throw new NotSupportedException('Job ttr is not supported with that serializer.');
            }
        }

        $data['input'] = $message;
        $rs = $this->conductor->post([
            'api/workflow',
        ], $data)->send();

        if (!$rs->isOk) {
            throw new NotSupportedException('Message push failed error :' . $rs);
        }

        return $rs->content;
    }

    public static function isWorkflowDef($workflow)
    {
        if ($task instanceof TaskDefInterface) {
            return true;
        } elseif ($workflow instanceof Model) {
            foreach ($workflow->getBehaviors() as $behavior) {
                if ($behavior instanceof TaskDefInterface) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Create/Update workflow definition on conductor.
     *
     * @return null|bool nothing to do (null), success (true) or failure (false).
     */
    public static function create($model, $conductor)
    {
        if (class_exists($model)) {
            $workflow = Yii::createObject($model);
            if ($this->isTaskDef($workflow)) {
                $workflowDef = $workflow->def;
                $user = Yii::$app->has('user') ? Yii::$app->user->isGuest ? 'guest' : Yii::$app->user->identity->has('name') ? Yii::$app->user->identity->name : Yii::$app->user->id : 'unknown';
                $workflowDef['createdBy'] = $user;
                $workflowDef['updatedBy'] = $user;

                foreach ($workflowDef['tasks'] as $id => $task) {
                    if (($name = ArrayHelper::getValue($task, 'taskReferenceName')) !== null && class_exists($name)) {
                        $task = Yii::createObject($model);
                        Task::create($name);
                        $workflowDef['tasks'][$id]['taskReferenceName'] = $task->getName();
                        $workflowDef['tasks'][$id]['type'] = self::TASK_TYPE_SIMPLE;
                    }
                }

                Yii::info(sprintf('Create/Update "%s" workflow definition', $model));

                return $conductor->saveWorkflowDef($workflowDef);
            } else {
                throw new InvalidParamException(sprintf("Model '%s'doesn't implement TaskDefInterface or doesn't have TaskDefBehavior", $model));
            }
        } else {
            throw new InvalidParamException(sprintf("Model '%s'doesn't exist", $model));
        }
        return null;
    }
}
