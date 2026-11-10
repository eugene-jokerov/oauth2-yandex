<?php

namespace Jokerov\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Yandex ID resource owner.
 *
 * @see https://yandex.ru/dev/id/doc/ru/user-information
 */
class YandexResourceOwner implements ResourceOwnerInterface
{
    /**
     * Response
     *
     * @var array
     */
    private $response;

    /**
     * Class constructor
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * User's ID from Yandex
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->getResponseValue('id');
    }

    /**
     * User's login (nickname)
     *
     * @return string|null
     */
    public function getNickname(): ?string
    {
        return $this->getResponseValue('login');
    }

    /**
     * Login used before the introduction of the unified Yandex login
     *
     * @return string|null
     */
    public function getOldSocialLogin(): ?string
    {
        return $this->getResponseValue('old_social_login');
    }

    /**
     * User's display name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getResponseValue('display_name');
    }

    /**
     * User's first name
     *
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->getResponseValue('first_name');
    }

    /**
     * User's last name
     *
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->getResponseValue('last_name');
    }

    /**
     * User's real name (first name, patronymic and last name)
     *
     * @return string|null
     */
    public function getRealName(): ?string
    {
        return $this->getResponseValue('real_name');
    }

    /**
     * User's default email address
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getResponseValue('default_email');
    }

    /**
     * Alias of getEmail()
     *
     * @return string|null
     */
    public function getDefaultEmail(): ?string
    {
        return $this->getEmail();
    }

    /**
     * All email addresses of the user
     *
     * @return string[]
     */
    public function getEmails(): array
    {
        $emails = $this->getResponseValue('emails');

        return is_array($emails) ? array_values(array_filter($emails, 'is_string')) : [];
    }

    /**
     * Whether the email address is verified
     *
     * @return bool|null
     */
    public function isEmailVerified(): ?bool
    {
        return $this->getBooleanValue('is_email_verified');
    }

    /**
     * User's gender: "male" or "female"
     *
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->getResponseValue('sex');
    }

    /**
     * User's birthday in YYYY-MM-DD format
     *
     * @return string|null
     */
    public function getBirthday(): ?string
    {
        return $this->getResponseValue('birthday');
    }

    /**
     * User's phone number
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->getResponseValue('default_phone');
    }

    /**
     * Whether the phone number is verified
     *
     * @return bool|null
     */
    public function isPhoneVerified(): ?bool
    {
        return $this->getBooleanValue('is_phone_verified');
    }

    /**
     * Identifier of the default avatar
     *
     * @return string|null
     */
    public function getDefaultAvatarId(): ?string
    {
        return $this->getResponseValue('default_avatar_id');
    }

    /**
     * URL of the default avatar, for example
     * https://avatars.yandex.net/get-yapic/131652443/default
     *
     * @return string|null
     */
    public function getAvatarUrl(): ?string
    {
        $avatarId = $this->getDefaultAvatarId();

        return null === $avatarId || '' === $avatarId
            ? null
            : 'https://avatars.yandex.net/get-yapic/' . $avatarId . '/default';
    }

    /**
     * All data returned by Yandex
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    private function getResponseValue(string $key)
    {
        return $this->response[$key] ?? null;
    }

    /**
     * @param string $key
     *
     * @return bool|null
     */
    private function getBooleanValue(string $key): ?bool
    {
        $value = $this->getResponseValue($key);

        return is_bool($value) ? $value : null;
    }
}
