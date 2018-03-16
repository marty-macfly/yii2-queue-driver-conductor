Conductor Driver
==============

[yiisoft/yii2-queue](https://github.com/yiisoft/yii2-queue) is an extension for running tasks asyncronously via queues.

This package is a new driver to make it works with [netflix/conductor](https://netflix.github.io/conductor/) [workflow](https://netflix.github.io/conductor/intro/concepts/#workflow-definition) (for push) and [task](https://netflix.github.io/conductor/intro/concepts/#task-definition) (for worker).

you'll find example how to use features which are specific to it. For more details see [yii2-queue guide](https://github.com/yiisoft/yii2-queue/blob/master/docs/guide/README.md).

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist macfly/yii2-queue-driver-conductor
```

or add

```
"macfly/yii2-queue-driver-conductor": "*"
```

to the require section of your `composer.json` file.

Workflow usage
-------------

Configuration example to push job to a conductor workflow:

```php
return [
    'bootstrap' => [
        'workflow', // The component registers own console commands
    ],
    'components' => [
        'conductor' => [
            'class'     => \yii\queue\conductor\components\Conductor::class,
            'baseUrl'   => 'http://127.0.0.1:8080',
            // ...
        ],
        'workflow' => [
            'class' => \yii\queue\conductor\Workflow::class,
            'name' => 'kitchensink', // Name of the workflow to trigger (https://netflix.github.io/conductor/metadata/kitchensink/)
            'version' => 12, // Optional. If not specified uses the latest version of the workflow (https://netflix.github.io/conductor/runtime/#with-input-only)
            'correlationId' => 'my_id_to_help_me_find_the_flow_trigger_by_that_component', // Optional. User supplied Id that can be used to retrieve workflows (https://netflix.github.io/conductor/runtime/#with-input-only)
            'taskToDomain' => ['task_1' => 'dev'], // Optional. see https://netflix.github.io/conductor/domains/ for more detail on domain usage.
            'supportDelayTtr' => true, // Optional (default: false). By default conductor doesn't not support delay and ttr, like the way they are used in yii2-queue, but we can make them work if the worker is also based on that driver and you're not overriding the serializer.
        ],
    ],
];
```

Usage in code
-------------

If you want to use model as a workflow input.
For example, if you want to create a model that will run the [Kitchensink Example](https://netflix.github.io/conductor/metadata/kitchensink/) workflow the class may look like the following:

```php
class KitchensinkModel extends \yii\base\Model
{
    public $mod;
    public $oddEven;
    public $task2Name;
}
```

Here's how to send a task into workflow:

```php
Yii::$app->workflow->push(new KitchensinkModel([
    'task2Name' => 'task_5',
]));
```
Pushes job into queue that run after 5 min:

```php
Yii::$app->workflow->delay(5 * 60)->push(new KitchensinkModel([
    'task2Name' => 'task_5',
]));
```

**Important:** see `supportDelayTtr` workflow component option to make that driver support delayed running.

Messaging third party workers
-----------------------------

You may pass any data to workflow:

```php
Yii::$app->workflow->push([
    'task2Name' => 'task_5',
]);
```

This is useful if the queue is processed using a specially developer third party worker. In that case you should set `supportDelayTtr` to `false` or modify your worker to manage `ttr` and `delay`. They will be in the task input, so you need to get them and manage them.

Console
-------

Console is used to manage workflow.

```sh
yii workflow/clear
```

`clear` command terminate all workflow of that type.

```sh
yii workflow/remove [id]
```

`remove` command terminate a workflow.

Task worker usage
-------------

Configuration example to process job from conductor task:

```php
return [
    'bootstrap' => [
        'task', // The component registers own console commands
    ],
    'components' => [
        'conductor' => [
            'class'     => \yii\queue\conductor\components\Conductor::class,
            'baseUrl'   => 'http://127.0.0.1:8080',
            // ...
        ],
        'task' => [
            'class' => \yii\queue\conductor\Task::class,
            'name' => 'task_1', // Name of the queue we want to bind on (in that example we will proceed the job of hez first task of the kitchensink workflow example https://netflix.github.io/conductor/metadata/kitchensink/)
            'model' => \app\models\TestTask::class, // Optional, if task are not send by the workflow component of that driver, we need to know which model will manage and proceed the data.
            'domain' => 'dev' // Optional, domain of task we want to proceed (see https://netflix.github.io/conductor/domains/ for more detail on domain usage).
        ],
    ],
];
```
Usage in code
-------------

Each task which is get from the queue should match an object or a model class (or the default one which is define in the `task` component will be used). For example, if you want to create a model that will run the first task (`task_1`) [Kitchensink Example](https://netflix.github.io/conductor/metadata/kitchensink/) workflow, the class may look like the following:

```php
class TestTask extends \yii\base\Model implements \yii\queue\JobInterface
{
    public $mod;
    public $oddEven;
    public $env;

    public function execute($queue)
    {
        echo "Do the job you want to do here\n";
    }
}
```

Console
-------

Console command is used to execute tasks.

```sh
yii task/listen [timeout]
```

`listen` command launches a worker in daemon which infinitely queries the queue. If there are new tasks
they're immediately obtained and executed. `timeout` parameter is number of seconds to wait a job.
This method is most efficient when command is properly daemonized via
[supervisor](worker.md#supervisor) or [systemd](worker.md#systemd).

```sh
yii task/run
```

`run` command obtains and executes tasks in a loop until queue is empty. Works well with
[cron](worker.md#cron).

`run` and `listen` commands have options:

- `--verbose`, `-v`: print executing statuses into console.
- `--isolate`: verbose mode of a job execute. If enabled, execute result of each job will be printed.
- `--color`: highlighting for verbose mode.

```sh
yii task/create [model]
```

`create` command create/update task definition of the given model. Model need to implement `TaskDefInterface` or has `TaskDefBehavior` attached.

Here is model example that support `TaskDefBehavior`:

```php
class TestTask extends \yii\base\Model implements \yii\queue\JobInterface
{
    public $mod;
    public $oddEven;
    public $env;

    public function behaviors()
    {
        return [
            \yii\queue\conductor\behaviors\TaskDefBehavior::className(),
        ];
    }

    public function execute($queue)
    {
        echo "Do the job you want to do here\n";
    }
}
```

```sh
yii task/clear
```

`clear` command clears a queue.

```sh
yii task/remove [id]
```

`remove` command removes a job.
