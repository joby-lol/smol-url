<?php
/*
* smolURL https://github.com/joby-lol/smol-url
* (c) 2025 Joby Elliott code@joby.lol
* MIT License https://opensource.org/licenses/MIT
*/

namespace Joby\Smol\URL;

use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    public function testEmptyPaths()
    {
        // path with no arguments should be an empty path, even when absolute because the URL class handles turning it into '/'
        $path = new Path();
        $this->assertEquals('', (string)$path);
        $this->assertNull($path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertTrue($path->absolute);
        // same for an empty relative path, the URL turns it into './' as needed
        $path = new Path(absolute: false);
        $this->assertEquals('', (string)$path);
        $this->assertNull($path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertFalse($path->absolute);
        // relative path with just a dot should become an empty relative path
        $path = new Path(['.'], absolute: false);
        $this->assertEquals('', (string)$path);
        // relative path with just a double dot should become an empty relative path
        $path = new Path(['..'], absolute: false);
        $this->assertEquals('../', (string)$path);
        // absolute path with just a dot should become an empty relative path
        $path = new Path(['.']);
        $this->assertEquals('', (string)$path);
        $this->assertEquals([], $path->directory);
        // absolute path with just a double dot should become an empty relative path
        $path = new Path(['..']);
        $this->assertEquals('', (string)$path);
        $this->assertEquals([], $path->directory);
        // empty absolute path created from string
        $path = Path::fromString('/');
        $this->assertEquals('', (string)$path);
        $this->assertNull($path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertTrue($path->absolute);
        // empty relative path created from string
        $path = Path::fromString('');
        $this->assertEquals('', (string)$path);
        $this->assertNull($path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertFalse($path->absolute);
        // relative path created from string
        $path = Path::fromString('./');
        $this->assertEquals('', (string)$path);
        $this->assertNull($path->filename);
        $this->assertFalse($path->absolute);
        $this->assertEquals([], $path->directory);
        // relative path created from string
        $path = Path::fromString('../');
        $this->assertEquals('../', (string)$path);
        $this->assertNull($path->filename);
        $this->assertFalse($path->absolute);
        $this->assertEquals(['..'], $path->directory);
    }

    public function testEmptyPathsWithFilenames()
    {
        // relative filenames
        $path = new Path(filename: 'filename', absolute: false);
        $this->assertEquals('filename', (string)$path);
        $this->assertEquals('filename', $path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertFalse($path->absolute);
        $path = new Path(filename: 'filename.ext', absolute: false);
        $this->assertEquals('filename.ext', (string)$path);
        $this->assertEquals('filename.ext', $path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertFalse($path->absolute);
        // absolute filenames from string
        $path = Path::fromString('/filename');
        $this->assertEquals('filename', (string)$path);
        $this->assertEquals('filename', $path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertTrue($path->absolute);
        $path = Path::fromString('/filename.ext');
        $this->assertEquals('filename.ext', (string)$path);
        $this->assertEquals('filename.ext', $path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertTrue($path->absolute);
        // relative filenames from string
        $path = Path::fromString('filename');
        $this->assertEquals('filename', (string)$path);
        $this->assertEquals('filename', $path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertFalse($path->absolute);
        $path = Path::fromString('filename.ext');
        $this->assertEquals('filename.ext', (string)$path);
        $this->assertEquals('filename.ext', $path->filename);
        $this->assertEquals([], $path->directory);
        $this->assertFalse($path->absolute);
    }

    public function testAbsolutePathWithoutFilename()
    {
        // absolute path with no filename
        $path = new Path(['dir1', 'dir2'], absolute: true);
        $this->assertEquals('dir1/dir2/', (string)$path);
        $this->assertNull($path->filename);
        $this->assertEquals(['dir1', 'dir2'], $path->directory);
        $this->assertTrue($path->absolute);
    }

    public function testRelativePathWithoutFilename()
    {
        // relative path with no filename
        $path = new Path(['dir1', 'dir2'], null, false);
        $this->assertEquals('dir1/dir2/', (string)$path);
        $this->assertNull($path->filename);
        $this->assertEquals(['dir1', 'dir2'], $path->directory);
        $this->assertFalse($path->absolute);
    }

    public function testAbsolutePathWithoutFilenameFromString()
    {
        // absolute path with no filename from string
        $path = Path::fromString('/dir1/dir2/');
        $this->assertEquals('dir1/dir2/', (string)$path);
        $this->assertNull($path->filename);
        $this->assertEquals(['dir1', 'dir2'], $path->directory);
        $this->assertTrue($path->absolute);
    }

    public function testRelativePathWithoutFilenameFromString()
    {
        // relative path with no filename from string
        $path = Path::fromString('dir1/dir2/');
        $this->assertEquals('dir1/dir2/', (string)$path);
        $this->assertNull($path->filename);
        $this->assertEquals(['dir1', 'dir2'], $path->directory);
        $this->assertFalse($path->absolute);
    }

    public function testAbsolutePathWithFilename()
    {
        // absolute path with filename
        $path = new Path(['dir1', 'dir2'], 'filename', true);
        $this->assertEquals('dir1/dir2/filename', (string)$path);
        $this->assertEquals('filename', $path->filename);
        $this->assertEquals(['dir1', 'dir2'], $path->directory);
        $this->assertTrue($path->absolute);
    }

    public function testRelativePathWithFilename()
    {
        // relative path with filename
        $path = new Path(['dir1', 'dir2'], 'filename', false);
        $this->assertEquals('dir1/dir2/filename', (string)$path);
        $this->assertEquals('filename', $path->filename);
        $this->assertEquals(['dir1', 'dir2'], $path->directory);
        $this->assertFalse($path->absolute);
    }

    public function testAbsolutePathWithFilenameFromString()
    {
        // absolute path with filename from string
        $path = Path::fromString('/dir1/dir2/filename');
        $this->assertEquals('dir1/dir2/filename', (string)$path);
        $this->assertEquals('filename', $path->filename);
        $this->assertEquals(['dir1', 'dir2'], $path->directory);
        $this->assertTrue($path->absolute);
    }

    public function testRelativePathWithFilenameFromString()
    {
        // relative path with filename from string
        $path = Path::fromString('dir1/dir2/filename');
        $this->assertEquals('dir1/dir2/filename', (string)$path);
        $this->assertEquals('filename', $path->filename);
        $this->assertEquals(['dir1', 'dir2'], $path->directory);
        $this->assertFalse($path->absolute);
    }

    public function testResolvingDoubleDots()
    {
        // case where double dots are above the root
        $path = new Path(['dir1', 'dir2', '..', 'dir3']);
        $this->assertEquals('dir1/dir3/', (string)$path);
        // absolute paths attempting to traverse higher than root should be equivalent to the root
        $path = new Path(['..'], absolute: true);
        $this->assertEquals('', (string)$path);
        $path = new Path(['..', '..']);
        $this->assertEquals('', (string)$path);
        // filename should be preserved in this case though
        $path = new Path(['..', '..'], 'filename');
        $this->assertEquals('filename', (string)$path);
        // double dots should be stackable at the front of relative paths
        $path = new Path(['..', '..'], 'filename', false);
        $this->assertEquals('../../filename', (string)$path);
    }

    public function testAbsoluteDirname()
    {
        // absolute path with or without filename should return just path
        $this->assertEquals('/', Path::fromString('/')->dirName());
        $this->assertEquals('/', Path::fromString('/filename')->dirName());
        $this->assertEquals('/dir1/dir2/', Path::fromString('/dir1/dir2/')->dirName());
        $this->assertEquals('/dir1/dir2/', Path::fromString('/dir1/dir2/filename')->dirName());
        // should also work with .. parts
        $this->assertEquals('/', Path::fromString('/../')->dirName());
        $this->assertEquals('/', Path::fromString('/../filename')->dirName());
    }

    public function testRelativeDirname()
    {
        // relative path with or without filename should return just path
        $this->assertEquals('./', Path::fromString('./')->dirName());
        $this->assertEquals('./', Path::fromString('./filename')->dirName());
        $this->assertEquals('./dir1/dir2/', Path::fromString('./dir1/dir2/')->dirName());
        $this->assertEquals('./dir1/dir2/', Path::fromString('./dir1/dir2/filename')->dirName());
        // should also work with .. parts
        $this->assertEquals('../', Path::fromString('../')->dirName());
        $this->assertEquals('../', Path::fromString('../filename')->dirName());
    }

    public function testAbsoluteDirnameConsistency()
    {
        // passing a dirname output back into fromString should yield a URL with the same dirname
        $this->assertEquals(
            Path::fromString('/')->dirName(),
            Path::fromString(Path::fromString('/')->dirName())->dirName()
        );
        $this->assertEquals(
            Path::fromString('/')->dirName(),
            Path::fromString(Path::fromString('/filename')->dirName())->dirName()
        );
        $this->assertEquals(
            Path::fromString('/dir1/dir2/')->dirName(),
            Path::fromString(Path::fromString('/dir1/dir2/')->dirName())->dirName()
        );
        $this->assertEquals(
            Path::fromString('/dir1/dir2/')->dirName(),
            Path::fromString(Path::fromString('/dir1/dir2/filename')->dirName())->dirName()
        );
    }

    public function testRelativeDirnameConsistency()
    {
        // passing a dirname output back into fromString should yield a URL with the same dirname
        $this->assertEquals(
            Path::fromString('./')->dirName(),
            Path::fromString(Path::fromString('./')->dirName())->dirName()
        );
        $this->assertEquals(
            Path::fromString('./')->dirName(),
            Path::fromString(Path::fromString('./filename')->dirName())->dirName()
        );
        $this->assertEquals(
            Path::fromString('./dir1/dir2/')->dirName(),
            Path::fromString(Path::fromString('./dir1/dir2/')->dirName())->dirName()
        );
        $this->assertEquals(
            Path::fromString('./dir1/dir2/')->dirName(),
            Path::fromString(Path::fromString('./dir1/dir2/filename')->dirName())->dirName()
        );
        // should also work with .. parts
        $this->assertEquals(
            Path::fromString('../')->dirName(),
            Path::fromString(Path::fromString('../')->dirName())->dirName()
        );
        $this->assertEquals(
            Path::fromString('../')->dirName(),
            Path::fromString(Path::fromString('../filename')->dirName())->dirName()
        );
        $this->assertEquals(
            Path::fromString('../dir1/dir2/')->dirName(),
            Path::fromString(Path::fromString('../dir1/dir2/')->dirName())->dirName()
        );
        $this->assertEquals(
            Path::fromString('../dir1/dir2/')->dirName(),
            Path::fromString(Path::fromString('../dir1/dir2/filename')->dirName())->dirName()
        );
    }
}
