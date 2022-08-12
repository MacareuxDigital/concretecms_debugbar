<?php
namespace ConcreteDebugbar;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Support\Facade\Application;
use ConcreteDebugbar\DataCollector\EnvironmentDataCollector;
use ConcreteDebugbar\DataCollector\LogDataCollector;
use ConcreteDebugbar\DataCollector\RequestDataCollector;
use ConcreteDebugbar\DataCollector\SessionDataCollector;
use DebugBar\Bridge\DoctrineCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\TimeDataCollector;
use Doctrine\DBAL\Logging\DebugStack;

class Debugbar extends \DebugBar\DebugBar
{
    /**
     * Debugbar constructor.
     */
    public function __construct()
    {
        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new MessagesCollector());
        $this->addCollector(new TimeDataCollector());
        $this->addCollector(new MemoryCollector());
        $this->addCollector(new RequestDataCollector());
        $this->addCollector(new SessionDataCollector());
        $doctrineDebugStack = new DebugStack();
        $app = Application::getFacadeApplication();
        /** @var Connection $connection */
        $connection = $app->make(Connection::class);
        $connection->getConfiguration()->setSQLLogger($doctrineDebugStack);
        $this->addCollector(new DoctrineCollector($doctrineDebugStack));
        $this->addCollector(new LogDataCollector());
        $this->addCollector(new EnvironmentDataCollector());
    }
}