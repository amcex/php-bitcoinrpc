<?php

use Denpa\Bitcoin;
use Denpa\Bitcoin\Exceptions;
use GuzzleHttp\Psr7\BufferStream;

class BitcoindResponseTest extends TestCase
{
    /**
     * Set up test.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->guzzleResponse = $this->getBlockResponse();
        $this->response = Bitcoin\BitcoindResponse::createFrom($this->guzzleResponse);
    }

    public function testResult()
    {
        $this->assertTrue($this->response->hasResult());

        $this->assertEquals(
            null, $this->response->error()
        );
        $this->assertEquals(
            self::$getBlockResponse,
            $this->response->result()
        );
    }

    public function testNoResult()
    {
        $response = Bitcoin\BitcoindResponse::createFrom(
            $this->rawTransactionError()
        );

        $this->assertFalse($response->hasResult());
    }

    public function testStatusCode()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testReasonPhrase()
    {
        $this->assertEquals('OK', $this->response->getReasonPhrase());
    }

    public function testWithStatus()
    {
        $response = $this->response->withStatus(444, 'test');

        $this->assertEquals(444, $response->getStatusCode());
        $this->assertEquals('test', $response->getReasonPhrase());
    }

    public function testCreateFrom()
    {
        $guzzleResponse = $this->getBlockResponse();

        $response = Bitcoin\BitcoindResponse::createFrom($guzzleResponse);

        $this->assertInstanceOf(Bitcoin\BitcoindResponse::class, $response);
        $this->assertEquals($response->response(), $guzzleResponse);
    }

    public function testError()
    {
        $response = Bitcoin\BitcoindResponse::createFrom(
            $this->rawTransactionError()
        );

        $this->assertTrue($response->hasError());

        $this->assertEquals(
            null, $response->result()
        );
        $this->assertEquals(
            self::$rawTransactionError,
            $response->error()
        );
    }

    public function testNoError()
    {
        $this->assertFalse($this->response->hasError());
    }

    public function testArrayAccessGet()
    {
        $this->assertEquals(
            self::$getBlockResponse['hash'],
            $this->response['hash']
        );
    }

    public function testArrayAccessSet()
    {
        try {
            $this->response['hash'] = 'test';
            $this->expectException(Exceptions\ClientException::class);
        } catch (Exceptions\ClientException $e) {
            $this->assertEquals(
                'Cannot modify readonly object',
                $e->getMessage()
            );
        }
    }

    public function testArrayAccessUnset()
    {
        try {
            unset($this->response['hash']);
            $this->expectException(Exceptions\ClientException::class);
        } catch (Exceptions\ClientException $e) {
            $this->assertEquals(
                'Cannot modify readonly object',
                $e->getMessage()
            );
        }
    }

    public function testArrayAccessIsset()
    {
        $this->assertTrue(isset($this->response['hash']));
        $this->assertFalse(isset($this->response['cookie']));
    }

    public function testInvoke()
    {
        $response = $this->response;

        $this->assertEquals(
            self::$getBlockResponse['hash'],
            $response('hash')
        );
    }

    public function testGet()
    {
        $this->assertEquals(
            self::$getBlockResponse['hash'],
            $this->response->get('hash')
        );

        $this->assertEquals(
            self::$getBlockResponse['tx'][0],
            $this->response->get('tx.0')
        );
    }

    public function testHas()
    {
        $this->assertTrue($this->response->has('hash'));
        $this->assertTrue($this->response->has('tx.0'));
        $this->assertFalse($this->response->has('tx.3'));
        $this->assertFalse($this->response->has('cookies'));
        $this->assertFalse($this->response->has('height'));
    }

    public function testExists()
    {
        $this->assertTrue($this->response->exists('hash'));
        $this->assertTrue($this->response->exists('tx.0'));
        $this->assertTrue($this->response->exists('tx.3'));
        $this->assertTrue($this->response->exists('height'));
        $this->assertFalse($this->response->exists('cookies'));
    }

    public function testContains()
    {
        $this->assertTrue($this->response->contains('00000000839a8e6886ab5951d76f411475428afc90947ee320161bbf18eb6048'));
        $this->assertFalse($this->response->contains('cookies'));
    }

    public function testKeys()
    {
        $this->assertEquals(
            array_keys(self::$getBlockResponse),
            $this->response->keys()
        );
    }

    public function testValue()
    {
        $this->assertEquals(
            array_values(self::$getBlockResponse),
            $this->response->values()
        );
    }

    public function testRandom()
    {
        $tx1 = $this->response->random(1, 'tx');
        $tx2 = $this->response->random(1, 'tx');
        $this->assertContains($tx1, self::$getBlockResponse['tx']);
        $this->assertContains($tx2, self::$getBlockResponse['tx']);

        $random = $this->response->random();
        $this->assertContains($random, self::$getBlockResponse);

        $random2 = $this->response->random(2);
        $this->assertCount(2, $random2);
        $this->assertArraySubset($random2, self::$getBlockResponse);

        $random3 = $this->response->random(1, 'merkleroot');
        $this->assertEquals(self::$getBlockResponse['merkleroot'], $random3);

        $random4 = $this->response->random(6, 'tx');
        $this->assertEquals(self::$getBlockResponse['tx'], $random4);
    }

    public function testCount()
    {
        $this->assertEquals(
            count(self::$getBlockResponse),
            count($this->response)
        );

        $this->assertEquals(
            count(self::$getBlockResponse),
            $this->response->count()
        );
    }

    public function testProtocolVersion()
    {
        $response = $this->response->withProtocolVersion(1.0);
        $protocolVersion = $response->getProtocolVersion();

        $this->assertEquals('1.0', $protocolVersion);
    }

    public function testHeaders()
    {
        $response = $this->response->withHeader('X-Foo', 'bar');
        $response = $response->withAddedHeader('X-Bar', 'baz');

        $headers = $response->getHeaders();
        $hasXFoo = $response->hasHeader('X-Foo');
        $hasXBar = $response->hasHeader('X-Bar');
        $XFoo = $response->getHeader('X-Foo')[0];
        $XBar = $response->getHeader('X-Bar')[0];
        $XFooLine = $response->getHeaderLine('X-Foo');

        $this->assertTrue($hasXFoo);
        $this->assertTrue($hasXBar);
        $this->assertEquals('bar', $XFoo);
        $this->assertEquals('baz', $XBar);
        $this->assertEquals('bar', $XFooLine);

        $expected = [
            'X-Foo' => ['bar'],
            'X-Bar' => ['baz'],
        ];

        $this->assertEquals($expected, $headers);

        $response = $response->withoutHeader('X-Bar');

        $hasXBar = $response->hasHeader('X-Bar');

        $this->assertFalse($hasXBar);
    }

    public function testBody()
    {
        $stream = new BufferStream();
        $stream->write('cookies');

        $response = $this->response->withBody($stream);

        $this->assertEquals('cookies', $response->getBody()->__toString());
    }

    public function testSerialize()
    {
        $serializedContainer = serialize($this->response->getContainer());
        $class = Bitcoin\BitcoindResponse::class;

        $serialized = sprintf(
            'C:%u:"%s":%u:{%s}',
            strlen($class),
            $class,
            strlen($serializedContainer),
            $serializedContainer
        );

        $this->assertEquals(
            $serialized,
            serialize($this->response)
        );
    }

    public function testUnserialize()
    {
        $container = $this->response->getContainer();

        $this->assertEquals(
            $container,
            unserialize(serialize($this->response))->getContainer()
        );
    }

    public function testJsonSerialize()
    {
        $this->assertEquals(
            json_encode($this->response->getContainer()),
            json_encode($this->response)
        );
    }
}
