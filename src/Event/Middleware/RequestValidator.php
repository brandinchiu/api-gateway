<?php

namespace App\Event\Middleware;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class RequestValidator
 * @package App\Event\Middleware
 */
class RequestValidator
{
    /** @var ContainerInterface  */
    protected ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param RequestEvent $event
     * @return void
     * @throws \Exception
     */
    public function execute(RequestEvent $event)
    {
        $destination = null;
        $pathParts = explode('/', $event->getRequest()->getRequestUri());
        $headers = $event->getRequest()->headers->all();

        foreach($headers as $key=>$value) {
            $headers[$key] = current($value);
        }

        try {
            /**
             * load the config file.
             */
            $config = Yaml::parseFile(
                sprintf('%s/config/gateway.yaml', $this->container->get('kernel')->getProjectDir())
            );

            if(
                $config == null ||
                !array_key_exists('destinations', $config)
            ) {
                throw new ParseException('Error loading gateway configuration');
            }

            /**
             * find the first destination that meets all criteria.
             */
            foreach($config['destinations'] as $name=>$rules) {
                /**
                 * evaluate request path for rule requirements.
                 */
                if(!empty(array_diff($rules['hasPath'], $pathParts))) {
                    continue;
                }

                /**
                 * evaluate request headers for rule requirements.
                 */
                foreach($rules['hasHeader'] as $headerRule) {
                    $parts = explode(':', $headerRule);

                    if(!is_array($parts)) {
                        continue;
                    }

                    $header = $parts[0];
                    $value = $parts[1];

                    /**
                     * if any of the header values don't match, skip to the next destination.
                     */
                    if(
                        !array_key_exists($header, $headers) ||
                        $headers[$header] != $value
                    ) {
                        continue 2;
                    }
                }

                $destination = $name;
                break;
            }

            if($destination == null) {
                throw new ParseException('No suitable destination found for request');
            }

        } catch(ParseException $e) {
            /**
             * TODO: throw HTTP504: Bad Gateway.
             */
            throw new \Exception('HTTP504: Bad Gateway');
        }

        $event->getRequest()->attributes->set(
            'route-name',
            $destination
        );

        $event->getRequest()->attributes->set(
            'route-to',
            $this->getDestination($config['destinations'][$destination])
        );
    }

    /**
     * @param array $rules
     * @return string|null
     */
    private function getDestination(array $rules): ?string
    {
        $destination = null;

        if(
            array_key_exists('url', $rules) &&
            $rules['url'] != null
        ) {
            $destination = $rules['url'];
        }

        if(
            array_key_exists('hostname', $rules) &&
            array_key_exists('scheme', $rules) &&
            $rules['hostname'] != null &&
            $rules['scheme'] != null
        ) {
            $destination = sprintf("%s://%s", $rules['scheme'], $rules['hostname']);
        }

        return $destination;
    }
}
