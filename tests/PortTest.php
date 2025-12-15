<?php
/*
* smolHTTP
* https://github.com/joby-lol/smol-http
* (c) 2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\URL;

use PHPUnit\Framework\TestCase;

class PortTest extends TestCase
{
    public function testPort()
    {
        $port = new Port(80);
        $this->assertEquals(80, $port->value);
    }

    public function testOverMaxPort()
    {
        $this->expectException(UrlException::class);
        new Port(65536);
    }

    public function testUnderMinPort()
    {
        $this->expectException(UrlException::class);
        new Port(-1);
    }
}
