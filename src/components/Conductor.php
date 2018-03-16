<?php
namespace yii\queue\conductor\components;

use Yii;
use yii\base\InvalidArgumentException;
use yii\httpclient\Client;
use yii\helpers\ArrayHelper;

/**
 * Conductor Component
 *
 * @link https://netflix.github.io/conductor/
 * @author Charles Delfly <mmacfly@gmail.com>
 */
class Conductor extends Client
{
    /** @inheritdoc */
    public $requestConfig = [
        'format' => Client::FORMAT_JSON,
    ];
    /** @inheritdoc */
    public $responseConfig = [
        'format' => Client::FORMAT_JSON
    ];

    public function getTaskDef($taskDefName)
    {
        $rs = $this->get('/api/metadata/taskdefs/' . urlencode($taskDefName))->send();
        if ($rs->statusCode == 200) {
            return $rs->data;
        } elseif ($rs->statusCode == 204) {
            return null;
        }

        throw new \Exception('Unable to get Taskdef return: ' . $rs);
    }

    public function existTaskDef($taskDefName)
    {
        return $this->getTaskDef($taskDefName) !== null;
    }

    public function saveTaskDef($taskDef)
    {
        if (($taskDefName = ArrayHelper::getValue($taskDef, 'name')) === null) {
            throw new InvalidArgumentException('TaskDef should at least have a "name"');
        }

        if (($oldDef = $this->getTaskdef($taskDefName)) === null) {
            $method = 'POST';
            $data = [$taskDef];
        } else {
            $method = 'PUT';
            $taskDef['createTime'] = ArrayHelper::getValue($oldDef, 'createTime');
            $taskDef['createdBy'] = ArrayHelper::getValue($oldDef, 'createdBy');
            $data = $taskDef;
        }

        $rs = $this->createRequest()
                    ->setMethod($method)
                    ->setUrl('/api/metadata/taskdefs')
                    ->setData($data)
                    ->send();
        return $rs->isOk;
    }
}
