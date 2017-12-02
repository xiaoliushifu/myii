<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use yii\helpers\FileHelper;
use yii\widgets\Block;
use yii\widgets\ContentDecorator;
use yii\widgets\FragmentCache;

/**
 * View组件在MVC模式里代表视图对象
 * View represents a view object in the MVC pattern.
 * View组件提供了一系列的方法，比如render()来渲染视图（文件）
 * View provides a set of methods (e.g. [[render()]]) for rendering purpose.
 * 详情请看看guide
 * For more details and usage information on View, see the [guide article on views](guide:structure-views).
 *
 * $viewFile 当前正被渲染的视图文件，false表示当前没有视图文件被渲染，只读。
 * @property string|bool $viewFile The view file currently being rendered. False if no view file is being
 * rendered. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class View extends Component
{
    /**开始先是四个事件
     * @event Event an event that is triggered by [[beginPage()]].
     */
    const EVENT_BEGIN_PAGE = 'beginPage';
    /**
     * @event Event an event that is triggered by [[endPage()]].
     */
    const EVENT_END_PAGE = 'endPage';
    /**
     * @event ViewEvent an event that is triggered by [[renderFile()]] right before it renders a view file.
     */
    const EVENT_BEFORE_RENDER = 'beforeRender';
    /**
     * @event ViewEvent an event that is triggered by [[renderFile()]] right after it renders a view file.
     */
    const EVENT_AFTER_RENDER = 'afterRender';

    /**
	* 上下文对象，renderFile()被调用时，所处的上下文环境(一般视图是在控制器的方法里渲染，故上下文一般指控制器）
     * @var ViewContextInterface the context under which the [[renderFile()]] method is being invoked.
     */
    public $context;
    /**自定义参数，可以在好几个视图模板里共享
     * @var mixed custom parameters that are shared among view templates.
     */
    public $params = [];
    /**
	 * 数组，一系列的视图渲染器。由扩展名作为下标，对应的渲染器对象为元素值的数组。
     * @var array a list of available renderers indexed by their corresponding supported file extensions.
     * Each renderer may be a view renderer object or the configuration for creating the renderer object.
	 * 下面的例子支持Smarty和twig模板引擎
     * For example, the following configuration enables both Smarty and Twig view renderers:
     *
     * ```php
     * [
     *     'tpl' => ['class' => 'yii\smarty\ViewRenderer'],
     *     'twig' => ['class' => 'yii\twig\ViewRenderer'],
     * ]
     * ```
     *如果没有渲染器的话，那么视图文件将视为普通的php文件，转而使用readerPhpFile()方法去渲染。
     *默认的没有渲染器的，除非自定义扩展
     * If no renderer is available for the given view file, the view file will be treated as a normal PHP
     * and rendered via [[renderPhpFile()]].
     */
    public $renderers;
    /**
	* 默认的视图文件的扩展，如果渲染时没有给出视图文件的扩展名，那么就是默认的php，这也意味着无需模板引擎。
     * @var string the default view file extension. This will be appended to view file names if they don't have file extensions.
     */
    public $defaultExtension = 'php';
    /**
	* 主题，数组或字符串，表示主题对象或者主题对象的配置数组。主题就是一个目录，里面有好多视图，将来可以替换非主题的内容，
	* 从而保留主题的内容，有多个子目录就有多个主题
     * @var Theme|array|string the theme object or the configuration for creating the theme object.
	 * 没设置，则意味着未启用
     * If not set, it means theming is not enabled.
     */
    public $theme;
    /**
	* 数组，一系列命名的输出块。下标是块名，值就是块内容。
     * @var array a list of named output blocks. The keys are the block names and the values
	 * 可以通过beginBlock()和endBlock()来捕获视图文件里的小片段。捕获的小片段在视图文件的其他地方可以重复使用。
     * are the corresponding block content. You can call [[beginBlock()]] and [[endBlock()]]
     * to capture small fragments of a view. They can be later accessed somewhere else
     * through this property.
     */
    public $blocks;
    /**
	* 
	* 片段缓存功能应用在视图中，该属性用来保存活跃的widget。用于内部实现内容缓存特性的
     * @var array a list of currently active fragment cache widgets. This property
     * is used internally to implement the content caching feature. Do not modify it directly.
     * @internal
     */
    public $cacheStack = [];
    /**不太懂。
     * 用于嵌入动态内容的小洞，这是片段缓存实现嵌套特性的基础
     * @var array a list of placeholders for embedding dynamic contents. This property
     * 也是内部使用来实现内容缓存特性的
     * is used internally to implement the content caching feature. Do not modify it directly.
     * @internal
     */
    public $dynamicPlaceholders = [];

    /**当前正被渲染的视图文件，允许同时渲染多个视图，因为一个视图可以在另一个视图里被渲染
     * 故应该是一个索引数组
     * @var array the view files currently being rendered. There may be multiple view files being
     * rendered at a moment because one view may be rendered within another.
     */
    private $_viewFiles = [];


    /**
     * Initializes the view component.
     */
    public function init()
    {
        parent::init();
		//确定主题
        if (is_array($this->theme)) {
            if (!isset($this->theme['class'])) {
                $this->theme['class'] = 'yii\base\Theme';
            }
            $this->theme = Yii::createObject($this->theme);
        } elseif (is_string($this->theme)) {
            $this->theme = Yii::createObject($this->theme);
        }
    }

    /**
	* 渲染视图，这就是从控制器的方法里最后调用的那个render。只不过从控制器做了个转接到达这里而已
     * Renders a view.
     * 被渲染的视图，可以是以下情况之一
     * The view to be rendered can be specified in one of the following formats:
     * 1 路径别名,比如"@app/views/site/index"
     * - path alias (e.g. "@app/views/site/index");
	 * 2 该应用的视图目录的绝对路径。 视图名字以双斜线开始。比如"//site/index"。则表示  basic/views/site/index.php
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
	 * 实际的视图文件将从$app::viewPath()方法返回的应用主体的视图路径里寻找。
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
	 * 3 当前模块的绝对路径，比如"/site/index",这种以一个斜线开始。
     * - absolute path within current module (e.g. "/site/index"): the view name starts with a single slash.
	 *   实际的视图文件将从当前模块的视图路径中寻找 [[Module::viewPath|view path]] 和 [[Controller::module|current module]]两个方法确定
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of the [[Controller::module|current module]].
	    4 相对视图，比如只一个"index"。既不是以斜线开始，也不是以"@"号开始。
     * - relative view (e.g. "index"): the view name does not start with `@` or `/`. The corresponding view file will be
	 *   这种视图文件将从[[ViewContextInterface::getViewPath()|view path]]给出的路径里寻找
     *   looked for under the [[ViewContextInterface::getViewPath()|view path]] of the view `$context`.
	 *    如果$context上下文没有给出，则使用当前被渲染的其他视图的路径。这种情况一般发生在在一个视图里渲染另一个视图。
     *   If `$context` is not given, it will be looked for under the directory containing the view currently
     *   being rendered (i.e., this happens when rendering a view within another view).
     * 
	 * $view 视图名
     * @param string $view the view name.
	 * $params 传入视图的参数，键值对。将会在视图文件中析出（extract())函数，然后在视图文件中可以使用
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * $context  上下文对象。分配到视图中，在视图中通过context可以访问到，何为上下文？其实就是所属控制器而已。
     * @param object $context the context to be assigned to the view and can later be accessed via [[context]]
	 * 如果这个上下文实现[[ViewContextInterface]]接口，还可以根据视图名定位视图的路径
     * in the view. If the context implements [[ViewContextInterface]], it may also be used to locate
     * the view file corresponding to a relative view name.
     * @return string the rendering result
	 * 视图未找到的异常
     * @throws ViewNotFoundException if the view file does not exist.
	 * 无效调用，当视图不可解析时报异常
     * @throws InvalidCallException if the view cannot be resolved.
     * @see renderFile() 详情参考renderFile()
     */
    public function render($view, $params = [], $context = null)
    {
		//根据上下文参数（第二个参数）找到视图文件，无需传递参数$params，因为这里还不需要渲染呢，这里是根据视图文件名，在文件系统里找到视图文件而已
        //一般就是在视图目录里，和当前控制器名字有关的子目录里。
        //返回的例子可以是:D:\wamp64\www\basic\views\site\index.php
        $viewFile = $this->findViewFile($view, $context);
		//找到视图文件的绝对路径后，到这个方法里去渲染
        return $this->renderFile($viewFile, $params, $context);
    }

    /**
	 * 根据视图文件名，找到视图文件的绝对路径。
     * Finds the view file based on the given view name.
	 * 
	 * $view 视图名 或者视图文件的路径别名。请参考[[render()]]方法学习如何设置这个参数
     * @param string $view the view name or the path alias of the view file. Please refer to [[render()]]
     * on how to specify this parameter.
	 * 
     * @param object $context the context to be assigned to the view and can later be accessed via [[context]]
     * in the view. If the context implements [[ViewContextInterface]], it may also be used to locate
     * the view file corresponding to a relative view name.
	 * 
	 * 视图文件的路径，也许这个文件根本就不存在。
     * @return string the view file path. Note that the file may not exist.   仅仅返回字符串，并不检验是否在文件系统里存在。
     * @throws InvalidCallException if a relative view name is given while there is no active context to
     * determine the corresponding view file.
     */
    protected function findViewFile($view, $context = null)
    {
		//别名形式的，就去解析别名，获得文件系统绝对路径
        if (strncmp($view, '@', 1) === 0) {
            // e.g. "@app/views/main"
            $file = Yii::getAlias($view);
		//两斜杠开始的，就从mvc的视图目录，
        } elseif (strncmp($view, '//', 2) === 0) {
            // e.g. "//layouts/main"
            $file = Yii::$app->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
		//单斜杠开始的视图名
        } elseif (strncmp($view, '/', 1) === 0) {
            // e.g. "/site/index"
			//当前控制器的模块所在视图目录
            if (Yii::$app->controller !== null) {
                $file = Yii::$app->controller->module->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
            } else {
                throw new InvalidCallException("Unable to locate view file for view '$view': no active controller.");
            }
		//$view都不符合上述三种情况，则根据$context上下文对象取得视图目录，这也是最常用的情况，大部分都是从视图总目录的控制器子目录下取得视图
            //返回结果的例子是不带后缀的：  D:\wamp64\www\basic\views\site\index
        } elseif ($context instanceof ViewContextInterface) {
            $file = $context->getViewPath() . DIRECTORY_SEPARATOR . $view;
		//再或者，根据getViewFile方法返回的路径决定
        } elseif (($currentViewFile = $this->getViewFile()) !== false) {
            $file = dirname($currentViewFile) . DIRECTORY_SEPARATOR . $view;
        } else {
            throw new InvalidCallException("Unable to resolve view file for view '$view': no active view context.");
        }
        //有扩展名就直接返回（这也说明了，我们完全可以在控制器的操作方法里的最后阶段返回视图时，视图文件的参数，完全可以自带扩展名
        //还能提高一点性能呢
        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        //没有扩展名的，就加上默认的扩展，由defaultExtension成员指定。例如："D:\wamp64\www\basic\views\site\index"+".php"
        $path = $file . '.' . $this->defaultExtension;
        //如果defaultExtension成员不是php(比如是txt)，文件也不对，那就再加上php。有可能是"D:\wamp64\www\basic\views\site\index.txt.php"这种情况。
        if ($this->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }
		//终于可以返回了。
        return $path;
    }

    /**
	 * 
	 * 渲染一个视图文件(如果从action里调用，多数是渲染普通视图；从控制器里直接调用，多数就是布局视图）
     * Renders a view file.
     *
	 * 如果主题开启（非null),那么尽可能渲染视图的主题版本。
     * If [[theme]] is enabled (not null), it will try to render the themed version of the view file as long
     * as it is available.
     *
	 * 该方法会调用localize来本地化视图文件，什么叫本地化？有些视图难道是远程的？
     * The method will call [[FileHelper::localize()]] to localize the view file.
     *
	 * 如果渲染器可用，那么使用渲染器渲染视图文件
     * If [[renderers|renderer]] is enabled (not null), the method will use it to render the view file.
	 * 不然，就当做正常的php文件，require过来，捕获视图文件的输出然后以字符串的形式返回。
     * Otherwise, it will simply include the view file as a normal PHP file, capture its output and
     * return it as a string.
     *
	 * $viewFile  视图文件名，可用是一个绝对路径也可以是一个别名
     * @param string $viewFile the view file. This can be either an absolute file path or an alias of it.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @param object $context the context that the view should use for rendering the view. If null,
     * existing [[context]] will be used.
     * @return string the rendering result
     * @throws ViewNotFoundException if the view file does not exist
	 * 从入栈视图文件，保存上下文来看，该方法有可能会递归调用，因为
	 * 在渲染视图时，有可能又碰到子视图再去渲染。Yii框架类似地问题，应该有很多，比如打日志
	 * 都是使用这种入栈保存环境，出栈恢复环境的方法实现的。
	 * 这个思想，就跟计算机底层的中断是一个思想吧？
     */
    public function renderFile($viewFile, $params = [], $context = null)
    {
        //还有可能是带有别名的字符串吗？
        $viewFile = Yii::getAlias($viewFile);
		
		//有主题就应用主题
        if ($this->theme !== null) {
            $viewFile = $this->theme->applyTo($viewFile);
        }
        if (is_file($viewFile)) {
            //本地化，是相对于国际化的一个概念，同一个视图，在中国是zh-cn目录下,在美国就是en-US目录下
			//basic项目默认没有国际化的处理，肯定保持原样
            $viewFile = FileHelper::localize($viewFile);
        } else {
            throw new ViewNotFoundException("The view file does not exist: $viewFile");
        }
		//保存旧的上下文，因为有可能会递归，第一次进来时，上下文是空的null
        $oldContext = $this->context;
		//使用本次参数传递的上下文对象
        if ($context !== null) {
            $this->context = $context;
        }
        $output = '';//初始化保存渲染后内容字符串的变量
		//在渲染之前，把当前视图入栈保存，因为在渲染一个视图的时候，有可能再次渲染其他的视图
		//可以用栈数据结构实现，依次递归渲染
        $this->_viewFiles[] = $viewFile;
		//触发一个【渲染前】事件
        if ($this->beforeRender($viewFile, $params)) {
            Yii::trace("Rendering view file: $viewFile", __METHOD__);
			//获得视图文件的扩展名
            $ext = pathinfo($viewFile, PATHINFO_EXTENSION);
			//是否有对应扩展名的渲染器【模板引擎】，因为每个渲染器都有自己独特的视图文件的扩展名
            if (isset($this->renderers[$ext])) {
                if (is_array($this->renderers[$ext]) || is_string($this->renderers[$ext])) {
					//用Yii全局助手类，依赖实例化渲染引擎对象
                    $this->renderers[$ext] = Yii::createObject($this->renderers[$ext]);
                }
                /* @var $renderer ViewRenderer */
                $renderer = $this->renderers[$ext];
				//调用渲染引擎的render方法进行渲染
                $output = $renderer->render($this, $viewFile, $params);
            } else {
                //目前都是没有使用渲染器的，直接走这个方法，获得渲染后的字符串结果,
                //这个渲染结果都是来自这个视图文件的，因为每渲染一个视图，都会单独打开一个输出缓冲区
                $output = $this->renderPhpFile($viewFile, $params);
            }
			//触发一个【渲染后】事件
            $this->afterRender($viewFile, $params, $output);
        }
		//渲染一个视图后，出栈。但是并没有接收，所以实际是删除了视图数组的最后一个元素。
        array_pop($this->_viewFiles);
		//恢复上下文，如果没有递归过，那就是直接赋null呗
        $this->context = $oldContext;

        return $output;
    }

    /**
	 * 返回当前正被渲染的视图，注意，使用end方法，返回数组的最后一个单元
	 * 估计是考虑了视图递归渲染时，可能存在多个子视图的情况吧。渲染视图家族树？
     * @return string|bool the view file currently being rendered. False if no view file is being rendered.
     */
    public function getViewFile()
    {
        return end($this->_viewFiles);
    }

    /**
     * 执行beforeRender事件。事件对象是继承自Event基类的ViewEvent
     * This method is invoked right before [[renderFile()]] renders a view file.
     * The default implementation will trigger the [[EVENT_BEFORE_RENDER]] event.
     * If you override this method, make sure you call the parent implementation first.
     * @param string $viewFile the view file to be rendered.
     * @param array $params the parameter array passed to the [[render()]] method.
     * @return bool whether to continue rendering the view file.
     */
    public function beforeRender($viewFile, $params)
    {
		//为视图对象，单独继承Event对象，写了视图对象的事件类，Yii框架有很多这样的做法
		//各个组件的事件，都是继承自Event基类，各自命名写自己的事件。因为各个组件的特性不同。
        $event = new ViewEvent([
            'viewFile' => $viewFile,
            'params' => $params,
        ]);//默认没有绑定事件处理器
        $this->trigger(self::EVENT_BEFORE_RENDER, $event);

        //事件对象的isValid属性默认都是true,故即使没有事件处理器，也是可以正常往后进行下去
        return $event->isValid;
    }

    /**
	 * 该方法在【renderFile】方法的最后阶段调用的
     * This method is invoked right after [[renderFile()]] renders a view file.
	 * 默认的实现，当然就是触发事件
     * The default implementation will trigger the [[EVENT_AFTER_RENDER]] event.
	 * 如果要覆盖这个方法，请确保首先调用父类方法的实现
     * If you override this method, make sure you call the parent implementation first.
     * @param string $viewFile the view file being rendered.   被渲染的视图文件
     * @param array $params the parameter array passed to the [[render()]] method. 传递到视图的参数
     * @param string $output the rendering result of the view file. Updates to this parameter 渲染后的结果
     * will be passed back and returned by [[renderFile()]].
	 * 注意是引用传递，故这也是渲染后，最后调整视图内容的机会
     */
    public function afterRender($viewFile, $params, &$output)
    {
		//何时绑定的？是不是默认没有绑定呢？afterRender方法的触发方法非同一般，是直接判断是否有事件处理者，进而再去触发事件；
		//而我们平时看到的，都是直接去触发（trigger,在trigger中使用行为方式绑定事件处理器）。为什么要这样呢？这里为啥hasEventHandler呢？
        if ($this->hasEventHandlers(self::EVENT_AFTER_RENDER)) {
            $event = new ViewEvent([
                'viewFile' => $viewFile,
                'params' => $params,
				//传递了渲染后的结果到事件处理器中
                'output' => $output,
            ]);
            $this->trigger(self::EVENT_AFTER_RENDER, $event);
            $output = $event->output;
        }
    }

    /**
     * 把视图看做是php脚本来渲染。该方法由【renderFile】里调用，是【模板引擎】和【php脚本模板】二选一
	 * 的结果
     * Renders a view file as a PHP script.
     *
     *该方法把视图文件看做php脚本。包含进来这个文件，析出参数，
     * This method treats the view file as a PHP script and includes the file.
     * It extracts the given parameters and makes them available in the view file.
     * 捕获包含进来的视图文件的输出，作为字符串返回。
     * The method captures the output of the included view file and returns it as a string.
     *
     * This method should mainly be called by view renderer or [[renderFile()]].
     *
     * @param string $_file_ the view file.  视图文件（普通视图文件，和布局视图文件都会由这个方法来渲染）
     * 但是参数明显不同，普通视图是从控制器方法里手写传递而来；而布局视图文件的参数，却正好是普通视图文件渲染而来的$content。
     * @param array $_params_ the parameters (name-value pairs) that will be extracted and made available in the view file.析出的参数
     * @return string the rendering result  返回渲染后的结果
     */
    public function renderPhpFile($_file_, $_params_ = [])
    {
        //开启一个输出缓冲
        ob_start();
        //关闭绝对输出选项
        ob_implicit_flush(false);
        //析出关联数组的下标为变量，覆盖已有的变量符号表。
        extract($_params_, EXTR_OVERWRITE);
		//包含进来，自动执行php语句和解析php变量。
		//所以对于变量来说，仅仅是个php的解析，并没有模板引擎的替换，语法等。
		//因为模板本身就是php文件嘛
        require($_file_);

        //取得所有输出缓存区的内容，然后清空并关闭输出缓存阀门
		//注意，ob_get_clean只是获得本次开启之后有关输出的内容，并不是以后全部的，因为输出缓冲也是栈机制管理
        return ob_get_clean();
    }

    /**
	 * 渲染由php语句返回的动态内容
     * Renders dynamic content returned by the given PHP statements.
	 * 该方法主要和内容缓存【片段缓存，页面缓存】一块使用，用于渲染其中没有缓存的部分，需了解缓存机制才能明白这个方法
     * This method is mainly used together with content caching (fragment caching and page caching)
     * when some portions of the content (called *dynamic content*) should not be cached.
	 * 所谓动态内容，其实就是一些php语句
     * The dynamic content must be returned by some PHP statements.
	 * $statements  就是生成动态内容的php语句
     * @param string $statements the PHP statements for generating the dynamic content.
     * @return string the placeholder of the dynamic content, or the dynamic content if there is no
     * active content cache currently.
     */
    public function renderDynamic($statements)
    {
        if (!empty($this->cacheStack)) {
            $n = count($this->dynamicPlaceholders);
            $placeholder = "<![CDATA[YII-DYNAMIC-$n]]>";
            $this->addDynamicPlaceholder($placeholder, $statements);

            return $placeholder;
        }
        return $this->evaluateDynamicContent($statements);
    }

    /**
	 * 与上一个方法是紧密相连的，这里是添加小洞
     * Adds a placeholder for dynamic content.
     * This method is internally used.内容调用
     * @param string $placeholder the placeholder name 小洞的名字
	 * $statements  生成动态内容的php语句
     * @param string $statements the PHP statements for generating the dynamic content
     */
    public function addDynamicPlaceholder($placeholder, $statements)
    {
		//遍历缓存栈，为每个小洞添加php语句
        foreach ($this->cacheStack as $cache) {
            $cache->dynamicPlaceholders[$placeholder] = $statements;
        }
        $this->dynamicPlaceholders[$placeholder] = $statements;
    }

    /**
	* 用php原生函数eval来执行php语句（官网说很危险），是把字符串当做php代码执行的函数
     * Evaluates the given PHP statements.
	 * 在Yii框架中，主要在内部使用来实现动态内容的一些特性
     * This method is mainly used internally to implement dynamic content feature.
     * @param string $statements the PHP statements to be evaluated.
     * @return mixed the return value of the PHP statements.
     */
    public function evaluateDynamicContent($statements)
    {
        return eval($statements);
    }

    /**
	 * 开始记录一个块 
     * Begins recording a block.
     * This method is a shortcut to beginning [[Block]]
     * @param string $id the block ID.
     * @param bool $renderInPlace whether to render the block content in place.
     * Defaults to false, meaning the captured block will not be displayed.
     * @return Block the Block widget instance
     */
    public function beginBlock($id, $renderInPlace = false)
    {
		//转而使用其他类来完成，View组件可以利用之实现一些功能而已。
        return Block::begin([
            'id' => $id,
            'renderInPlace' => $renderInPlace,
            'view' => $this,
        ]);
    }

    /**
	* 同样使用Block来终止一段记录
	* 具体Block是什么，稍后再说
     * Ends recording a block.
     */
    public function endBlock()
    {
        Block::end();
    }

    /**
	 * 开始内容的解析,该内容由指定视图装饰器装饰而来。
     * Begins the rendering of content that is to be decorated by the specified view.
	 * 该方法用来实现嵌套的布局视图，比如一个布局视图嵌套在另一个视图文件，比如：
     * This method can be used to implement nested layout. For example, a layout can be embedded
     * in another layout file specified as '@app/views/layouts/base.php' like the following:
     *
     * ```php
     * <?php $this->beginContent('@app/views/layouts/base.php'); ?>
     * //...layout content here...
     * <?php $this->endContent(); ?>
     * ```
     * $viewFile视图文件，用来装饰widget包含的内容
     * @param string $viewFile the view file that will be used to decorate the content enclosed by this widget.
	 * 可以是视图文件路径也可以是别名
     * This can be specified as either the view file path or path alias.
     * @param array $params the variables (name => value) to be extracted and made available in the decorative view.
     * @return ContentDecorator the ContentDecorator widget instance
     * @see ContentDecorator
     */
    public function beginContent($viewFile, $params = [])
    {
		//内容装饰器，稍后再说
        return ContentDecorator::begin([
            'viewFile' => $viewFile,
            'params' => $params,
            'view' => $this,
        ]);
    }

    /**
	* 结束内容装饰器
     * Ends the rendering of content.
     */
    public function endContent()
    {
        ContentDecorator::end();
    }

    /**
	* 开始一段片段缓存
     * Begins fragment caching.
	 * 如果可以的话，该方法将展示曾经缓存的内容
     * This method will display cached content if it is available.
	 * 如果还没有缓存，那么就是开启片段缓存，直到遇到endCache来标记要缓存的片段范围，并缓存之
     * If not, it will start caching and would expect an [[endCache()]]
     * call to end the cache and save the content into cache.
	 * 典型的片段缓存的使用如下：
     * A typical usage of fragment caching is as follows,
     *
     * ```php
     * if ($this->beginCache($id)) {
     *     // ...generate content here
     *     $this->endCache();
     * }
     * ```
     *  $id 片段缓存唯一标识符
     * @param string $id a unique ID identifying the fragment to be cached.
	 * $properties  初始化【FragmentCache】缓存对象的属性  
     * @param array $properties initial property values for [[FragmentCache]]
	 * 返回布尔值，用来表示是否生成缓存的内容
     * @return bool whether you should generate the content for caching.
 	 * 返回false，表示曾经的缓存可用
     * False if the cached version is available.
     */
    public function beginCache($id, $properties = [])
    {
        $properties['id'] = $id;
		//把视图组件传递到片段缓存对象中
        $properties['view'] = $this;
        /* @var $cache FragmentCache 从代码看，begin，end这些方法，明显是小部件widget的使用习惯，所以可以看出FragmentCache属于小部件的子类*/
        $cache = FragmentCache::begin($properties);
		//如果有曾经缓存的内容，则返回数据。这表示至少是第二次调用了；
		//而如果是第一次调用beginCache，肯定还没有缓存内容，故直接return true，等待endCache来确定片段缓存的页面范围。
        if ($cache->getCachedContent() !== false) {
            $this->endCache();

            return false;
        }
        return true;
    }

    /**
	* 结束一段片段缓存
     * Ends fragment caching.
     */
    public function endCache()
    {
        FragmentCache::end();
    }

    /**
     * 标记一个页面的开始，不是缓存功能，但也能干缓存的事。
     * 一般在整个页面的起始位置（因为页面被视图文件拆分，经检查该代码在layouts/html.php的起始位置）
     * Marks the beginning of a page.
     */
    public function beginPage()
    {
        //开启一个输出缓存阀门（与其他ob_start互不干扰，各个ob_start是栈数据结构，不是队列数据结构）
        //这样就解释了，为什么一般在action中使用echo,var_dump什么的，都不会立即输出，而是程序结束才会输出。
        //不!,不是这样的，应该还有其他的缓存机制控制render方法调用之前，使得echo，var_dump这样的输出会缓存
        ob_start();
        ob_implicit_flush(false);

        $this->trigger(self::EVENT_BEGIN_PAGE);
    }

    /**
     * 标记一个页面的结束。
     * 一般在整个页面的结束位置（因为页面被视图文件拆分，经检查该代码在layouts/html.php的结束位置）
     * Marks the ending of a page.
     */
    public function endPage()
    {
        $this->trigger(self::EVENT_END_PAGE);
        //输出缓存区的内容，并关闭本次缓存
        ob_end_flush();
    }
}
