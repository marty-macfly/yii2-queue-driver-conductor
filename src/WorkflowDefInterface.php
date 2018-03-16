<?php

namespace yii\queue\conductor;

/**
 * Interface WorkflowDefInterface
 *
 * @link https://netflix.github.io/conductor/metadata/#workflow-definition
 * @author Charles Delfly <mmacfly@gmail.com>
 */
interface WorkflowDefInterface
{
    const TASK_TYPE_SIMPLE = 'SIMPLE';
    /**
     * Returns task defintion.
     * @return array task definition
     */
    public function getDef();
}
