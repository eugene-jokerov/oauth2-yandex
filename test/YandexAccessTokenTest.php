<?php

namespace Jokerov\OAuth2\Client\Test\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Jokerov\OAuth2\Client\Provider\Yandex;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\TestCase;

class YandexAccessTokenTest extends TestCase
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
     * Test access token request
     *
     * @return void
     */
    public function testGetAccessToken()
    {
        $this->mockHttpClient([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"access_token":"mock_access_token","token_type":"bearer","expires_in":3600}'
            ),
        ]);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertGreaterThan(time(), $token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    /**
     * Test refresh token grant
     *
     * @return void
     */
    public function testRefreshToken()
    {
        $this->mockHttpClient([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"access_token":"mock_refreshed_token","token_type":"bearer","expires_in":3600,'
                . '"refresh_token":"mock_refresh_token"}'
            ),
        ]);

        $token = $this->provider->getAccessToken('refresh_token', ['refresh_token' => 'mock_refresh_token']);

        $this->assertEquals('mock_refreshed_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
    }

    /**
     * Test the IdentityProviderException on a failed token request
     *
     * @return void
     */
    public function testThrowExceptionWhenAccessTokenFails()
    {
        $this->mockHttpClient([
            new Response(
                400,
                ['Content-Type' => 'application/json'],
                '{"error_description":"Error message","error":"bad_verification_code"}'
            ),
        ]);

        try {
            $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        } catch (IdentityProviderException $e) {
            $this->assertEquals('bad_verification_code: Error message', $e->getMessage());
            // IdentityProviderException extends \Exception, whose code is int,
            // so the HTTP status is used. The Yandex error code is available
            // in getResponseBody()['error'].
            $this->assertEquals(400, $e->getCode());
            $this->assertEquals(
                ['error_description' => 'Error message', 'error' => 'bad_verification_code'],
                $e->getResponseBody()
            );
            $this->assertEquals('bad_verification_code', $e->getResponseBody()['error']);

            return;
        }

        $this->fail('IdentityProviderException was not thrown');
    }

    /**
     * Test the IdentityProviderException when the error has no description
     *
     * @return void
     */
    public function testThrowExceptionWhenErrorDescriptionIsMissing()
    {
        $this->mockHttpClient([
            new Response(400, ['Content-Type' => 'application/json'], '{"error":"invalid_request"}'),
        ]);

        try {
            $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        } catch (IdentityProviderException $e) {
            $this->assertEquals('invalid_request', $e->getMessage());
            $this->assertEquals(400, $e->getCode());
            $this->assertEquals('invalid_request', $e->getResponseBody()['error']);

            return;
        }

        $this->fail('IdentityProviderException was not thrown');
    }

    /**
     * Test the IdentityProviderException on a broken (non JSON) response
     *
     * @return void
     */
    public function testThrowExceptionOnInvalidResponseBody()
    {
        $this->mockHttpClient([
            new Response(502, ['Content-Type' => 'text/html'], '<html>Bad Gateway</html>'),
        ]);

        try {
            $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        } catch (IdentityProviderException $e) {
            $this->assertEquals(502, $e->getCode());

            return;
        }

        $this->fail('IdentityProviderException was not thrown');
    }
}
