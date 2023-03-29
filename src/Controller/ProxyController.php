<?php

namespace App\Controller;

use App\Service\GatewayService;
use App\Service\LoggingService;
use Google\Cloud\Logging\Logger;
use Google\Cloud\Logging\LoggingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProxyController
 * @package App\Controller
 */
class ProxyController extends AbstractController
{
    /** @var GatewayService  */
    protected GatewayService $gatewayService;

    /** @var LoggingService  */
    protected LoggingService $loggingService;

    /**
     * @param GatewayService $gatewayService
     * @param LoggingService $loggingService
     */
    public function __construct(GatewayService $gatewayService, LoggingService $loggingService)
    {
        $this->gatewayService = $gatewayService;
        $this->loggingService = $loggingService;
    }

    public function route(Request $request): Response
    {
        /**
         * TODO: move to middleware?
         */
        $this->loggingService->log('another log test again', ['custom' => 'abc123'], $request);
        $this->loggingService->logException(new \Exception('another exception occurred'), $request);
        /**
         * push request through curl service.
         */
        return $this->gatewayService->forward($request);
    }
}
