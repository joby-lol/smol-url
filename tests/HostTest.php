<?php
/*
* smolHTTP
* https://github.com/joby-lol/smol-http
* (c) 2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\URL;

use PHPUnit\Framework\TestCase;

class HostTest extends TestCase
{
    public function testWithIP()
    {
        // ipv4
        $host = new Host('127.0.0.1');
        $this->assertEquals('127.0.0.1', (string)$host);
        $this->assertEquals('127.0.0.1', $host->value);
        // ipv6
        $host = new Host('::1');
        $this->assertEquals('::1', (string)$host);
        $this->assertEquals('::1', $host->value);
    }

    public function testWithDomain()
    {
        $host = new Host('example.com');
        $this->assertEquals('example.com', (string)$host);
        $this->assertEquals('example.com', $host->value);
        // verify lower-case normalization
        $host = new Host('ExAmPlE.CoM');
        $this->assertEquals('example.com', (string)$host);
        $this->assertEquals('example.com', $host->value);
        // verify with some weird TLDs and subdomains
        $host = new Host('example.co.uk');
        $this->assertEquals('example.co.uk', (string)$host);
        $this->assertEquals('example.co.uk', $host->value);
        $host = new Host('example.co.uk.example.com');
        $this->assertEquals('example.co.uk.example.com', (string)$host);
        $this->assertEquals('example.co.uk.example.com', $host->value);
        $host = new Host('example.lol');
        $this->assertEquals('example.lol', (string)$host);
        $this->assertEquals('example.lol', $host->value);
    }

    public function testWithHostname()
    {
        $host = new Host('localhost');
        $this->assertEquals('localhost', (string)$host);
        $this->assertEquals('localhost', $host->value);
        $host = new Host('localhost.local');
        $this->assertEquals('localhost.local', (string)$host);
        $this->assertEquals('localhost.local', $host->value);
        $host = new Host('localhost.local.local');
        $this->assertEquals('localhost.local.local', (string)$host);
        $this->assertEquals('localhost.local.local', $host->value);
        // verify normalization
        $host = new Host('LOCALHOST.LOCAL.LOCAL');
        $this->assertEquals('localhost.local.local', (string)$host);
        $this->assertEquals('localhost.local.local', $host->value);
    }

    public function testInvalidHost()
    {
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Invalid host');
        new Host('....................................');
    }
}
