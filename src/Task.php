<?php
namespace yii\queue\conductor;

use Yii;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\queue\cli\LoopInterface;

/**
 * Task Queue
 *
 * @link https://netflix.github.io/conductor/intro/concepts/#task-definition
 * @author Charles Delfly <mmacfly@gmail.com>
 */
class Task extends Queue
{
    /**
     * @var null|string Task domain
     * @link https://netflix.github.io/conductor/domains/#how-to-use-task-domains
     */
    public $domain = null;
    /**
     * @var string job model if not provide in input
     */
    public $model;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
      * @inheritdoc
      */
    public function status($id)
    {
        $rs = $this->conductor->get([
            'api/tasks/' . $id
            ])->send();

        if ($rs->statusCode == 404) {
            throw new InvalidParamException("Unknown workflow ID: $id.");
        } elseif (!$rs->isOk) {
            throw new NotSupportedException($rs);
        }

        if (($status = ArrayHelper::getValue($rs->data, 'status')) !== null) {
            if (strcasecmp($status, 'SCHEDULED') === 0) {
                return self::STATUS_WAITING;
            } elseif (strcasecmp($status, 'IN_PROGRESS') === 0) {
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
            'api/admin/task/' . $this->name,
            ])->send();

        if (!$rs->isOk) {
            throw new NotSupportedException($rs);
        }

        foreach ($rs->data as $task) {
            $this->remove($task->taskId);
        }
    }

    /**
     * Removes a job by ID
     *
     * @param int $id of a job
     * @param int $reason to stop the job
     * @return bool
     */
    public function remove($id)
    {
        $rs = $this->conductor->delete([
            'api/tasks/queue/' . $this->name. '/' . $id,
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
        throw new NotSupportedException('Directly pushing a task is not supported by the driver');
    }

    public static function isTaskDef($task)
    {
        if ($task instanceof TaskDefInterface) {
            return true;
        } elseif ($task instanceof Model) {
            foreach ($task->getBehaviors() as $behavior) {
                if ($behavior instanceof TaskDefInterface) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Create/Update task definition on conductor.
     *
     * @return null|bool nothing to do (null), success (true) or failure (false).
     */
    public static function create($model)
    {
        if (class_exists($model)) {
            $task = Yii::createObject($model);
            if (self::isTaskDef($task)) {
                $taskDef = $task->def;
                $user = Yii::$app->has('user') ? Yii::$app->user->isGuest ? 'guest' : Yii::$app->user->identity->has('name') ? Yii::$app->user->identity->name : Yii::$app->user->id : 'unknown';
                $taskDef['createdBy'] = $user;
                $taskDef['updatedBy'] = $user;

                Yii::info(sprintf('Create/Update "%s" task definition', $model));
                $conductor = Instance::ensure(self::$conductor, components\Conductor::class);
                return $conductor->saveTaskDef($taskDef);
            } else {
                throw new InvalidParamException(sprintf("Model '%s'doesn't implement TaskDefInterface or doesn't have TaskDefBehavior", $model));
            }
        } else {
            throw new InvalidParamException(sprintf("Model '%s'doesn't exist", $model));
        }
        return null;
    }

    /**
     * Listens queue and runs each job.
     *
     * @param bool $repeat whether to continue listening when queue is empty.
     * @param int $timeout number of seconds to wait for next message.
     * @return null|int exit code.
     * @internal for worker command only.
     */
    public function run($repeat, $timeout = 0)
    {
        return $this->runWorker(function (LoopInterface $loop) use ($repeat, $timeout) {
            $uri = [
                '/api/tasks/poll/batch/' . $this->name,
                'count' => 1,
                'timeout' => $timeout * 1000,
                'workerid' => sprintf("%s-%s", gethostname(), $this->getWorkerPid()),
            ];

            if ($this->domain !== null) {
                $uri['domain'] = $this->domain;
            }

            while ($loop->canContinue()) {
                $rs = $this->conductor->get($uri, '', [], ['timeout' => $timeout + 1])->send();
                if ($rs->isOk && ($payload = ArrayHelper::getValue($rs->data, 0)) !== null) {
                    $id = ArrayHelper::getValue($payload, 'taskId');
                    // Acknowledge task reception to avoid requeue https://netflix.github.io/conductor/runtime/#schema-for-updating-task-result.
                    $rs = $this->conductor->post([
                            '/api/tasks/' . $id . '/ack',
                            'workerid' => ArrayHelper::getValue($uri, 'workerid'),
                        ])->send();

                    if (!$rs->isOK) {
                        Yii::error(sprintf("Error acknowledging task '%s': %s", $id, $rs));
                        continue;
                    }
                    $message = ArrayHelper::getValue($payload, 'inputData');

                    // Schedule the $delay https://netflix.github.io/conductor/faq/#how-do-you-schedule-a-task-to-be-put-in-the-queue-after-some-time-eg-1-hour-1-day-etc
                    if (($delay = ArrayHelper::getValue($message, 'delay')) !== null && is_int($delay) && $delay > 0) {
                        print_r($payload);
                        if ($delay == ArrayHelper::getValue($payload, 'callbackAfterSeconds')) {
                            unset($message['delay']);
                        } else {
                            $rs = $this->conductor->post('/api/tasks', [
                                'workflowInstanceId' => ArrayHelper::getValue($payload, 'workflowInstanceId'),
                                'taskId' => $id,
                                'callbackAfterSeconds' => $delay,
                                'workerId' => ArrayHelper::getValue($uri, 'workerid'),
                                'status' => 'IN_PROGRESS',
                            ])->send();
                            continue;
                        }
                    }

                    if (($ttr = ArrayHelper::getValue($message, 'ttr')) !== null) {
                        unset($message['ttr']);
                    }

                    $attempt = ArrayHelper::getValue($message, 'attempt', 1);
                    unset($attempt['attempt']);

                    if (is_array($message) && !isset($message['class']) && class_exists($this->model)) {
                        $message['class'] = $this->model;
                    }
                    if ($message) {
                        $return = $this->handleMessage($id, Json::encode($message), $ttr, $attempt);
                        $rs = $this->conductor->post('/api/tasks', [
                            'workflowInstanceId' => ArrayHelper::getValue($payload, 'workflowInstanceId'),
                            'taskId' => $id,
                            'workerId' => ArrayHelper::getValue($uri, 'workerid'),
                            'status' => $return ? 'COMPLETED' : 'FAILED',
                        ])->send();
                        if (!$rs->isOK) {
                            Yii::error(sprintf("Error changing task '%s' status to '%s': %s", $id, $return ? 'COMPLETED' : 'FAILED', $rs));
                            continue;
                        }
                    }
                } elseif (!$repeat) {
                    break;
                }
            }
        });
    }
}
