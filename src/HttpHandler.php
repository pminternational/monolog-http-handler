<?php

namespace Msschl\Monolog\Handler;

use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * This file is part of the msschl\monolog-http-handler package.
 *
 * Copyright (c) 2018 Markus Schlotbohm
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */
class HttpHandler extends AbstractProcessingHandler
{

	/**
	 * The http client instance.
	 *
     * @var \Http\Client\HttpAsyncClient
     */
    protected $client;

    /**
     * The message factory instance.
     *
     * @var \Http\Message\MessageFactory
     */
    protected $messageFactory;

    /**
     * The options array.
     *
     * @var array
     */
    protected $options = [
    	'uri'             => null,
    	'method'          => 'GET',
    	'headers'         => [
    		'Content-Type' => 'application/json'
    	],
    	'protocolVersion' => '1.1'
    ];

    /**
     * Create a new monolog http client handler instance.
     *
     * @param  array                $options The array of options consisting of the uri, method, headers and protocol
     *                                       version.
     * @param  HttpAsyncClient|null $client  An instance of a psr-7 http async client implementation or null when the
     *                                       HttpAsyncClientDiscovery should be used to find an instance.
     * @param  MessageFactory|null  $factory An instance of a psr-7 message factory implementation or null when
     *                                       the MessageFactoryDiscovery should be used to find an instance.
     * @param  int                  $level   The minimum logging level at which this handler will be triggered.
     * @param  boolean              $bubble  Whether the messages that are handled can bubble up the stack or not.
     */
	public function __construct(
		array $options = [],
		HttpAsyncClient $client = null,
		MessageFactory $factory = null,
		$level = Logger::DEBUG,
		$bubble = true
	) {
		$this->client = $client ?: HttpAsyncClientDiscovery::find();
		$this->messageFactory = $factory ?: MessageFactoryDiscovery::find();

		$this->setOptions($options);

		parent::__construct($level, $bubble);
	}

	/**
	 * Sets the options for the monolog http handler.
	 *
	 * @param  array $options
	 * @return \Msschl\Monolog\Handler\HttpHandler
	 */
	public function setOptions(array $options)
	{
		$this->options = array_merge($this->options, $options);

		return $this;
	}

	/**
	 * Sets the uri.
	 *
	 * @param  string|null $uri
	 * @return \Msschl\Monolog\Handler\HttpHandler
	 */
	public function setUri(string $uri = null)
	{
		$this->options['uri'] = $uri;

		return $this;
	}

	/**
	 * Gets the uri.
	 *
	 * @return string|null
	 */
	public function getUri()
	{
		return $this->options['uri'];
	}

	/**
	 * Sets the http method.
	 *
	 * @param  string $method
	 * @return \Msschl\Monolog\Handler\HttpHandler
	 */
	public function setMethod(string $method)
	{
		$this->options['method'] = $method;

		return $this;
	}

	/**
	 * Gets the http method.
	 *
	 * @return string
	 */
	public function getMethod() : string
	{
		return $this->options['method'] ?: 'GET';
	}

	/**
	 * Sets the headers.
	 *
	 * @param  array $headers
	 * @return \Msschl\Monolog\Handler\HttpHandler
	 */
	public function setHeaders(array $headers)
	{
		$this->options['headers'] = $headers;

		return $this;
	}

	/**
	 * Gets the headers.
	 *
	 * @return array
	 */
	public function getHeaders() : array
	{
		return $this->options['headers'] ?: [ 'Content-Type' => 'application/json' ];
	}

	/**
	 * Sets the http protocol version.
	 *
	 * @param  string $version
	 * @return \Msschl\Monolog\Handler\HttpHandler
	 */
	public function setProtocolVersion(string $version = '1.1')
	{
		$this->options['protocolVersion'] = $version;

		return $this;
	}

	/**
	 * Gets the http protocol version.
	 *
	 * @return string
	 */
	public function getProtocolVersion() : string
	{
		return $this->options['protocolVersion'] ?: '1.1';
	}

	/**
     * Gets the default formatter.
     *
     * @return \Monolog\Formatter\JsonFormatter
     */
    protected function getDefaultFormatter() : FormatterInterface
    {
        return new JsonFormatter();
    }

	/**
     * Returns the HTTP adapter.
     *
     * @return \Http\Client\HttpAsyncClient
     */
    protected function getHttpClient(): HttpAsyncClient
    {
        return $this->client;
    }

    /**
     * Returns the message factory.
     *
     * @return \Http\Message\MessageFactory
     */
    protected function getMessageFactory(): MessageFactory
    {
        return $this->messageFactory;
    }

    /**
     * Writes the record.
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record)
    {
    	$uri = $this->getUri();

    	if (empty($uri)) {
    		return;
    	}

    	$request = $this->getMessageFactory()->createRequest(
    		$this->getMethod(),
    		$this->getUri(),
    		$this->getHeaders(),
    		$record['formatted'],
    		$this->getProtocolVersion()
    	);

        $this->getHttpClient()->sendAsyncRequest($request);
    }
}