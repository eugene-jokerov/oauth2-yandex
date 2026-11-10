<?php

namespace Jokerov\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

/**
 * Yandex ID OAuth2 provider for league/oauth2-client.
 *
 * @see https://yandex.ru/dev/id/doc/ru/
 */
class Yandex extends AbstractProvider
{
    /**
     * Authorization endpoint.
     */
    public const AUTHORIZATION_URL = 'https://oauth.yandex.ru/authorize';

    /**
     * Access token endpoint.
     */
    public const ACCESS_TOKEN_URL = 'https://oauth.yandex.ru/token';

    /**
     * User info endpoint.
     */
    public const RESOURCE_OWNER_DETAILS_URL = 'https://login.yandex.ru/info';

    /**
     * {@inheritdoc}
     */
    public function getBaseAuthorizationUrl(): string
    {
        return self::AUTHORIZATION_URL;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return self::ACCESS_TOKEN_URL;
    }

    /**
     * {@inheritdoc}
     *
     * The access token is sent in the "Authorization: Bearer" header by
     * AbstractProvider::getResourceOwner(), so the token never leaks into
     * proxy or web server access logs via the query string.
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return self::RESOURCE_OWNER_DETAILS_URL . '?' . http_build_query(['format' => 'json']);
    }

    /**
     * {@inheritdoc}
     *
     * "login:email" is required for login.yandex.ru/info to return the user
     * email address. Additional scopes may be passed to getAuthorizationUrl().
     */
    protected function getDefaultScopes(): array
    {
        return ['login:email'];
    }

    /**
     * {@inheritdoc}
     *
     * Yandex separates multiple scopes with a space.
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    /**
     * {@inheritdoc}
     *
     * Extra scopes are appended to the default ones, so "login:email" is never
     * lost when the caller passes custom scopes.
     */
    protected function getAuthorizationParameters(array $options): array
    {
        if (isset($options['scope']) && !is_array($options['scope'])) {
            $options['scope'] = preg_split('/[\s,]+/', (string) $options['scope'], -1, PREG_SPLIT_NO_EMPTY);
        }

        $options['scope'] = array_values($options['scope'] ?? []);
        $options['scope'] = array_unique(array_merge($this->getDefaultScopes(), $options['scope']));

        return parent::getAuthorizationParameters($options);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $data
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (!is_array($data)) {
            if ($response->getStatusCode() >= 400) {
                throw new IdentityProviderException(
                    'Unexpected response from Yandex',
                    $response->getStatusCode(),
                    $response
                );
            }

            return;
        }

        if (!$this->hasError($data)) {
            return;
        }

        $message = $this->getErrorMessage($data);

        throw new IdentityProviderException($message, $response->getStatusCode(), $data);
    }

    /**
     * Builds a human readable error message.
     *
     * @param array $data
     */
    private function getErrorMessage(array $data): string
    {
        $message = $data['error_description'] ?? null;

        if (!is_string($message) || '' === $message) {
            $message = is_string($data['error']) ? $data['error'] : 'Unknown Yandex error';
        }

        if (isset($data['error']) && is_string($data['error']) && $message !== $data['error']) {
            $message = $data['error'] . ': ' . $message;
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new YandexResourceOwner($response);
    }

    /**
     * Detects Yandex error payloads.
     *
     * The token endpoint returns {"error": "...", "error_description": "..."},
     * while login.yandex.ru/info may answer a bare {"error": "..."}.
     *
     * @param array $data
     */
    private function hasError(array $data): bool
    {
        if (!isset($data['error'])) {
            return false;
        }

        $error = $data['error'];

        // Some Yandex responses use boolean flags named "error".
        if (is_bool($error)) {
            return $error;
        }

        return is_string($error) && '' !== $error;
    }
}
