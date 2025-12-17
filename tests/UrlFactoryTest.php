<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;

use PHPUnit\Framework\TestCase;

class UrlFactoryTest extends TestCase
{
    public function testBaseUrlUsesProvidedValue()
    {
        $base = new URL(
            Path::fromString('/'),
            null,
            null,
            Scheme::HTTPS,
            new User('user', 'pass'),
            new Host('example.com'),
            new Port(8443),
        );
        $factory = new UrlFactory($base);
        $this->assertSame($base, $factory->baseUrl());
    }

    public function testFromUrlFillsMissingPartsFromBase()
    {
        $base = new URL(
            Path::fromString('/'),
            null,
            null,
            Scheme::HTTPS,
            new User('baseuser', 'basepass'),
            new Host('example.com'),
            new Port(443),
        );
        $factory = new UrlFactory($base);
        $url = new URL(
            Path::fromString('/path'),
            new Query(['a' => '1']),
            new Fragment('frag'),
        );
        $result = $factory->fromUrl($url);
        $this->assertEquals('https://baseuser:basepass@example.com/path?a=1#frag', (string)$result);
        $this->assertSame($url->path, $result->path);
        $this->assertSame($url->query, $result->query);
        $this->assertSame($url->fragment, $result->fragment);
    }

    public function testFromStringParsesAbsoluteUrl()
    {
        $factory = new UrlFactory(new URL());
        $result = $factory->fromString('https://user:pass@example.com:8080/path?arg=value#frag');
        $this->assertEquals('https://user:pass@example.com:8080/path?arg=value#frag', (string)$result);
        $this->assertEquals(Scheme::HTTPS, $result->scheme);
        $this->assertEquals('user:pass', (string)$result->user);
        $this->assertEquals('example.com', (string)$result->host);
        $this->assertEquals('8080', (string)$result->port);
        $this->assertEquals('arg=value', (string)$result->query);
        $this->assertEquals('frag', (string)$result->fragment);
    }

    public function testFromStringParsesRelativeUrl()
    {
        $factory = new UrlFactory(new URL());
        $result = $factory->fromString('/dir/file?foo=bar#frag');
        $this->assertEquals('/dir/file?foo=bar#frag', (string)$result);
        $this->assertNull($result->scheme);
        $this->assertNull($result->host);
        $this->assertNull($result->user);
        $this->assertNull($result->port);
    }

    public function testFromStringRelativeUsesBaseUrlWhenConverted()
    {
        $base = new URL(
            Path::fromString('/'),
            null,
            null,
            Scheme::HTTPS,
            new User('user', 'pass'),
            new Host('example.com'),
            new Port(8443),
        );
        $factory = new UrlFactory($base);
        $relative = $factory->fromString('/dir/file?foo=bar#frag');
        $result = $factory->fromUrl($relative);
        $this->assertEquals('https://user:pass@example.com:8443/dir/file?foo=bar#frag', (string)$result);
        $this->assertSame($relative->path, $result->path);
        $this->assertSame($relative->query, $result->query);
        $this->assertSame($relative->fragment, $result->fragment);
    }

    public function testFromStringRejectsInvalidScheme()
    {
        $factory = new UrlFactory(new URL());
        $this->expectException(URLException::class);
        $factory->fromString('ftp://example.com/path');
    }

    public function testFromGlobalsUsesServerSuperglobalsAndBase()
    {
        $server_backup = $_SERVER;
        $get_backup = $_GET;
        $_SERVER['REQUEST_URI'] = '/dir/file?ignored=1';
        $_GET = ['q' => '1', 'z' => '2'];
        $base = new URL(
            Path::fromString('/'),
            null,
            null,
            Scheme::HTTPS,
            new User('user', 'pass'),
            new Host('example.com'),
            new Port(444),
        );
        $factory = new UrlFactory($base);
        try {
            $result = $factory->fromGlobals();
            $this->assertEquals('https://user:pass@example.com:444/dir/file?q=1&z=2', (string)$result);
            $this->assertEquals($base->scheme, $result->scheme);
            $this->assertEquals($base->host, $result->host);
            $this->assertEquals($base->user, $result->user);
            $this->assertEquals($base->port, $result->port);
        } finally {
            $_SERVER = $server_backup;
            $_GET = $get_backup;
        }
    }

    public function testGeneratesBaseUrlFromServerVars()
    {
        $server_backup = $_SERVER;
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_PORT'] = 8080;
        $_SERVER['HTTPS'] = 'on';
        try {
            $factory = new UrlFactory();
            $this->assertEquals('https://example.com:8080/', (string)$factory->baseUrl());
        } finally {
            $_SERVER = $server_backup;
        }
    }
}
