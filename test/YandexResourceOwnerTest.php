<?php

namespace Jokerov\OAuth2\Client\Test\Provider;

use Jokerov\OAuth2\Client\Provider\YandexResourceOwner;
use PHPUnit\Framework\TestCase;

class YandexResourceOwnerTest extends TestCase
{
    /**
     * Full response from Yandex
     *
     * @var array
     */
    protected $response;

    /**
     * Setup this unit test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->response = json_decode(
            '{"first_name":"mock_firstname","last_name":"mock_lastname","display_name":"mock_displayname",'
            . '"emails":["test@yandex.ru","other-test@yandex.ru"],"default_email":"test@yandex.ru",'
            . '"real_name":"Vasya Pupkin","birthday":"1987-03-12","default_avatar_id":"131652443",'
            . '"login":"mock_login","old_social_login":"uid-mmzxrnry","sex":"male","id":"1000034426",'
            . '"is_email_verified":true,"default_phone":{"id":1,"number":"+79001234567"}}',
            true
        );

        parent::setUp();
    }

    /**
     * Tear down this unit test
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->response = null;
    }

    /**
     * Test all getters on a full response
     *
     * @return void
     */
    public function testGetters()
    {
        $resource = new YandexResourceOwner($this->response);

        $this->assertInstanceOf(YandexResourceOwner::class, $resource);

        $this->assertSame('1000034426', $resource->getId());
        $this->assertSame('mock_login', $resource->getNickname());
        $this->assertSame('uid-mmzxrnry', $resource->getOldSocialLogin());
        $this->assertSame('mock_displayname', $resource->getName());
        $this->assertSame('mock_firstname', $resource->getFirstName());
        $this->assertSame('mock_lastname', $resource->getLastName());
        $this->assertSame('Vasya Pupkin', $resource->getRealName());
        $this->assertSame('test@yandex.ru', $resource->getEmail());
        $this->assertSame('test@yandex.ru', $resource->getDefaultEmail());
        $this->assertSame(['test@yandex.ru', 'other-test@yandex.ru'], $resource->getEmails());
        $this->assertTrue($resource->isEmailVerified());
        $this->assertSame('male', $resource->getGender());
        $this->assertSame('1987-03-12', $resource->getBirthday());
        $this->assertSame('131652443', $resource->getDefaultAvatarId());
        $this->assertSame('https://avatars.yandex.net/get-yapic/131652443/default', $resource->getAvatarUrl());
        $this->assertSame($this->response, $resource->toArray());
    }

    /**
     * Test getters return null instead of raising notices on a shortcut response
     * (login:email only, which is what Yandex returns by default)
     *
     * @return void
     */
    public function testGettersWithPartialResponse()
    {
        $resource = new YandexResourceOwner(['id' => '1000034426', 'default_email' => 'test@yandex.ru']);

        $this->assertSame('1000034426', $resource->getId());
        $this->assertSame('test@yandex.ru', $resource->getEmail());
        $this->assertNull($resource->getNickname());
        $this->assertNull($resource->getName());
        $this->assertNull($resource->getFirstName());
        $this->assertNull($resource->getLastName());
        $this->assertNull($resource->getRealName());
        $this->assertNull($resource->getGender());
        $this->assertNull($resource->getBirthday());
        $this->assertSame([], $resource->getEmails());
        $this->assertNull($resource->isEmailVerified());
        $this->assertNull($resource->getDefaultAvatarId());
        $this->assertNull($resource->getAvatarUrl());
    }

    /**
     * Test all getters are null-safe on an empty response
     *
     * @return void
     */
    public function testGettersWithEmptyResponse()
    {
        $resource = new YandexResourceOwner([]);

        $this->assertNull($resource->getId());
        $this->assertNull($resource->getNickname());
        $this->assertNull($resource->getEmail());
        $this->assertNull($resource->getName());
        $this->assertNull($resource->getFirstName());
        $this->assertNull($resource->getLastName());
        $this->assertNull($resource->getGender());
        $this->assertNull($resource->getOldSocialLogin());
        $this->assertNull($resource->getRealName());
        $this->assertNull($resource->getDefaultEmail());
        $this->assertSame([], $resource->getEmails());
        $this->assertNull($resource->isEmailVerified());
        $this->assertNull($resource->getBirthday());
        $this->assertNull($resource->getPhone());
        $this->assertNull($resource->isPhoneVerified());
        $this->assertNull($resource->getDefaultAvatarId());
        $this->assertNull($resource->getAvatarUrl());
        $this->assertSame([], $resource->toArray());
    }

    /**
     * Test "0" and 0 values are not converted to null
     *
     * @return void
     */
    public function testGettersAreNullCoalesceNotFalsy()
    {
        $resource = new YandexResourceOwner(['id' => 0, 'login' => '0', 'sex' => '0']);

        $this->assertSame('0', $resource->getId());
        $this->assertSame('0', $resource->getNickname());
        $this->assertSame('0', $resource->getGender());
    }
}
