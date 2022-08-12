<?php
namespace ConcreteDebugbar\DataCollector;

use Concrete\Core\Support\Facade\Application;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionDataCollector extends DataCollector implements Renderable
{
    /**
     * @inheritDoc
     */
    function collect()
    {
        $app = Application::getFacadeApplication();
        /** @var Session $session */
        $session = $app->make('session');

        return $session->all();
    }

    /**
     * @inheritDoc
     */
    function getName()
    {
        return 'concrete_session';
    }

    /**
     * @inheritDoc
     */
    function getWidgets()
    {
        return [
            "session" => [
                "icon" => "user",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "concrete_session",
                "default" => "{}"
            ]
        ];
    }

}