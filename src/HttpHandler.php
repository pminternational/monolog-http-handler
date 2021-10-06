<?php

namespace Msschl\Monolog\Handler;

use Exception;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Psr\Http\Client\ClientExceptionInterface;

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
     * @var HttpClient
     */
    protected HttpClient $client;

    /**
     * The message factory instance.
     *
     * @var MessageFactory
     */
    protected MessageFactory $messageFactory;

    /**
     * The options array.
     *
     * @var array
     */
    protected $options = [
        'uri'             => null,
        'method'          => 'GET',
        'headers'         => [
            'Content-Type' => 'application/json',
        ],
        'protocolVersion' => '1.1',
    ];

    /**
     * Initializes a new instance of the {@see HttpHandler} class.
     *
     * @param array               $options   The array of options consisting of the uri, method, headers and protocol
     *                                       version.
     * @param HttpClient|null     $client    An instance of a psr-7 http client implementation or null when the
     *                                       HttpClientDiscovery should be used to find an instance.
     * @param MessageFactory|null $factory   An instance of a psr-7 message factory implementation or null when
     *                                       the MessageFactoryDiscovery should be used to find an instance.
     * @param int                 $level     The minimum logging level at which this handler will be triggered.
     * @param boolean             $bubble    Whether the messages that are handled can bubble up the stack or not.
     */
    public function __construct(
        array $options = [],
        HttpClient $client = null,
        MessageFactory $factory = null,
        int $level = Logger::DEBUG,
        bool $bubble = true
    ) {
        $this->client         = $client ?: HttpClientDiscovery::find();
        $this->messageFactory = $factory ?: MessageFactoryDiscovery::find();

        $this->setOptions($options);

        parent::__construct($level, $bubble);
    }

    /**
     * Sets the options for the monolog http handler.
     *
     * @param array $options The array of options.
     *
     * @return self
     */
    public function setOptions(array $options): HttpHandler
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Gets the uri.
     *
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->options['uri'];
    }

    /**
     * Sets the uri.
     *
     * @param string|null $uri Sets the http server uri or null to disable the {@see HttpHandler}.
     *
     * @return self
     */
    public function setUri(string $uri = null): HttpHandler
    {
        $this->options['uri'] = $uri;

        return $this;
    }

    /**
     * Gets the http method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->options['method'] ?? 'GET';
    }

    /**
     * Sets the http method.
     *
     * @param string $method The http method e.g. 'GET'.
     *
     * @return self
     */
    public function setMethod(string $method): HttpHandler
    {
        $this->options['method'] = $method;

        return $this;
    }

    /**
     * Gets the headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->options['headers'] ?? ['Content-Type' => 'application/json'];
    }

    /**
     * Sets the headers array. Overrides all existing header key and value pairs.
     *
     * @param array $headers The headers array.
     *
     * @return self
     */
    public function setHeaders(array $headers): HttpHandler
    {
        $this->options['headers'] = $headers;

        return $this;
    }

    /**
     * Gets a value for a specific header key.
     *
     * @param string|null $key     The header key.
     * @param string|null $default A default value or null
     *
     * @return string|null $default
     */
    public function getHeader(string $key = null, string $default = null): ?string
    {
        return $this->getHeaders()[$key] ?? $default;
    }

    /**
     * Returns whether a header exists or not.
     *
     * @param string|null $key The header key.
     *
     * @return bool
     */
    public function hasHeader(string $key = null): bool
    {
        if ($key === null) {
            return false;
        }

        $array = $this->getHeaders();

        return isset($array[$key]) || array_key_exists($key, $array);
    }

    /**
     * Pushes a header value onto the headers array.
     *
     * @param string      $key   The header key.
     * @param string|null $value The header value.
     *
     * @return self
     */
    public function pushHeader(string $key, string $value = null): HttpHandler
    {
        $headers = $this->getHeaders();

        $headers[$key] = $value;

        $this->setHeaders($headers);

        return $this;
    }

    /**
     * Pops a header value from the headers array.
     *
     * @param string|null $key The header key.
     *
     * @return string|null
     */
    public function popHeader(string $key = null): ?string
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
    public function getProtocolVersion(): string
    {
        return $this->options['protocolVersion'] ?? '1.1';
    }

    /**
     * Sets the http protocol version.
     *
     * @param string $version The http protocol version.
     *
     * @return self
     */
    public function setProtocolVersion(string $version = '1.1'): HttpHandler
    {
        $this->options['protocolVersion'] = $version;

        return $this;
    }

    /**
     * Handles a set of records at once.
     *
     * @param array $records The records to handle (an array of record arrays)
     *
     * @throws ClientExceptionInterface
     */
    public function handleBatch(array $records): void
    {
        foreach ($records as $key => $record) {
            if ($this->isHandling($record)) {
                $record               = $this->processRecord($record);
                $records['records'][] = $record;
            }

            unset($records[$key]);
        }

        $records['formatted'] = $this->getFormatter()->formatBatch($records['records'] ?? []);

        $this->write($records);
    }

    /**
     * Gets the default formatter.
     *
     * @return JsonFormatter
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new JsonFormatter();
    }

    /**
     * Returns the HTTP adapter.
     *
     * @return HttpClient
     */
    protected function getHttpClient(): HttpClient
    {
        return $this->client;
    }

    /**
     * Returns the message factory.
     *
     * @return MessageFactory
     */
    protected function getMessageFactory(): MessageFactory
    {
        return $this->messageFactory;
    }

    /**
     * Writes the record.
     *
     * @param array $record
     *
     * @return void
     * @throws ClientExceptionInterface
     */
    protected function write(array $record): void
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
        } catch (Exception $e) {
            // QUESTION(msschl): How to handle the thrown exceptions???
            /* istanbul ignore next */
            return;
        }
    }
}
