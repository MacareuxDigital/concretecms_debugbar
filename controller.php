<?php

namespace Concrete\Package\ConcretecmsDebugbar;

use Concrete\Core\Package\Package;
use Concrete\Core\Permission\Key\Key;
use ConcreteDebugbar\Debugbar;
use DebugBar\DebugBarException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Controller extends Package
{
    const PLACEHOLDER_TEXT = '<!-- debugbar:placeholder -->';
    protected $pkgHandle = 'concretecms_debugbar';
    protected $appVersionRequired = '9.0.0';
    protected $pkgVersion = '1.0.0';
    protected $pkgAutoloaderRegistries = [
        'src/ConcreteDebugbar' => '\ConcreteDebugbar',
    ];

    /**
     * Returns the translated name of the package.
     *
     * @return string
     */
    public function getPackageName()
    {
        return t('PHP Debug Bar for Concrete CMS');
    }

    /**
     * Returns the translated package description.
     *
     * @return string
     */
    public function getPackageDescription()
    {
        return t('Displays a debug bar to display profiling data like database queries, memory usage, etc.');
    }

    /**
     * Install process of the package.
     */
    public function install()
    {
        $this->registerAutoload();

        if (!class_exists('DebugBar\DebugBar')) {
            throw new \Exception(t('Required libraries not found.'));
        }

        $this->installContentFile('config/permissions.xml');

        return parent::install();
    }

    public function on_start()
    {
        $permission = Key::getByHandle('show_debug_bar');

        if ($permission && $permission->validate()) {

            $this->registerAutoload();

            $app = $this->getApplication();

            $app->singleton('debugbar', Debugbar::class);
            $app->bind('debugbar/renderer', function () use ($app) {
                /** @var Debugbar $debugbar */
                $debugbar = $app->make('debugbar');

                return $debugbar->getJavascriptRenderer($this->getRelativePath() . '/vendor/maximebf/debugbar/src/DebugBar/Resources');
            });
            $app->bind('debugbar/messages', function () use ($app) {
                $debugbar = $app->make('debugbar');

                return $debugbar['messages'];
            });
            $app->bind('debugbar/time', function () use ($app) {
                $debugbar = $app->make('debugbar');

                return $debugbar['time'];
            });

            /** @var EventDispatcher $director */
            $director = $app->make('director');

            $director->addListener('on_before_dispatch', function ($event) use ($app) {
                $app->make('debugbar/time')->startMeasure('dispatch', t('Run App'));
            });

            $director->addListener('on_page_view', function ($event) use ($app) {
                $app->make('debugbar/time')->startMeasure('page_view', t('Render Page'));
            });

            $director->addListener('on_start', function ($event) use ($app) {
                $app->make('debugbar/time')->startMeasure('render_view', t('Render View'));
            });

            $director->addListener('on_before_render', function ($event) use ($app) {
                $debugbarRenderer = $app->make('debugbar/renderer');
                $v = $event->getArgument('view');
                $v->addHeaderItem($debugbarRenderer->renderHead());
                $v->addFooterItem(self::PLACEHOLDER_TEXT);
                $app->make('debugbar/time')->startMeasure('render_template', t('Render Template'));
            });

            $director->addListener('on_render_complete', function ($event) use ($app) {
                if ($app->make('debugbar/time')->hasStartedMeasure('render_view')) {
                    $app->make('debugbar/time')->stopMeasure('render_view');
                }
                if ($app->make('debugbar/time')->hasStartedMeasure('render_template')) {
                    $app->make('debugbar/time')->stopMeasure('render_template');
                }
            });

            $director->addListener('on_shutdown', function ($event) use ($app) {
                if ($app->make('debugbar/time')->hasStartedMeasure('page_view')) {
                    $app->make('debugbar/time')->stopMeasure('page_view');
                }
                if ($app->make('debugbar/time')->hasStartedMeasure('dispatch')) {
                    $app->make('debugbar/time')->stopMeasure('dispatch');
                }
            });

            $director->addListener('on_block_load', function ($event) use ($app) {
                $bID = $event->getArgument('bID');
                $btHandle = $event->getArgument('btHandle');
                $app->make('debugbar/time')->startMeasure(sprintf('load_block_%d', $bID), sprintf('Render %s block (bID: %d)', $btHandle, $bID));
            });

            $director->addListener('on_block_before_render', static function ($event) use ($app) {
                /** @var \Concrete\Core\Block\Block $b */
                $b = $event->getBlock();
                if ($b) {
                    $bID = $b->getBlockID();
                    $btHandle = $b->getBlockTypeHandle();
                    $app->make('debugbar/time')->startMeasure(sprintf('render_block_%d', $bID), sprintf('Render %s block template (bID: %d)', $btHandle, $bID));
                }
            });

            $director->addListener('on_block_output', static function ($event) use ($app) {
                /** @var \Concrete\Core\Block\Block $b */
                $b = $event->getBlock();
                if ($b) {
                    $bID = $b->getBlockID();
                    if ($app->make('debugbar/time')->hasStartedMeasure(sprintf('load_block_%d', $bID))) {
                        $app->make('debugbar/time')->stopMeasure(sprintf('load_block_%d', $bID), [
                            'arHandle' => $b->getAreaHandle(),
                        ]);
                    }
                    if ($app->make('debugbar/time')->hasStartedMeasure(sprintf('render_block_%d', $bID))) {
                        $app->make('debugbar/time')->stopMeasure(sprintf('render_block_%d', $bID), [
                            'template' => $b->getBlockFilename(),
                        ]);
                    }
                }
            });

            $director->addListener('on_page_output', function ($event) use ($app) {
                $debugbarRenderer = $app->make('debugbar/renderer');
                $contents = $event->getArgument('contents');
                $contents = str_replace(self::PLACEHOLDER_TEXT, $debugbarRenderer->render(), $contents);
                $event->setArgument('contents', $contents);
            });
        }
    }

    /**
     * Register autoloader.
     */
    protected function registerAutoload()
    {
        if (file_exists($this->getPackagePath() . '/vendor/autoload.php')) {
            require $this->getPackagePath() . '/vendor/autoload.php';
        }
    }
}
