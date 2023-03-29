<?php

namespace App\Service;

use Google\Cloud\Logging\Logger;
use Google\Cloud\Logging\LoggingClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LoggingService
 * @package App\Service
 */
class LoggingService
{
    /** @var ContainerInterface  */
    protected ContainerInterface $container;

    /**
     * @var LoggingClient
     */
    private LoggingClient $client;

    /** @var Logger  */
    private Logger $logger;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        /**
         * TODO: get credentials from env.
         */
        $this->client = new LoggingClient([
            'projectId' => 'alchemy-374618',
            'keyFilePath' => '/Users/misfitpixel/Development/projects/misfitpixel/api-gateway/var/google/credentials.json'
        ]);

        $this->logger = $this->client->logger('gateway');
    }

    /**
     * @param array|string $content
     * @param array $labels
     * @param Request|null $request
     * @param array|null $sourceLocation
     * @param int $severity
     * @return void
     */
    public function log($content, array $labels = [], Request $request = null, array $sourceLocation = null, int $severity = Logger::DEBUG)
    {
        /**
         * TODO: check params for sourceLocation.
         */
        $entry = $this->getLogger()->entry($content, [
            'severity' => $severity,
            'httpRequest' => $this->convertToHttpRequest($request),
            'labels' => array_merge($labels, [
                'app' => 'api-gateway'
            ]),
            /**
             * TODO: handle sourceLocation when logging so that it doesn't always come from this file.
             */
            'sourceLocation' => $sourceLocation
        ]);

        $this->getLogger()->write($entry);
    }

    /**
     * @param \Exception $exception
     * @param Request $request
     * @return void
     */
    public function logException(\Exception $exception, Request $request)
    {
        $this->log(
            $exception->getMessage(),
            [
                'exceptionClass' => get_class($exception)
            ],
            $request,
            [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'function' => null
            ],
            Logger::ERROR
        );
    }

    /**
     * @return Logger
     */
    protected function getLogger(): Logger
    {
        return $this->logger;
    }

    protected function convertToHttpRequest(Request $request): array
    {
        /**
         * TODO: handle missing fields.
         */
        return [
            'requestMethod' => $request->getMethod(),
            'requestUrl' => $request->getRequestUri(),
            'requestSize' => $request->headers->get('Content-Size'),
            'status' => 200,
            'responseSize' => null,
            'userAgent' => $request->headers->get('User-Agent'),
            'remoteIp' => $request->server->get('REMOTE_ADDR'),
            'serverIp' => $request->server->get('SERVER_ADDR'),
            'referer' => $request->headers->get('Referer'),
            'latency' => '0s',
            'cacheLookup' => false,
            'cacheHit' => false,
            'cacheValidatedWithOriginServer' => false,
            'cacheFillBytes' => 0,
            'protocol' => $request->getProtocolVersion()
        ];
    }
}
