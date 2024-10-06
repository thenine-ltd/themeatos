<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 19/04/2022
 * Time: 08:33
 *
 * @since 1.12.0
 */

namespace WPCCrawler\Objects;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Fluent;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\View;
use Throwable;
use WPCCrawler\Objects\Informing\Informer;

/**
 * This class is a mediator between Illuminate's View component and the app. This creates a {@link Factory} that can be
 * used to compile Blade templates into HTML. Simply, use {@link view()} method to retrieve the factory. Then, call its
 * {@link Factory::make()} method with the relative path of the Blade view to render it.
 *
 * @since 1.12.0
 */
class Blade {

    /** @var string[] Array containing paths where to look for Blade files */
    public $viewPaths;

    /** @var string Location where to store cached views */
    public $cachePath;

    /** @var Container */
    protected $container;

    /** @var Factory */
    protected $instance;

    /**
     * @param string[] $viewPaths The paths to the directories that store the Blade templates
     * @param string   $cachePath The path to a directory that will be used to store the PHP files created from the
     *                            Blade templates. In other words, the directory will be used to cache files.
     */
    function __construct(array $viewPaths, string $cachePath) {
        $this->viewPaths = $viewPaths;
        $this->cachePath = $cachePath;

        $this->container = new Container();
        $this->instance = $this->createFactory();
    }

    /**
     * @return Factory The view factory that can be used to compile Blade templates into HTML
     * @since 1.12.0
     */
    public function view(): Factory {
        return $this->instance;
    }

    /**
     * Renders a Blade view defined as a string
     *
     * @param string $string           The contents of a Blade template
     * @param array  $data             The data to be provided to the view, to resolve the variables used in the view
     * @param bool   $deleteCachedView `true` if the cached view should be deleted after it is rendered.
     * @return string
     * @since 1.14.0
     */
    public function render(string $string, array $data = [], bool $deleteCachedView = true): string {
        // Get the current instance of the container and then set the instance as the container created in this Blade
        // instance, so that the classes registered to the container can be retrieved when needed. If this is not done,
        // reflection errors occur.
        $prevContainerInstance = Container::getInstance();
        Container::setInstance($this->container);

        // The following logic is retrieved from BladeCompiler::render() method of Laravel
        $component = new class($string) extends Component {
            /** @var string */
            protected $template;

            public function __construct(string $template) {
                $this->template = $template;
            }

            public function render(): string {
                return $this->template;
            }
        };

        // Resolve the view through the component
        $resolvedView = $component->resolveView();
        if (!is_string($resolvedView)) {
            return '';
        }

        /** @var View $view */
        $view = $this->instance->make($resolvedView, $data);

        try {
            $result = tap($view->render(), function () use ($view, $deleteCachedView, $prevContainerInstance) {
                if ($deleteCachedView) {
                    unlink($view->getPath());
                }

                // Restore the previous container instance just in case
                Container::setInstance($prevContainerInstance);
            });

        } catch (Throwable $e) {
            Informer::addInfo(_wpcc('View template could not be rendered.'))
                ->setException($e)
                ->addAsLog();
            return '';
        }

        return is_string($result)
            ? $result
            : '';
    }

    /*
     * HELPERS
     */

    /**
     * @return Factory The view factory that can be used to compile Blade templates into HTML
     * @since 1.12.0
     * @see https://github.com/mattstauffer/Torch/tree/master/components/view
     */
    protected function createFactory(): Factory {
        $fs = new Filesystem();
        $dispatcher = new Dispatcher($this->container);

        // Create a view factory that is capable of rendering PHP and Blade templates
        $viewResolver  = new EngineResolver();
        $bladeCompiler = new BladeCompiler($fs, $this->cachePath);

        $viewResolver->register('blade', function() use ($bladeCompiler) {
            return new CompilerEngine($bladeCompiler);
        });

        $viewFinder = new FileViewFinder($fs, $this->viewPaths);
        $viewFactory = new Factory($viewResolver, $viewFinder, $dispatcher);
        $viewFactory->setContainer($this->container);
        $this->container->instance(\Illuminate\Contracts\View\Factory::class, $viewFactory);
        $this->container->instance(BladeCompiler::class, $bladeCompiler);
        $this->container->singleton('view', function() use ($viewFactory) {
            return $viewFactory;
        });

        $this->container->singleton('config', function() {
            $config = new Fluent();
            $config['view.compiled'] = $this->cachePath;

            return $config;
        });

        return $viewFactory;
    }

}