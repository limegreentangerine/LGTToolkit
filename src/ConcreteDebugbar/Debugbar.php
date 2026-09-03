<?php
namespace LgtToolkit\ConcreteDebugbar;

use Doctrine\DBAL\Logging\DebugStack;
use DebugBar\Bridge\DoctrineCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use Concrete\Core\Support\Facade\Application;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\TimeDataCollector;
use Concrete\Core\Database\Connection\Connection;
use LgtToolkit\ConcreteDebugbar\DataCollector\LogDataCollector;
use LgtToolkit\ConcreteDebugbar\DataCollector\RequestDataCollector;
use LgtToolkit\ConcreteDebugbar\DataCollector\SessionDataCollector;
use LgtToolkit\ConcreteDebugbar\DataCollector\EnvironmentDataCollector;

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
