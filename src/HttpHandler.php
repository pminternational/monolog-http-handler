<?php

namespace Msschl\Monolog\Handler;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
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
     * @var \Http\Client\HttpClient
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
     * Initializes a new instance of the {@see HttpHandler} class.
     *
     * @param  array                $options The array of options consisting of the uri, method, headers and protocol
     *                                       version.
     * @param  HttpClient|null      $client  An instance of a psr-7 http client implementation or null when the
     *                                       HttpClientDiscovery should be used to find an instance.
     * @param  MessageFactory|null  $factory An instance of a psr-7 message factory implementation or null when
     *                                       the MessageFactoryDiscovery should be used to find an instance.
     * @param  int                  $level   The minimum logging level at which this handler will be triggered.
     * @param  boolean              $bubble  Whether the messages that are handled can bubble up the stack or not.
     */
	public function __construct(
		array $options = [],
		HttpClient $client = null,
		MessageFactory $factory = null,
		$level = Logger::DEBUG,
		$bubble = true
	) {
		$this->client = $client ?: HttpClientDiscovery::find();
		$this->messageFactory = $factory ?: MessageFactoryDiscovery::find();

		$this->setOptions($options);

		parent::__construct($level, $bubble);
	}

	/**
	 * Sets the options for the monolog http handler.
	 *
	 * @param  array $options The array of options.
	 * @return self
	 */
	public function setOptions(array $options)
	{
		$this->options = array_merge($this->options, $options);

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
	 * Sets the uri.
	 *
	 * @param  string|null $uri Sets the http server uri or null to disable the {@see HttpHandler}.
	 * @return self
	 */
	public function setUri(string $uri = null)
	{
		$this->options['uri'] = $uri;

		return $this;
	}

	/**
	 * Gets the http method.
	 *
	 * @return string
	 */
	public function getMethod() : string
	{
		return $this->options['method'] ?? 'GET';
	}

	/**
	 * Sets the http method.
	 *
	 * @param  string $method The http method e.g. 'GET'.
	 * @return self
	 */
	public function setMethod(string $method)
	{
		$this->options['method'] = $method;

		return $this;
	}

	/**
	 * Gets the headers.
	 *
	 * @return array
	 */
	public function getHeaders() : array
	{
		return $this->options['headers'] ?? [ 'Content-Type' => 'application/json' ];
	}

	/**
	 * Sets the headers array. Overrides all existing header key and value pairs.
	 *
	 * @param  array $headers The headers array.
	 * @return self
	 */
	public function setHeaders(array $headers)
	{
		$this->options['headers'] = $headers;

		return $this;
	}

	/**
	 * Gets a value for a specific header key.
	 *
	 * @param  string|null $key The header key.
	 * @return string|null
	 */
	public function getHeader(string $key = null)
	{
		return $this->getHeaders()[$key] ?? null;
	}

	/**
	 * Pushes a header value onto the headers array.
	 *
	 * @param  string $key   The header key.
	 * @param  string $value The header value.
	 * @return self
	 */
	public function pushHeader(string $key, string $value)
	{
		$headers = $this->getHeaders();

		$headers[$key] = $value;

		$this->setHeaders($headers);

		return $this;
	}

	/**
	 * Pops a header value from the headers array.
	 *
	 * @param  string|null $key The header key.
	 * @return string|null
	 */
	public function popHeader(string $key = null)
	{
		$value = $this->getHeader($key);

		if ($value !== null) {
			unset($this->options['headers'][$key]);
		}

		return $value;
	}

	/**
	 * Gets the http protocol version.
	 *
	 * @return string
	 */
	public function getProtocolVersion() : string
	{
		return $this->options['protocolVersion'] ?? '1.1';
	}

	/**
	 * Sets the http protocol version.
	 *
	 * @param  string $version The http protocol version.
	 * @return self
	 */
	public function setProtocolVersion(string $version = '1.1')
	{
		$this->options['protocolVersion'] = $version;

		return $this;
	}

	/**
	 * Handles a set of records at once.
	 *
	 * @param  array  $records The records to handle (an array of record arrays)
	 * @return bool
	 */
	public function handleBatch(array $records)
	{
		foreach ($records as $key => $record) {
	        if ($this->isHandling($record)) {
	        	$record = $this->processRecord($record);
	    		$records['records'][] = $record;
	    	}

			unset($records[$key]);
	    }

	    $records['formatted'] = $this->getFormatter()->formatBatch($records['records'] ?? []);

	    $this->write($records);

	    return false === $this->bubble;
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
     * @return \Http\Client\HttpClient
     */
    protected function getHttpClient(): HttpClient
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

    	try {
    		$this->getHttpClient()->sendRequest($request);
    	/* istanbul ignore next */
    	} catch (\Exception $e) {
    		// QUESTION(msschl): How to handle the thrown exceptions???
    		/* istanbul ignore next */
    		return;
    	}
    }
}