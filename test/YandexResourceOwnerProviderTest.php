<?php

namespace Jokerov\OAuth2\Client\Test\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Jokerov\OAuth2\Client\Provider\Yandex;
use Jokerov\OAuth2\Client\Provider\YandexResourceOwner;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;

class YandexResourceOwnerProviderTest extends TestCase
{
    /**
     * Yandex instance provider
     *
     * @var Yandex
     */
    protected $provider;

    /**
     * Setup this unit test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->provider = new Yandex([
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'https://example.com/redirect',
        ]);

        parent::setUp();
    }

    /**
     * Setup a mocked HTTP client with the given responses
     *
     * @param Response[] $responses
     *
     * @return void
     */
    private function mockHttpClient(array $responses)
    {
        $handlerStack = HandlerStack::create(new MockHandler($responses));
        $this->provider->setHttpClient(new Client(['handler' => $handlerStack]));
    }

    /**
     * Test the resource owner details url does not contain the access token
     *
     * @return void
     */
    public function testGetResourceOwnerDetailsUrl()
    {
        $token = new AccessToken(['access_token' => 'mock_access_token']);

        $url = $this->provider->getResourceOwnerDetailsUrl($token);

        $this->assertEquals('https://login.yandex.ru/info?format=json', $url);
        $this->assertStringNotContainsString('mock_access_token', $url);
    }

    /**
     * Test getResourceOwner() sends the token in the Authorization header
     *
     * @return void
     */
    public function testGetResourceOwner()
    {
        $responseBody = json_encode([
            'id'            => '1000034426',
            'login'         => 'mock_login',
            'default_email' => 'test@yandex.ru',
            'display_name'  => 'mock_displayname',
            'first_name'    => 'mock_firstname',
            'last_name'     => 'mock_lastname',
        ]);

        $this->mockHttpClient([
            new Response(200, ['Content-Type' => 'application/json'], $responseBody),
        ]);

        $token = new AccessToken(['access_token' => 'mock_access_token']);
        $resource = $this->provider->getResourceOwner($token);

        $this->assertInstanceOf(YandexResourceOwner::class, $resource);
        $this->assertEquals('1000034426', $resource->getId());
        $this->assertEquals('mock_login', $resource->getNickname());
        $this->assertEquals('test@yandex.ru', $resource->getEmail());
        $this->assertEquals('mock_displayname', $resource->getName());
        $this->assertEquals('mock_firstname', $resource->getFirstName());
        $this->assertEquals('mock_lastname', $resource->getLastName());
    }

    /**
     * Test getResourceOwner() throws on a bare {"error": ...} response
     *
     * @return void
     */
    public function testGetResourceOwnerThrowsOnError()
    {
        $this->mockHttpClient([
            new Response(401, ['Content-Type' => 'application/json'], '{"error":"invalid_token"}'),
        ]);

        try {
            $this->provider->getResourceOwner(new AccessToken(['access_token' => 'expired_token']));
        } catch (IdentityProviderException $e) {
            $this->assertEquals('invalid_token', $e->getMessage());
            $this->assertEquals(401, $e->getCode());
            $this->assertEquals('invalid_token', $e->getResponseBody()['error']);

            return;
        }

        $this->fail('IdentityProviderException was not thrown');
    }
}
