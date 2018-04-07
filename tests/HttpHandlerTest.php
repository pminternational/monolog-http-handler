<?php

namespace Msschl\Monolog\Handler\Tests\HttpHandler;

use Http\Mock\Client;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\BufferHandler;
use Monolog\Logger;
use Msschl\Monolog\Handler\HttpHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
*
*/
class HttpHandlerTest extends TestCase
{

	/**
	 * The http mock client.
	 *
	 * @var \Http\Mock\Client
	 */
	protected $client;

	/**
	 * The http handler instance.
	 *
	 * @var \Msschl\Monolog\Handler\HttpHandler
	 */
	protected $handler;

	/**
	 * This method is run once for each test method and creates an instance of the HttpHandler.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		$this->client = new Client();
		$this->handler = new HttpHandler([], $this->client);
	}

	public function testDefaultValuesAfterInstanceIsCreated()
	{
		$this->assertNull($this->handler->getUri());
		$this->assertSame('GET', $this->handler->getMethod());
		$this->assertSame(['Content-Type' => 'application/json'], $this->handler->getHeaders());
		$this->assertSame('1.1', $this->handler->getProtocolVersion());
	}

	public function testSetOptionsAndReturnsSelfInstance()
	{
		$expectedUri     		 = 'https://log.server/log/endpoint';
		$expectedMethod    		 = 'POST';
		$expectedHeaders 		 = ['Content-Type' => 'application/xml'];
		$expectedProtocolVersion = '1.0';

		$options = [
			'uri'     		  => $expectedUri,
			'method'  		  => $expectedMethod,
			'headers'		  => $expectedHeaders,
			'protocolVersion' => $expectedProtocolVersion
		];

		$returnValue = $this->handler->setOptions($options);

		$this->assertInstanceOf(HttpHandler::class, $returnValue);
		$this->assertSame($this->handler, $returnValue);

		$this->assertTrue(is_string($this->handler->getUri()));
		$this->assertSame($expectedUri, $this->handler->getUri());

		$this->assertTrue(is_string($this->handler->getMethod()));
		$this->assertSame($expectedMethod, $this->handler->getMethod());

		$this->assertTrue(is_array($this->handler->getHeaders()));
		$this->assertSame($expectedHeaders, $this->handler->getHeaders());

		$this->assertTrue(is_string($this->handler->getProtocolVersion()));
		$this->assertSame($expectedProtocolVersion, $this->handler->getProtocolVersion());
	}

	public function testSetUriAndReturnsSelfInstance()
	{
		$expected = 'https://log.server/log/endpoint';

		$returnValue = $this->handler->setUri($expected);

		$this->assertInstanceOf(HttpHandler::class, $returnValue);
		$this->assertSame($this->handler, $returnValue);

		$this->assertTrue(is_string($this->handler->getUri()));
		$this->assertSame($expected, $this->handler->getUri());
	}

	public function testSetUriToNullAndReturnsSelfInstance()
	{
		$expected = null;

		$returnValue = $this->handler->setUri($expected);

		$this->assertInstanceOf(HttpHandler::class, $returnValue);
		$this->assertSame($this->handler, $returnValue);

		$this->assertSame($expected, $this->handler->getUri());
	}

	public function testSetMethodAndReturnsSelfInstance()
	{
		$expected = 'PUT';

		$returnValue = $this->handler->setMethod($expected);

		$this->assertInstanceOf(HttpHandler::class, $returnValue);
		$this->assertSame($this->handler, $returnValue);

		$this->assertTrue(is_string($this->handler->getMethod()));
		$this->assertSame($expected, $this->handler->getMethod());
	}

	public function testSetHeadersAndReturnsSelfInstance()
	{
		$expected = ['Content-Type' => 'application/xml'];

		$returnValue = $this->handler->setHeaders($expected);

		$this->assertInstanceOf(HttpHandler::class, $returnValue);
		$this->assertSame($this->handler, $returnValue);

		$this->assertTrue(is_array($this->handler->getHeaders()));
		$this->assertSame($expected, $this->handler->getHeaders());
	}

	public function testSetProtocolVersionAndReturnsSelfInstance()
	{
		$expected = '1.0';

		$returnValue = $this->handler->setProtocolVersion($expected);

		$this->assertInstanceOf(HttpHandler::class, $returnValue);
		$this->assertSame($this->handler, $returnValue);

		$this->assertTrue(is_string($this->handler->getProtocolVersion()));
		$this->assertSame($expected, $this->handler->getProtocolVersion());
	}

	public function testHandleBatch()
	{
		$stub = $this->createMock(FormatterInterface::class, ['format', 'formatBatch']);

		$stub->method('formatBatch')->willReturn(array());

		$log = new Logger('logger');

		$this->handler->setFormatter($stub);

		$log->pushHandler(new BufferHandler($this->handler, 3, Logger::DEBUG, true, true));

		$stub->expects($this->once())->method('formatBatch');
		$stub->expects($this->exactly(0))->method('format');

		$log->error('first');
		$log->error('second');
		$log->error('third');
		$log->error('fourth');
	}

	public function testHandleBatchGetsCalled()
	{
		$stub = $this->createMock(HttpHandler::class, ['handleBatch']);

		$log = new Logger('logger');

		$log->pushHandler(new BufferHandler($stub, 3, Logger::DEBUG, true, true));

		$stub->expects($this->once())->method('handleBatch');

		$log->error('first');
		$log->error('second');
		$log->error('third');
		$log->error('fourth');
	}

	public function testSendHttpRequest()
	{
		$stub = $this->createMock(RequestInterface::class);

		$log = new Logger('logger');

		$this->handler->setUri('https://log.server/log/endpoint');

		$log->pushHandler($this->handler);

		$log->error('Bar');

		$this->assertInstanceOf(RequestInterface::class, $this->client->getLastRequest());
		$this->assertNotNull($this->client->getLastRequest());
	}

	public function testDoNotSendHttpRequestOnEmptyUri()
	{
		$stub = $this->createMock(RequestInterface::class);

		$log = new Logger('logger');

		$log->pushHandler($this->handler);

		$log->error('Bar');

		$this->assertNotSame($stub, $this->client->getLastRequest());
		$this->assertSame(false, $this->client->getLastRequest());
	}

	public function testSendHttpRequestAndCatchException()
	{
		$stub = $this->createMock(RequestInterface::class);

		$exception = new \Exception('Whoops!');
		$this->client->addException($exception);

		$log = new Logger('logger');

		$log->pushHandler($this->handler);

		$log->error('Bar');

		$this->assertNotSame($stub, $this->client->getLastRequest());
		$this->assertSame(false, $this->client->getLastRequest());
	}

	/**
	 * This method is run once after each test method and frees the HttpHandler instace.
	 *
	 * @return void
	 */
	protected function tearDown()
	{
		$this->handler = null;
	}
}