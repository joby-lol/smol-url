<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserWithoutPassword()
    {
        $user = new User('user', null);
        $this->assertEquals('user', (string)$user);
        $this->assertEquals('user', $user->username);
        $this->assertNull($user->password);
        $user = new User('user', '');
        $this->assertEquals('user', (string)$user);
        $this->assertEquals('user', $user->username);
        $this->assertNull($user->password);
    }

    public function testUserWithPassword()
    {
        $user = new User('user', 'password');
        $this->assertEquals('user:password', (string)$user);
        $this->assertEquals('user', $user->username);
        $this->assertEquals('password', $user->password);
    }

    public function testURLEncoding()
    {
        $user = new User('user with spaces', null);
        $this->assertEquals('user%20with%20spaces', (string)$user);
        $this->assertEquals('user with spaces', $user->username);
        $this->assertNull($user->password);
        $user = new User('user with spaces', 'password with spaces');
        $this->assertEquals('user%20with%20spaces:password%20with%20spaces', (string)$user);
        $this->assertEquals('user with spaces', $user->username);
        $this->assertEquals('password with spaces', $user->password);
    }
}
