<?php
namespace Wandu\Foundation\Contracts;

use Wandu\Console\Dispatcher;
use Wandu\DI\ContainerInterface;
use Wandu\Router\Router;
use Wandu\Router\RoutesInterface;

interface DefinitionInterface extends RoutesInterface
{
    /**
     * @return array
     */
    public function configs();

    /**
     * @param \Wandu\DI\ContainerInterface $app
     */
    public function providers(ContainerInterface $app);

    /**
     * @param \Wandu\Console\Dispatcher $dispatcher
     */
    public function commands(Dispatcher $dispatcher);

    /**
     * @param \Wandu\Router\Router $router
     */
    public function routes(Router $router);
}