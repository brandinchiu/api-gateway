<?php

namespace App\Controller;

use App\Service\GatewayService;
use App\Service\LoggingService;
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

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function route(Request $request): Response
    {
        /**
         * push request through curl service.
         */
        $response = $this->gatewayService->forward($request);

        return $response;
    }
}
