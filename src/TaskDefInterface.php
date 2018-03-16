<?php

namespace yii\queue\conductor;

/**
 * Interface TaskDefInterface
 *
 * @link https://netflix.github.io/conductor/metadata/#task-definition
 * @author Charles Delfly <mmacfly@gmail.com>
 */
interface TaskDefInterface
{
    /**
    * @see Task::retryLogic reschedule the task after the retryDelaySeconds
    */
    const RETRY_LOGIC_FIXED = 'FIXED';
    /**
    * @see Task::retryLogic reschedule after retryDelaySeconds * attempNo
    */
    const RETRY_LOGIC_EXPONENTIAL_BACKOFF = 'EXPONENTIAL_BACKOFF';
    /**
    * @see Task::timeoutPolicy retries the task again
    */
    const TIMEOUT_POLICY_RETRY = 'RETRY';
    /**
    * @see Task::timeoutPolicy workflow is marked as TIMED_OUT and terminated
    */
    const TIMEOUT_POLICY_TIME_OUT_WF = 'TIME_OUT_WF';
    /**
    * @see Task::timeoutPolicy registers a counter (task_timeout)
    */
    const TIMEOUT_POLICY_ALERT_ONLY = 'ALERT_ONLY';

    /**
     * Returns task defintion.
     * @return array task definition
     */
    public function getDef();
}
