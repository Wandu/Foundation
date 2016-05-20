<?php
namespace Wandu\Foundation\Kernels;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Wandu\Config\Contracts\ConfigInterface;
use Wandu\DI\ContainerInterface;
use Wandu\Foundation\Bridges\WhoopsToPsr7;
use Wandu\Foundation\Contracts\HttpErrorHandlerInterface;
use Wandu\Foundation\Contracts\DefinitionInterface;
use Wandu\Foundation\Contracts\KernelInterface;
use Wandu\Http\Exception\AbstractHttpException;
use Wandu\Http\Exception\HttpException;
use Wandu\Http\Exception\HttpMethodNotAllowedException;
use Wandu\Http\Exception\HttpNotFoundException;
use Wandu\Http\Exception\InternalServerErrorException;
use Wandu\Http\Middleware\Responsify;
use Wandu\Http\Psr\Factory\ServerRequestFactory;
use Wandu\Http\Psr\Response;
use Wandu\Http\Psr\Sender\ResponseSender;
use Wandu\Http\Psr\Stream\StringStream;
use Wandu\Router\Dispatcher;
use Wandu\Router\Exception\MethodNotAllowedException as RouteMethodException;
use Wandu\Router\Exception\RouteNotFoundException;
use Throwable;

class HttpRouterKernel implements KernelInterface
{
    /** @var \Wandu\Foundation\Contracts\DefinitionInterface */
    private $config;

    /**
     * @param \Wandu\Foundation\Contracts\DefinitionInterface $config
     */
    public function __construct(DefinitionInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app)
    {
        $this->config->providers($app);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(ContainerInterface $app)
    {
        /* @var \Wandu\Config\Contracts\ConfigInterface $config*/
        $config = $app->get(ConfigInterface::class);
        
        /* @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = $app->get(ServerRequestFactory::class)->createFromGlobals();

        try {
            $response = $this->dispatch($app->get(Dispatcher::class), $request);
        } catch (AbstractHttpException $exception) {
            /* @var \Wandu\Foundation\Contracts\HttpErrorHandlerInterface $handler */
            $handler = $app->get(HttpErrorHandlerInterface::class);
            $response = $handler->handle($request, $exception);
        } catch (Throwable $exception) {
            // if Debug Mode, make prettyfy response.
            if ($config->get('debug', true)) {
                /* @var \Wandu\Foundation\Bridges\WhoopsToPsr7 $prettifier */
                $prettifier = $app->get(WhoopsToPsr7::class);
                $response = $prettifier->responsify($exception);
            } else {
                /* @var \Wandu\Foundation\Contracts\HttpErrorHandlerInterface $handler */
                $handler = $app->get(HttpErrorHandlerInterface::class);
                $response = $handler->handle($request, $exception);
            }
            if (!$response->getBody()) {
                $body = $response->getReasonPhrase();
                $response = $response->withBody(new Stringstream($body));
            }
        }
        $responify = new Responsify(\Wandu\Http\response());
        $r = $responify->handle($request, function () use ($response) {
            return $response;
        });

        /* @var \Wandu\Http\Psr\Sender\ResponseSender $sender */
        $sender = $app->get(ResponseSender::class);
        $sender->sendToGlobal($r);
        return 0;
    }
    
    /**
     * @param \Wandu\Router\Dispatcher $dispatcher
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return mixed
     * @throws \Wandu\Http\Exception\HttpMethodNotAllowedException
     * @throws \Wandu\Http\Exception\HttpNotFoundException
     */
    protected function dispatch(Dispatcher $dispatcher, ServerRequestInterface $request)
    {
        $dispatcher = $dispatcher->withRoutes($this->config);
        try {
            return $dispatcher->dispatch($request);
        } catch (RouteNotFoundException $exception) {
            throw new HttpNotFoundException();
        } catch (RouteMethodException $exception) {
            throw new HttpMethodNotAllowedException();
        }
    }
}
