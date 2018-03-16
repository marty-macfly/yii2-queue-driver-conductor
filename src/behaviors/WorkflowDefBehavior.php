<?php
namespace yii\queue\conductor\behaviors;

use Yii;
use yii\base\Behavior;

use yii\queue\conductor\WorkflowDefInterface;

/**
 * WorkflowDefBehavior provide all the attribute needed to define a Workflow definition based on Yii model
 *
 * To use WorkflowDefBehavior, insert the following code to your Model class:
 *
 * ```php
 * use yii\queue\conductor\behaviors\WorkflowDefBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         WorkflowDefBehavior::className(),
 *     ];
 * }
 * ```
 *
 * By default, WorkflowDefBehavior will fill the `name` attribute with the model class
 * `ownerApp` will be `Yii::$app->name` and Workflow `inputParameters` will be the model attributes.
 *
 * If want to change the definition attribute, you may configure all the properties like the following:
 *
 * ```php
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => WorkflowDefBehavior::className(),
 *             'timeoutSeconds' => 300,
 *             'inputParameters' => ['attrbute1', 'attribute2'],
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Charles Delfly <charles@delfly.fr>
 * @link https://netflix.github.io/conductor/metadata/#workflow-definition
 */
class WorkflowDefBehavior extends Behavior implements WorkflowDefInterface
{
    /**
    * @var string Workflow unique name
    */
    private $name = null;
    /**
    * @var string Descriptive name of the workflow
    */
    public $description = '';
    /**
    * @var int Numeric field used to identify the version of the schema. Use incrementing numbers
    */
    public $version = 0;
    /**
    * @var array An array of task definitions
    */
    public $tasks = [];
    /**
    * @var string Workflow's onwer app
    */
    private $ownerApp = null;
    /**
    * @var array|null List of input parameters. Used for documenting the required inputs to workflow.
    */
    private $inputParameters = null;
    /**
    * @var array|null List of output of the workflow, if not specified, the output is defined as the output of the last executed task.
    */
    private $outputParameters = [];

    /**
     * Returns inputs values.
     * @return array inputs name
     */
    public function getInputParameters()
    {
        if ($this->inputParameters === null) {
            $this->inputParameters = $this->owner->attributes();
        }

        return $this->inputParameters;
    }

    /**
     * Set inputs values.
     */
    public function setInputParameters($value)
    {
        $this->inputParameters = $value;
    }

    /**
     * Returns outputs values.
     * @return array outputs name
     */
    public function getOutputParameters()
    {
        return $this->outputParameters;
    }

    /**
     * Set outputs values.
     */
    public function setOutputParameters($value)
    {
        $this->outputParameters = $value;
    }

    /**
     * Set Workflow name.
     */
    public function setName($value)
    {
        $this->name = $value;
    }

    /**
     * Returns Workflow name.
     * @return string Workflow name
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
     * Returns Workflow defintion.
     * @return array Workflow definition
     */
    public function getDef()
    {
        $def = [
            'ownerApp' => $this->getOwnerApp(),
            'name' => $this->getName(),
            'description' => $this->description,
            'tasks' =>  $this->getTasks(),
            'inputParameters' =>  $this->getInputParameters(),
            'outputParameters' =>  $this->getOutputParameters(),
        ];

        if ($this->version > 0) {
            $def['version'] = $this->version;
        }
        return $def;
    }
}
