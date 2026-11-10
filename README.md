# Yandex OAuth2 client provider

[![Latest Version](https://img.shields.io/packagist/v/jokerov/oauth2-yandex.svg)](https://packagist.org/packages/jokerov/oauth2-yandex)
[![License](https://img.shields.io/packagist/l/jokerov/oauth2-yandex.svg)](https://packagist.org/packages/jokerov/oauth2-yandex)

This package provides [Yandex ID](https://yandex.ru/dev/id/doc/ru/) integration for
[OAuth2 Client](https://github.com/thephpleague/oauth2-client) by the League.

It is a fork of [aego/oauth2-yandex](https://github.com/rakeev/oauth2-yandex) (abandoned since 2018)
with the fixed bugs listed below.

## Installation

```sh
composer require jokerov/oauth2-yandex
```

## Usage

```php
$provider = new Jokerov\OAuth2\Client\Provider\Yandex([
    'clientId'     => 'b80bb7740288fda1f201890375a60c8f',
    'clientSecret' => 'f23ccd066f8236c6f97a2a62d3f9f9f5',
    'redirectUri'  => 'https://example.org/oauth-endpoint',
]);

$authorizationUrl = $provider->getAuthorizationUrl();
// store the state in the session to validate it on the callback
$_SESSION['oauth2state'] = $provider->getState();

header('Location: ' . $authorizationUrl);
exit;
```

Callback handling:

```php
try {
    $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
    exit($e->getMessage());
}

/** @var YandexResourceOwner $owner */
$owner = $provider->getResourceOwner($token);

$owner->getId();          // string|null — "1000034426"
$owner->getEmail();       // string|null — "user@yandex.ru"
$owner->getNickname();    // string|null — login
$owner->getName();        // string|null — display name
$owner->getFirstName();   // string|null
$owner->getLastName();    // string|null
$owner->getRealName();    // string|null
$owner->getGender();      // string|null — "male" or "female"
$owner->getBirthday();    // string|null — YYYY-MM-DD
$owner->getEmails();      // string[]
$owner->getAvatarUrl();   // string|null
$owner->toArray();        // raw Yandex response
```

### Scopes

`login:email` is requested by default, so Yandex returns the user's email address.
Extra scopes are appended to the defaults:

```php
$provider->getAuthorizationUrl(['scope' => ['login:birthday', 'login:portrait']]);
```

### Refresh token

By default Yandex issues tokens valid for about one year and no refresh token.
Request `offline_access` explicitly if a refresh token is needed:

```php
$provider->getAuthorizationUrl(['scope' => ['offline_access']]);

$newToken = $provider->getAccessToken('refresh_token', [
    'refresh_token' => $token->getRefreshToken(),
]);
```

## Differences from aego/oauth2-yandex

| # | Problem in 0.2.2 | Fix |
|---|---|---|
| 1 | `YandexResourceOwner` raised `Undefined array key` warnings for every missing field (`$this->response['id'] ?: null`) | all getters are null-safe (`?? null`) and return `?string` |
| 2 | `?:` turned valid `"0"` values into `null` | `??` / explicit type checks |
| 3 | No error handling for non-JSON or empty bodies (`$data['error_description']` read unconditionally) | `checkResponse()` handles non-array payloads and missing description |
| 4 | `IdentityProviderException` carried the HTTP status (400) instead of the Yandex error code | error message now includes the Yandex `error` code, `getResponseBody()` returns the parsed array (the exception code stays the HTTP status, as `\Exception::$code` is `int`) |
| 5 | The access token was passed in the query string (`?oauth_token=`), leaking into access logs | token is sent via `Authorization: Bearer` header |
| 6 | `getDefaultScopes()` was empty, so email was often missing | `login:email` is requested by default and custom scopes are appended to it (space separated, as Yandex expects) |
| 7 | Tests were written for PHPUnit ~4.3 (`PHPUnit_Framework_TestCase`, `@expectedException`) and did not run on modern PHP | PHPUnit 9/10/11 tests with `GuzzleHttp\Handler\MockHandler` |
| 8 | PHP `>=5.5`, no `support`/`keywords` in `composer.json` | PHP `^7.4 \|\| ^8.0`, `league/oauth2-client ^2.6`, complete metadata |

Not covered by `aego/oauth2-yandex` and added in this fork: `getOldSocialLogin()`,
`getRealName()`, `getEmails()`, `isEmailVerified()`, `getBirthday()`, `getPhone()`,
`isPhoneVerified()`, `getDefaultAvatarId()`, `getAvatarUrl()`, `getDefaultEmail()`.

## License

MIT. See [LICENSE](LICENSE).
