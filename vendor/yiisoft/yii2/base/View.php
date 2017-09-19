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
	* 上下文对象，renderFile()被调用时，所处的上下文环境
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
     * If no renderer is available for the given view file, the view file will be treated as a normal PHP
     * and rendered via [[renderPhpFile()]].
     */
    public $renderers;
    /**
	* 默认的视图文件的扩展，如果渲染时没有给出视图文件的扩展名，那么就是默认的php。
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
	* 不太懂。
     * @var array a list of currently active fragment cache widgets. This property
     * is used internally to implement the content caching feature. Do not modify it directly.
     * @internal
     */
    public $cacheStack = [];
    /**不太懂。
     * @var array a list of placeholders for embedding dynamic contents. This property
     * is used internally to implement the content caching feature. Do not modify it directly.
     * @internal
     */
    public $dynamicPlaceholders = [];

    /**当前正被渲染的视图文件，允许同时渲染多个视图，因为一个视图可以在另一个视图里被渲染
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
	* 渲染视图
     * Renders a view.
     * 被渲染的视图，可以是以下情况之一
     * The view to be rendered can be specified in one of the following formats:
     * 1 路径别名,比如"@app/views/site/index"
     * - path alias (e.g. "@app/views/site/index");
	 * 2 该应用的视图目录的绝对路径。 视图名字以双斜线开始。比如"//site/index"。则表示  basic/views/site/index.php
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
	 * 实际的视图文件将从$app::viewPath()方法返回的应用视图路径里寻找。
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
	 * 3 当前模块的绝对路径，比如"/site/index",这种以一个斜线开始。
     * - absolute path within current module (e.g. "/site/index"): the view name starts with a single slash.
	 *   实际的视图文件将从当前模块的视图路径中寻找 [[Module::viewPath|view path]] 和 [[Controller::module|current module]]两个方法确定
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of the [[Controller::module|current module]].
	    4 相对视图，比如只一个"index"。既不是以斜线开始，也不是以"@"号开始。
     * - relative view (e.g. "index"): the view name does not start with `@` or `/`. The corresponding view file will be
	 *   这种视图文件将从[[ViewContextInterface::getViewPath()|view path]]给出的路径里寻找
     *   looked for under the [[ViewContextInterface::getViewPath()|view path]] of the view `$context`.
	 *    如果$context上下文没有给出，则使用当前被渲染的其他视图的路径。这种情况一般发生在在另一个视图里渲染又一个视图。
     *   If `$context` is not given, it will be looked for under the directory containing the view currently
     *   being rendered (i.e., this happens when rendering a view within another view).
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @param object $context the context to be assigned to the view and can later be accessed via [[context]]
     * in the view. If the context implements [[ViewContextInterface]], it may also be used to locate
     * the view file corresponding to a relative view name.
     * @return string the rendering result
     * @throws ViewNotFoundException if the view file does not exist.
     * @throws InvalidCallException if the view cannot be resolved.
     * @see renderFile()
     */
    public function render($view, $params = [], $context = null)
    {
		//根据上下文参数（第二个参数）找到视图文件
        $viewFile = $this->findViewFile($view, $context);
		//绝对路径到这个方法里去渲染
        return $this->renderFile($viewFile, $params, $context);
    }

    /**
     * Finds the view file based on the given view name.
     * @param string $view the view name or the path alias of the view file. Please refer to [[render()]]
     * on how to specify this parameter.
     * @param object $context the context to be assigned to the view and can later be accessed via [[context]]
     * in the view. If the context implements [[ViewContextInterface]], it may also be used to locate
     * the view file corresponding to a relative view name.
     * @return string the view file path. Note that the file may not exist.
     * @throws InvalidCallException if a relative view name is given while there is no active context to
     * determine the corresponding view file.
     */
    protected function findViewFile($view, $context = null)
    {
        if (strncmp($view, '@', 1) === 0) {
            // e.g. "@app/views/main"
            $file = Yii::getAlias($view);
        } elseif (strncmp($view, '//', 2) === 0) {
            // e.g. "//layouts/main"
            $file = Yii::$app->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
        } elseif (strncmp($view, '/', 1) === 0) {
            // e.g. "/site/index"
            if (Yii::$app->controller !== null) {
                $file = Yii::$app->controller->module->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
            } else {
                throw new InvalidCallException("Unable to locate view file for view '$view': no active controller.");
            }
        } elseif ($context instanceof ViewContextInterface) {
            $file = $context->getViewPath() . DIRECTORY_SEPARATOR . $view;
        } elseif (($currentViewFile = $this->getViewFile()) !== false) {
            $file = dirname($currentViewFile) . DIRECTORY_SEPARATOR . $view;
        } else {
            throw new InvalidCallException("Unable to resolve view file for view '$view': no active view context.");
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        $path = $file . '.' . $this->defaultExtension;
        if ($this->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
    }

    /**
     * Renders a view file.
     *
     * If [[theme]] is enabled (not null), it will try to render the themed version of the view file as long
     * as it is available.
     *
     * The method will call [[FileHelper::localize()]] to localize the view file.
     *
     * If [[renderers|renderer]] is enabled (not null), the method will use it to render the view file.
     * Otherwise, it will simply include the view file as a normal PHP file, capture its output and
     * return it as a string.
     *
     * @param string $viewFile the view file. This can be either an absolute file path or an alias of it.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @param object $context the context that the view should use for rendering the view. If null,
     * existing [[context]] will be used.
     * @return string the rendering result
     * @throws ViewNotFoundException if the view file does not exist
     */
    public function renderFile($viewFile, $params = [], $context = null)
    {
        $viewFile = Yii::getAlias($viewFile);

        if ($this->theme !== null) {
            $viewFile = $this->theme->applyTo($viewFile);
        }
        if (is_file($viewFile)) {
            $viewFile = FileHelper::localize($viewFile);
        } else {
            throw new ViewNotFoundException("The view file does not exist: $viewFile");
        }

        $oldContext = $this->context;
        if ($context !== null) {
            $this->context = $context;
        }
        $output = '';
        $this->_viewFiles[] = $viewFile;

        if ($this->beforeRender($viewFile, $params)) {
            Yii::trace("Rendering view file: $viewFile", __METHOD__);
            $ext = pathinfo($viewFile, PATHINFO_EXTENSION);
            if (isset($this->renderers[$ext])) {
                if (is_array($this->renderers[$ext]) || is_string($this->renderers[$ext])) {
                    $this->renderers[$ext] = Yii::createObject($this->renderers[$ext]);
                }
                /* @var $renderer ViewRenderer */
                $renderer = $this->renderers[$ext];
                $output = $renderer->render($this, $viewFile, $params);
            } else {
                $output = $this->renderPhpFile($viewFile, $params);
            }
            $this->afterRender($viewFile, $params, $output);
        }

        array_pop($this->_viewFiles);
        $this->context = $oldContext;

        return $output;
    }

    /**
     * @return string|bool the view file currently being rendered. False if no view file is being rendered.
     */
    public function getViewFile()
    {
        return end($this->_viewFiles);
    }

    /**
     * This method is invoked right before [[renderFile()]] renders a view file.
     * The default implementation will trigger the [[EVENT_BEFORE_RENDER]] event.
     * If you override this method, make sure you call the parent implementation first.
     * @param string $viewFile the view file to be rendered.
     * @param array $params the parameter array passed to the [[render()]] method.
     * @return bool whether to continue rendering the view file.
     */
    public function beforeRender($viewFile, $params)
    {
        $event = new ViewEvent([
            'viewFile' => $viewFile,
            'params' => $params,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked right after [[renderFile()]] renders a view file.
     * The default implementation will trigger the [[EVENT_AFTER_RENDER]] event.
     * If you override this method, make sure you call the parent implementation first.
     * @param string $viewFile the view file being rendered.
     * @param array $params the parameter array passed to the [[render()]] method.
     * @param string $output the rendering result of the view file. Updates to this parameter
     * will be passed back and returned by [[renderFile()]].
     */
    public function afterRender($viewFile, $params, &$output)
    {
        if ($this->hasEventHandlers(self::EVENT_AFTER_RENDER)) {
            $event = new ViewEvent([
                'viewFile' => $viewFile,
                'params' => $params,
                'output' => $output,
            ]);
            $this->trigger(self::EVENT_AFTER_RENDER, $event);
            $output = $event->output;
        }
    }

    /**
     * Renders a view file as a PHP script.
     *
     * This method treats the view file as a PHP script and includes the file.
     * It extracts the given parameters and makes them available in the view file.
     * The method captures the output of the included view file and returns it as a string.
     *
     * This method should mainly be called by view renderer or [[renderFile()]].
     *
     * @param string $_file_ the view file.
     * @param array $_params_ the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @return string the rendering result
     */
    public function renderPhpFile($_file_, $_params_ = [])
    {
        //开启输出缓存阀门
        ob_start();
        //关闭绝对输出选项
        ob_implicit_flush(false);
        //析出关联数组的下标为变量，覆盖已有的变量符号表
        extract($_params_, EXTR_OVERWRITE);
        require($_file_);

        //取得所有输出缓存区的内容，然后清空并关闭输出缓存阀门
        return ob_get_clean();
    }

    /**
     * Renders dynamic content returned by the given PHP statements.
     * This method is mainly used together with content caching (fragment caching and page caching)
     * when some portions of the content (called *dynamic content*) should not be cached.
     * The dynamic content must be returned by some PHP statements.
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
     * Adds a placeholder for dynamic content.
     * This method is internally used.
     * @param string $placeholder the placeholder name
     * @param string $statements the PHP statements for generating the dynamic content
     */
    public function addDynamicPlaceholder($placeholder, $statements)
    {
        foreach ($this->cacheStack as $cache) {
            $cache->dynamicPlaceholders[$placeholder] = $statements;
        }
        $this->dynamicPlaceholders[$placeholder] = $statements;
    }

    /**
     * Evaluates the given PHP statements.
     * This method is mainly used internally to implement dynamic content feature.
     * @param string $statements the PHP statements to be evaluated.
     * @return mixed the return value of the PHP statements.
     */
    public function evaluateDynamicContent($statements)
    {
        return eval($statements);
    }

    /**
     * Begins recording a block.
     * This method is a shortcut to beginning [[Block]]
     * @param string $id the block ID.
     * @param bool $renderInPlace whether to render the block content in place.
     * Defaults to false, meaning the captured block will not be displayed.
     * @return Block the Block widget instance
     */
    public function beginBlock($id, $renderInPlace = false)
    {
        return Block::begin([
            'id' => $id,
            'renderInPlace' => $renderInPlace,
            'view' => $this,
        ]);
    }

    /**
     * Ends recording a block.
     */
    public function endBlock()
    {
        Block::end();
    }

    /**
     * Begins the rendering of content that is to be decorated by the specified view.
     * This method can be used to implement nested layout. For example, a layout can be embedded
     * in another layout file specified as '@app/views/layouts/base.php' like the following:
     *
     * ```php
     * <?php $this->beginContent('@app/views/layouts/base.php'); ?>
     * //...layout content here...
     * <?php $this->endContent(); ?>
     * ```
     *
     * @param string $viewFile the view file that will be used to decorate the content enclosed by this widget.
     * This can be specified as either the view file path or path alias.
     * @param array $params the variables (name => value) to be extracted and made available in the decorative view.
     * @return ContentDecorator the ContentDecorator widget instance
     * @see ContentDecorator
     */
    public function beginContent($viewFile, $params = [])
    {
        return ContentDecorator::begin([
            'viewFile' => $viewFile,
            'params' => $params,
            'view' => $this,
        ]);
    }

    /**
     * Ends the rendering of content.
     */
    public function endContent()
    {
        ContentDecorator::end();
    }

    /**
     * Begins fragment caching.
     * This method will display cached content if it is available.
     * If not, it will start caching and would expect an [[endCache()]]
     * call to end the cache and save the content into cache.
     * A typical usage of fragment caching is as follows,
     *
     * ```php
     * if ($this->beginCache($id)) {
     *     // ...generate content here
     *     $this->endCache();
     * }
     * ```
     *
     * @param string $id a unique ID identifying the fragment to be cached.
     * @param array $properties initial property values for [[FragmentCache]]
     * @return bool whether you should generate the content for caching.
     * False if the cached version is available.
     */
    public function beginCache($id, $properties = [])
    {
        $properties['id'] = $id;
        $properties['view'] = $this;
        /* @var $cache FragmentCache */
        $cache = FragmentCache::begin($properties);
        if ($cache->getCachedContent() !== false) {
            $this->endCache();

            return false;
        }
        return true;
    }

    /**
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
