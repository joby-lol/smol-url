<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;

use PHPUnit\Framework\TestCase;

class URLTest extends TestCase
{
    public function testEmptyURL()
    {
        // full defaults should be an absolute root path
        $url = new URL();
        $this->assertEquals('/', (string)$url);
        // defaults with a relative Path should be a relative root path
        $url = new URL(new Path(absolute: false));
        $this->assertEquals('./', (string)$url);
    }

    public function testNormalizingEmptyAbsolutePathToSlash()
    {
        // if a path implementation returns a blank path that is marked as absolute, it should be normalized to a slash
        $url = new URL(new Path(absolute: true));
        $this->assertEquals('/', (string)$url);
        // this should also be the case when there are host/authority parts in front
        $url = new URL(
            new Path(absolute: true),
            host: $this->mockHost('example.com'),
        );
        $this->assertEquals('//example.com/', (string)$url);
    }

    public function testRelativePathWithHost()
    {
        // when a path is relative, the host should not be included in the string, even if it is present
        $url = new URL(
            new Path(filename: 'path', absolute: false),
            scheme: Scheme::HTTP,
            host: $this->mockHost('example.com'),
        );
        $this->assertEquals('path', (string)$url);
        // relative empty paths should be normalized to './' in this case
        $url = new URL(
            new Path(absolute: false),
            scheme: Scheme::HTTP,
            host: $this->mockHost('example.com'),
        );
        $this->assertEquals('./', (string)$url);
        // this should also be the case even if the scheme, port, and user are present, and it should include the query and fragment too
        $url = new URL(
            new Path(filename: 'path', absolute: false),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTP,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(8080),
        );
        $this->assertEquals('path?arg=value#fragment', (string)$url);
    }

