<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;

use PHPUnit\Framework\TestCase;

class FragmentTest extends TestCase
{
    public function testFragment()
    {
        $fragment = new Fragment('fragment');
        $this->assertEquals('fragment', (string)$fragment);
        $this->assertEquals('fragment', $fragment->value);
    }

    public function testURLEncoding()
    {
        $fragment = new Fragment('fragment with spaces');
        $this->assertEquals('fragment%20with%20spaces', (string)$fragment);
        $this->assertEquals('fragment with spaces', $fragment->value);
    }
}
