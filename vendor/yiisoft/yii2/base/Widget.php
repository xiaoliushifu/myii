<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use ReflectionClass;

/**
* Widget是小部件的基类
 * Widget is the base class for widgets.
 *小部件是在视图view中使用的可重复单元，就是用来生成对应视图代码（HTML）的php封装。
 * For more details and usage information on Widget, see the [guide article on widgets](guide:structure-widgets).
 * 小部件与视图文件相比，就是小巧型的，可重用的。不像普通的视图文件，大部分是php脚本。
 * @property string $id ID of the widget.
 * @property \yii\web\View $view The view object that can be used to render views or view files. Note that the
 * type of this property differs in getter and setter. See [[getView()]] and [[setView()]] for details.
 * @property string $viewPath The directory containing the view files for this widget. This property is
 * read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Widget extends Component implements ViewContextInterface
{
    /**
	 * 事件名，见名之意，init自然是在init方法里触发
     * @event Event an event that is triggered when the widget is initialized via [[init()]].
     * @since 2.0.11
     */
    const EVENT_INIT = 'init';
    /**
     * @event WidgetEvent an event raised right before executing a widget.
     * You may set [[WidgetEvent::isValid]] to be false to cancel the widget execution.
     * @since 2.0.11
     */
    const EVENT_BEFORE_RUN = 'beforeRun';
    /**
     * @event WidgetEvent an event raised right after executing a widget.
     * @since 2.0.11
     */
    const EVENT_AFTER_RUN = 'afterRun';

    /**
     * @var int a counter used to generate [[id]] for widgets.
     * @internal
     */
    public static $counter = 0;
    /**
     * @var string the prefix to the automatically generated widget IDs.
     * @see getId()
     */
    public static $autoIdPrefix = 'w';
    /**
	* 用数组实现的栈逻辑，静态。一般保存当前正被渲染的小部件对象，主要在begin和end方法里使用
     * @var Widget[] the widgets that are currently being rendered (not ended). This property
     * is maintained by [[begin()]] and [[end()]] methods.
     * @internal
     */
    public static $stack = [];


    /**
     * Initializes the object.
     * This method is called at the end of the constructor.
     * The default implementation will trigger an [[EVENT_INIT]] event.
     */
    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);//触发init事件
    }

    /**
     * Begins a widget.
     * This method creates an instance of the calling class. It will apply the configuration
     * to the created instance. A matching [[end()]] call should be called later.
     * As some widgets may use output buffering, the [[end()]] call should be made in the same view
     * to avoid breaking the nesting of output buffers.
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @return static the newly created widget instance
     * @see end()
     */
    public static function begin($config = [])
    {
		//自动填充当前调用的类（不是父类，而是实际发挥作用的子类），组成完整的配置信息。
		//因为该方法是静态，故调用时并未实例化对象，这里class下标是实例化的关键
        $config['class'] = get_called_class();
        /* @var $widget Widget */
        $widget = Yii::createObject($config);//用反射机制去实例化
        static::$stack[] = $widget;//存储到当前栈中

        return $widget;
    }

    /**
     * Ends a widget.
     * Note that the rendering result of the widget is directly echoed out.
     * @return static the widget instance that is ended.
     * @throws InvalidCallException if [[begin()]] and [[end()]] calls are not properly nested
     * @see begin()
     */
    public static function end()
    {
        if (!empty(static::$stack)) {
			//取出刚才的小部件对象
            $widget = array_pop(static::$stack);
            if (get_class($widget) === get_called_class()) {
                /* @var $widget Widget */
                if ($widget->beforeRun()) {
					//小部件的核心方法，就是run
                    $result = $widget->run();
                    $result = $widget->afterRun($result);
                    echo $result;
                }
                return $widget;
            } else {
                throw new InvalidCallException('Expecting end() of ' . get_class($widget) . ', found ' . get_called_class());
            }
        } else {
            throw new InvalidCallException('Unexpected ' . get_called_class() . '::end() call. A matching begin() is not found.');
        }
    }

    /**
	 * 一个方法完成小部件的渲染（与上述的begin(),end()相比是另一种渲染方式）
     * Creates a widget instance and runs it.
     * The widget rendering result is returned by this method.
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @return string the rendering result of the widget.
     * @throws \Exception
     */
    public static function widget($config = [])
    {
        ob_start();
        ob_implicit_flush(false);
        try {
            /* @var $widget Widget */
            $config['class'] = get_called_class();
            $widget = Yii::createObject($config);
            $out = '';
            if ($widget->beforeRun()) {
				//直接调用run()完事。
                $result = $widget->run();
                $out = $widget->afterRun($result);
            }
        } catch (\Exception $e) {
            // close the output buffer opened above if it has not been closed already
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }

        return ob_get_clean() . $out;
    }

    private $_id;

    /**
     * Returns the ID of the widget.
     * @param bool $autoGenerate whether to generate an ID if it is not set previously
     * @return string ID of the widget.
     */
    public function getId($autoGenerate = true)
    {
        if ($autoGenerate && $this->_id === null) {
            $this->_id = static::$autoIdPrefix . static::$counter++;
        }

        return $this->_id;
    }

    /**
     * Sets the ID of the widget.
     * @param string $value id of the widget.
     */
    public function setId($value)
    {
        $this->_id = $value;
    }

    private $_view;

    /**
     * Returns the view object that can be used to render views or view files.
     * The [[render()]] and [[renderFile()]] methods will use
     * this view object to implement the actual view rendering.
     * If not set, it will default to the "view" application component.
     * @return \yii\web\View the view object that can be used to render views or view files.
     */
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = Yii::$app->getView();
        }

        return $this->_view;
    }

    /**
     * Sets the view object to be used by this widget.
     * @param View $view the view object that can be used to render views or view files.
     */
    public function setView($view)
    {
        $this->_view = $view;
    }

    /**
	* 执行run，需要每个小部件子类各自完成，这个虽然是个空实现，其实是个接口。
     * Executes the widget.
     * @return string the result of widget execution to be outputted.
     */
    public function run()
    {
    }

    /**
     * Renders a view.
     * The view to be rendered can be specified in one of the following formats:
     *
     * - path alias (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of the currently
     *   active module.
     * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
     *
     * If the view name does not contain a file extension, it will use the default one `.php`.
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function render($view, $params = [])
    {
        return $this->getView()->render($view, $params, $this);
    }

    /**
     * Renders a view file.
     * @param string $file the view file to be rendered. This can be either a file path or a path alias.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file does not exist.
     */
    public function renderFile($file, $params = [])
    {
        return $this->getView()->renderFile($file, $params, $this);
    }

    /**
     * Returns the directory containing the view files for this widget.
     * The default implementation returns the 'views' subdirectory under the directory containing the widget class file.
     * @return string the directory containing the view files for this widget.
     */
    public function getViewPath()
    {
        $class = new ReflectionClass($this);

        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views';
    }
    
    /**
     * This method is invoked right before the widget is executed.
     *
     * The method will trigger the [[EVENT_BEFORE_RUN]] event. The return value of the method
     * will determine whether the widget should continue to run.
     *
     * When overriding this method, make sure you call the parent implementation like the following:
     *
     * ```php
     * public function beforeRun()
     * {
     *     if (!parent::beforeRun()) {
     *         return false;
     *     }
     *
     *     // your custom code here
     *
     *     return true; // or false to not run the widget
     * }
     * ```
     *
     * @return bool whether the widget should continue to be executed.
     * @since 2.0.11
     */
    public function beforeRun()
    {
        $event = new WidgetEvent();
        $this->trigger(self::EVENT_BEFORE_RUN, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after a widget is executed.
     *
     * The method will trigger the [[EVENT_AFTER_RUN]] event. The return value of the method
     * will be used as the widget return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterRun($result)
     * {
     *     $result = parent::afterRun($result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param mixed $result the widget return result.
     * @return mixed the processed widget result.
     * @since 2.0.11
     */
    public function afterRun($result)
    {
        $event = new WidgetEvent();
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_RUN, $event);
        return $event->result;
    }
}