    public function testFullURL()
    {
        // if all parts are specified, they should be included in the string
        $url = new URL(
            new Path(filename: 'path', absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTPS,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(8080),
        );
        $this->assertEquals('https://user:pass@example.com:8080/path?arg=value#fragment', (string)$url);
    }

    public function testPortInclusionRules()
    {
        // HTTP port should not be included if it is the default port
        $url = new URL(
            new Path(filename: 'path', absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTP,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(80),
        );
        $this->assertEquals('http://user:pass@example.com/path?arg=value#fragment', (string)$url);
        // HTTPS port should not be included if it is the default port
        $url = new URL(
            new Path(filename: 'path', absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTPS,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(443),

        );
        $this->assertEquals('https://user:pass@example.com/path?arg=value#fragment', (string)$url);
        // default HTTPS and HTTP ports should not be included if the scheme is empty
        $url = new URL(
            new Path(absolute: true),
            host: $this->mockHost('example.com'),
            port: $this->mockPort(443),
        );
        $this->assertEquals('//example.com/', (string)$url);
        $url = new URL(
            new Path(absolute: true),
            host: $this->mockHost('example.com'),
            port: $this->mockPort(80),
        );
        $this->assertEquals('//example.com/', (string)$url);
        // but other ports should be included for empty scheme
        $url = new URL(
            new Path(absolute: true),
            host: $this->mockHost('example.com'),
            port: $this->mockPort(1234),
        );
        $this->assertEquals('//example.com:1234/', (string)$url);
    }

    public function testWithScheme()
    {
        $url = new URL(
            new Path(absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTP,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(8080),
        );
        $https = $url->withScheme(Scheme::HTTPS);
        $none = $url->withScheme(null);
        $this->assertEquals(Scheme::HTTPS, $https->scheme);
        $this->assertNull($none->scheme);
        // original URL should still be http
        $this->assertEquals(Scheme::HTTP, $url->scheme);
        // everything else should also still match
        $this->assertEquals($url->user, $https->user);
        $this->assertEquals($url->fragment, $https->fragment);
        // if we call withScheme with the same scheme it should return the same object
        $same = $url->withScheme(Scheme::HTTP);
        $this->assertSame($url, $same);
    }

    public function testWithUser()
    {
        $url = new URL(
            new Path(absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTP,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(8080),
        );
        $changed = $url->withUser($this->mockUser('newuser', 'newpass'));
        $removed = $url->withUser(null);
        $unchanged = $url->withUser($url->user);
        // original URL should still be user:pass, others should be updated
        $this->assertEquals('user:pass', (string)$url->user);
        $this->assertEquals('newuser:newpass', (string)$changed->user);
        $this->assertNull($removed->user);
        // unchanged should be the same object
        $this->assertSame($url, $unchanged);
        // everything else should be unchanged
        $this->assertEquals($url->scheme, $changed->scheme);
        $this->assertEquals($url->host, $changed->host);
        $this->assertEquals($url->port, $changed->port);
        $this->assertEquals($url->path, $changed->path);
        $this->assertEquals($url->query, $changed->query);
        $this->assertEquals($url->fragment, $changed->fragment);
    }

    public function testWithHost()
    {
        $url = new URL(
            new Path(absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTP,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(8080),
        );
        $changed = $url->withHost($this->mockHost('changed'));
        $removed = $url->withHost(null);
        $unchanged = $url->withHost($url->host);
        // original URL should still be example.com, others should be updated
        $this->assertEquals('example.com', (string)$url->host);
        $this->assertEquals('changed', (string)$changed->host);
        $this->assertNull($removed->host);
        // unchanged should be the same object
        $this->assertSame($url, $unchanged);
        // everything else should be unchanged
        $this->assertEquals($url->scheme, $changed->scheme);
        $this->assertEquals($url->port, $changed->port);
        $this->assertEquals($url->path, $changed->path);
        $this->assertEquals($url->query, $changed->query);
        $this->assertEquals($url->fragment, $changed->fragment);
    }

    public function testWithPort()
    {
        $url = new URL(
            new Path(absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTP,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(8080),
        );
        $changed = $url->withPort($this->mockPort(8081));
        $removed = $url->withPort(null);
        $unchanged = $url->withPort($url->port);
        // original URL should still be 8080, others should be updated
        $this->assertEquals('8080', (string)$url->port);
        $this->assertEquals('8081', (string)$changed->port);
        $this->assertNull($removed->port);
        // unchanged should be the same object
        $this->assertSame($url, $unchanged);
        // everything else should be unchanged
        $this->assertEquals($url->scheme, $changed->scheme);
        $this->assertEquals($url->host, $changed->host);
        $this->assertEquals($url->path, $changed->path);
        $this->assertEquals($url->query, $changed->query);
        $this->assertEquals($url->fragment, $changed->fragment);
    }

    public function testWithPath()
    {
        $url = new URL(
            new Path(absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTP,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(8080),
        );
        $changed = $url->withPath(new Path(filename: 'newpath', absolute: true));
        $unchanged = $url->withPath($url->path);
        // original URL should still be /, others should be updated
        $this->assertEquals('', (string)$url->path);
        $this->assertEquals('newpath', (string)$changed->path);
        // unchanged should be the same object
        $this->assertSame($url, $unchanged);
        // everything else should be unchanged
        $this->assertEquals($url->scheme, $changed->scheme);
        $this->assertEquals($url->host, $changed->host);
        $this->assertEquals($url->port, $changed->port);
        $this->assertEquals($url->query, $changed->query);
        $this->assertEquals($url->fragment, $changed->fragment);
        $this->assertEquals($url->user, $changed->user);
    }

    public function testWithFragment()
    {
        $url = new URL(
            new Path(absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTP,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(8080),
        );
        $changed = $url->withFragment($this->mockFragment('newfragment'));
        $removed = $url->withFragment(null);
        $unchanged = $url->withFragment($url->fragment);
        // original URL should still be #fragment, others should be updated
        $this->assertEquals('fragment', (string)$url->fragment);
        $this->assertEquals('newfragment', (string)$changed->fragment);
        $this->assertNull($removed->fragment);
        // unchanged should be the same object
        $this->assertSame($url, $unchanged);
        // everything else should be unchanged
        $this->assertEquals($url->scheme, $changed->scheme);
        $this->assertEquals($url->host, $changed->host);
        $this->assertEquals($url->port, $changed->port);
        $this->assertEquals($url->path, $changed->path);
        $this->assertEquals($url->query, $changed->query);
        $this->assertEquals($url->user, $changed->user);
    }

    public function testWithQuery()
    {
        $url = new URL(
            new Path(absolute: true),
            $this->mockQuery(['arg' => 'value']),
            $this->mockFragment('fragment'),
            Scheme::HTTP,
            $this->mockUser('user', 'pass'),
            $this->mockHost('example.com'),
            $this->mockPort(8080),
        );
        $changed = $url->withQuery($this->mockQuery(['arg2' => 'value2']));
        $removed = $url->withQuery(null);
        $unchanged = $url->withQuery($url->query);
        // original URL should still be arg=value, others should be updated
        $this->assertEquals('arg=value', (string)$url->query);
        $this->assertEquals('arg2=value2', (string)$changed->query);
        $this->assertNull($removed->query);
        // unchanged should be the same object
        $this->assertSame($url, $unchanged);
        // everything else should be unchanged
        $this->assertEquals($url->scheme, $changed->scheme);
        $this->assertEquals($url->host, $changed->host);
        $this->assertEquals($url->port, $changed->port);
        $this->assertEquals($url->path, $changed->path);
        $this->assertEquals($url->user, $changed->user);
        $this->assertEquals($url->fragment, $changed->fragment);
    }

    public function testWithLinkStringApplied()
    {
        $url = new URL(
            Path::fromString('/d1/d2/filename'),
            new Query(['a1' => 'v1']),
            new Fragment('f1'),
        );
        // apply a link with just a filename
        $this->assertEquals(
            '/d1/d2/filename2',
            (string)$url->withLinkStringApplied('filename2'),
        );
        // apply a link with just a full query
        $this->assertEquals(
            '/d1/d2/filename?a2=v2',
            (string)$url->withLinkStringApplied('?a2=v2'),
        );
        // apply a link with a non-overriding partial query
        $this->assertEquals(
            '/d1/d2/filename?a1=v1&a2=v2',
            (string)$url->withLinkStringApplied('&a2=v2'),
        );
        // apply a link with an overriding partial query
        $this->assertEquals(
            '/d1/d2/filename?a1=v3&a2=v2',
            (string)$url->withLinkStringApplied('&a1=v3&a2=v2'),
        );
        // apply a link with just a fragment
        $this->assertEquals(
            '/d1/d2/filename#f2',
            (string)$url->withLinkStringApplied('#f2'),
        );
        // apply ./
        $this->assertEquals(
            '/d1/d2/',
            (string)$url->withLinkStringApplied('./'),
        );
        // apply ./filename2
        $this->assertEquals(
            '/d1/d2/filename2',
            (string)$url->withLinkStringApplied('./filename2'),
        );
        // apply ../
        $this->assertEquals(
            '/d1/',
            (string)$url->withLinkStringApplied('../'),
        );
        // apply ../filename2
        $this->assertEquals(
            '/d1/filename2',
            (string)$url->withLinkStringApplied('../filename2'),
        );
        // apply with all parts and full query
        $this->assertEquals(
            '/d1/d2/d3/filename2?a2=v2#f2',
            (string)$url->withLinkStringApplied('d3/filename2?a2=v2#f2'),
        );
        // apply with all parts and partial query
        $this->assertEquals(
            '/d1/d2/d3/filename2?a1=v3&a2=v2#f2',
            (string)$url->withLinkStringApplied('d3/filename2?a1=v3&a2=v2#f2'),
        );
        // apply with path and fragment only
        $this->assertEquals(
            '/d1/d2/d3/filename2#f2',
            (string)$url->withLinkStringApplied('./d3/filename2#f2'),
        );
        // apply with absolute path
        $this->assertEquals(
            '/d4/filename2',
            (string)$url->withLinkStringApplied('/d4/filename2'),
        );
    }

    protected function mockFragment(string|null $fragment = null): Fragment
    {
        $mock = $this->createMock(Fragment::class);
        $mock->method('__toString')->willReturn($fragment ?? '');
        return $mock;
    }

    protected function mockQuery(array $query = []): Query
    {
        $mock = $this->createMock(Query::class);
        $mock->method('__toString')->willReturn(http_build_query($query));
        $mock->method('get')->willReturnCallback(function (string $key, string|null $default = null) use ($query) {
            return $query[$key] ?? $default;
        });
        $mock->method('withArg')->willReturnCallback(function (string $key, string $value) use ($query) {
            $query[$key] = $value;
            return $this->mockQuery($query);
        });
        $mock->method('withoutArg')->willReturnCallback(function (string $key) use ($query) {
            unset($query[$key]);
            return $this->mockQuery($query);
        });
        $mock->method('withArgs')->willReturnCallback(function (array $args) use ($query) {
            $query = array_merge($query, $args);
            return $this->mockQuery($query);
        });
        $mock->method('withoutArgs')->willReturnCallback(function (array $keys) use ($query) {
            foreach ($keys as $key) {
                unset($query[$key]);
            }
            return $this->mockQuery($query);
        });
        return $mock;
    }

    protected function mockHost(string|null $host = null): Host
    {
        $mock = $this->createMock(Host::class);
        $mock->method('__toString')->willReturn($host ?? '');
        return $mock;
    }

    protected function mockPort(int|null $port = null): Port
    {
        return new Port($port);
    }

    protected function mockUser(string $username, string|null $password = null): User
    {
        $mock = $this->createMock(User::class);
        if ($username && $password) {
            $mock->method('__toString')->willReturn($username . ':' . $password);
        } elseif ($username) {
            $mock->method('__toString')->willReturn($username);
        } else {
            $mock->method('__toString')->willReturn('');
        }
        return $mock;
    }
}
