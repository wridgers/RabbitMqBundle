<?php

namespace OldSound\RabbitMqBundle\RabbitMq;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AMQPConnectionFactory
{
    /** @var \ReflectionClass */
    private $class;

    /** @var array */
    private $parameters = array(
        'url'                => '',
        'host'               => 'localhost',
        'port'               => 5672,
        'user'               => 'guest',
        'password'           => 'guest',
        'vhost'              => '/',
        'connection_timeout' => 3,
        'read_write_timeout' => 3,
        'ssl_context'        => null,
        'keepalive'          => false,
        'heartbeat'          => 0,
    );

    /**
     * Constructor
     *
     * @param string $class      FQCN of AMQPConnection class to instantiate.
     * @param array  $parameters Map containing parameters resolved by Extension.
     */
    public function __construct($class, array $parameters)
    {
        $this->class = $class;
        $this->parameters = array_merge($this->parameters, $parameters);
        $this->parameters = $this->parseUrl($this->parameters);
        if (is_array($this->parameters['ssl_context'])) {
            $this->parameters['ssl_context'] = ! empty($this->parameters['ssl_context'])
                ? stream_context_create(array('ssl' => $this->parameters['ssl_context']))
                : null;
        }
    }

    public function createConnection()
    {
        return new $this->class(
            $this->parameters['host'],
            $this->parameters['port'],
            $this->parameters['user'],
            $this->parameters['password'],
            $this->parameters['vhost'],
            false,      // insist
            'AMQPLAIN', // login_method
            null,       // login_response
            'en_US',    // locale
            $this->parameters['connection_timeout'],
            $this->parameters['read_write_timeout'],
            $this->parameters['ssl_context'],
            $this->parameters['keepalive'],
            $this->parameters['heartbeat']
        );
    }

    private function parseUrl($parameters)
    {
        if (!$parameters['url']) {
            return $parameters;
        }

        $url = parse_url($parameters['url']);

        if ($url === false || !isset($url['scheme']) || $url['scheme'] !== 'amqp') {
            throw new InvalidConfigurationException('Malformed parameter "url".');
        }

        if (isset($url['host'])) {
            $parameters['host'] = $url['host'];
        }
        if (isset($url['port'])) {
            $parameters['port'] = $url['port'];
        }
        if (isset($url['user'])) {
            $parameters['user'] = $url['user'];
        }
        if (isset($url['pass'])) {
            $parameters['password'] = $url['pass'];
        }
        if (isset($url['path'])) {
            $parameters['vhost'] = $url['path'];
        }

        if (isset($url['query'])) {
            $query = array();
            parse_str($url['query'], $query);
            $parameters = array_merge($parameters, $query);
        }

        unset($parameters['url']);

        return $parameters;
    }
}
