<?php

namespace Jokerov\OAuth2\Client\Test\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Jokerov\OAuth2\Client\Provider\Yandex;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\TestCase;

class YandexTest extends TestCase
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
     * Tear down this unit test
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->provider = null;
    }

    /**
     * Test base token url
     *
     * @return void
     */
    public function testGetBaseAccessTokenUrl()
    {
        $this->assertEquals('https://oauth.yandex.ru/token', $this->provider->getBaseAccessTokenUrl([]));
    }

    /**
     * Test authorization url request
     *
     * @return void
     */
    public function testGetAuthorizationUrl()
    {
        $uri = parse_url($this->provider->getAuthorizationUrl());
        parse_str($uri['query'], $query);

        $this->assertEquals('oauth.yandex.ru', $uri['host']);
        $this->assertEquals('/authorize', $uri['path']);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertEquals('code', $query['response_type']);
        $this->assertEquals('mock_client_id', $query['client_id']);
        $this->assertEquals('https://example.com/redirect', $query['redirect_uri']);

        $this->assertNotNull($this->provider->getState());
    }

    /**
     * Test the email scope is requested by default.
     *
     * Чтобы Яндекс вернул email пользователя, нужно запросить login:email.
     *
     * @return void
     */
    public function testDefaultScopeContainsEmail()
    {
        $uri = parse_url($this->provider->getAuthorizationUrl());
        parse_str($uri['query'], $query);

        $this->assertEquals('login:email', $query['scope']);
    }

    /**
     * Test the passed scopes are appended to the default ones
     *
     * @return void
     */
    public function testGetAuthorizationUrlWithCustomScope()
    {
        $uri = parse_url($this->provider->getAuthorizationUrl(['scope' => ['login:birthday', 'login:info']]));
        parse_str($uri['query'], $query);

        $this->assertEquals('login:email login:birthday login:info', $query['scope']);
    }
}
