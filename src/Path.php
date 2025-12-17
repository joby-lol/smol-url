<?php

/**
 * smolURL
 * https://github.com/joby-lol/smol-url
 * (c) 2025 Joby Elliott code@joby.lol
 * MIT License https://opensource.org/licenses/MIT
 */

namespace Joby\Smol\URL;

use Stringable;

/**
 * Class for paths, which are the part of the URL after the first / symbol, but before the query or fragment.
 * 
 * @phpstan-consistent-constructor
 */
readonly class Path implements Stringable
{
    /** @var bool $absolute whether the path is absolute or relative */
    public bool $absolute;
    /** @var array<string> $directory raw unencoded directory folder names */
    public array $directory;
    /** @var string|null $filename raw unencoded filename */
    public string|null $filename;

    /**
     * @param array<string> $directory  the directory parts of the path, unencoded
     */
    public function __construct(
        array       $directory = [],
        string|null $filename = null,
        bool        $absolute = true,
    ) {
        $this->absolute = $absolute;
        $this->directory = static::resolveDirectory($directory, $absolute);
        $this->filename = $filename ?: null;
        if ($filename === '.') throw new URLException('Invalid filename');
        if ($filename === '..') throw new URLException('Invalid filename');
    }

    public static function fromString(string $path): static
    {
        if ($path === '') return new static([], null, false);
        elseif ($path === '/') return new static([], null, true);
        if (str_starts_with($path, '/')) {
            $absolute = true;
            $path = substr($path, 1);
        } else {
            $absolute = false;
        }
        if (str_ends_with($path, '/')) {
            $filename = null;
            $directory = explode('/', substr($path, 0, -1));
        } else {
            $last_slash = strrpos($path, '/');
            if ($last_slash === false) {
                $filename = $path;
                $directory = [];
            } else {
                $filename = substr($path, $last_slash + 1);
                $directory = explode('/', substr($path, 0, $last_slash));
            }
        }
        return new static($directory, $filename, $absolute);
    }

    public function __toString(): string
    {
        $string = $this->encodedDirectory() . '/' . $this->encodedFilename();
        return ltrim($string, '/');
    }

    /**
     * Get the directory name as a string, with each directory name encoded. Includes trailing slashes for unambigously
     * indicating that this is a directory name, and does not end in a filename. Also include either a leading './' or
     * a leading '/' depending on whether this path is absolute. Relative paths may also start with '..'.
     *
     * @return string
     */
    public function dirname(): string
    {
        $directory = $this->encodedDirectory();
        if ($this->absolute) $directory = '/' . $directory;
        else {
            if ($directory !== '..' && !str_starts_with($directory, '../')) $directory = './' . $directory;
        }
        // ensure trailing slash
        if (!str_ends_with($directory, '/')) $directory .= '/';
        return $directory;
    }

    /**
     * Get the filename as a string, with any characters encoded. Does not include any leading or trailing slashes.
     */
    protected function encodedFilename(): string
    {
        if ($this->filename) return rawurlencode($this->filename);
        else return '';
    }

    /**
     * Get the directory as a string, with each directory name encoded. Does not include the filename, and does not
     * include any leading or trailing slashes.
     */
    protected function encodedDirectory(): string
    {
        return implode('/', array_map('rawurlencode', $this->directory));
    }

    /**
     * Resolve any . or .. directory names in the given directory array.
     *
     * For .. parts in absolute paths, they will always be removed, and any that would traverse above the root are ignored, so absolute paths will never contain any .. parts.
     *
     * For .. parts in relative paths, they are only removed if there is a parent directory above them. So the final path may still contain one or more .. parts at the beginning.
     * 
     * @param array<string> $directory
     * @return array<string>
     */
    protected static function resolveDirectory(array $directory, bool $absolute): array
    {
        $resolved = [];
        foreach ($directory as $part) {
            if ($part === '.') {
                continue;
            } elseif ($part === '..') {
                // for absolute
                if ($absolute) array_pop($resolved);
                else {
                    if (count($resolved) > 0 && end($resolved) != '..') array_pop($resolved);
                    else $resolved[] = '..';
                }
            } else {
                $resolved[] = $part;
            }
        }
        return $resolved;
    }
}
