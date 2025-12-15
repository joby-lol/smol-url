# smolURL

A simple and lightweight extensible URL library designed for working with URLs in human-scale applications.

## What is smolURL?

smolURL is a modern PHP library for working with URLs using an immutable, component-based architecture. Unlike built-in PHP URL functions, smolURL represents URLs as composed objects where each component (path, query, fragment, host, etc.) is its own readonly class.

Key features:

- **Immutable design**: All URL components are readonly and use `with*()` methods to create modified copies
- **Type-safe**: Built with PHP 8.3+ features including readonly properties and typed parameters
- **Component-based**: Each URL part (Path, Query, Fragment, Scheme, Host, Port, User) is a separate class
- **Security-focused**: Intentionally limited to HTTP/HTTPS schemes to prevent arbitrary scheme parsing
- **Link resolution**: Built-in support for resolving relative links (like HTML `<a>` tags)
- **Clean API**: All components implement `Stringable` for easy conversion to strings

## Basic usage

### Creating URLs

```php
use Joby\Smol\URL\{URL, Path, Query, Fragment, Scheme, Host, Port, User};

// Simple absolute path
$url = new URL(new Path(filename: 'page.html'));
echo $url; // "/page.html"

// Full URL with all components
$url = new URL(
    path: new Path(['dir1', 'dir2'], 'file.php'),
    query: new Query(['key' => 'value']),
    fragment: new Fragment('section'),
    scheme: Scheme::HTTPS,
    host: new Host('example.com'),
    port: new Port(8080),
    user: new User('username', 'password')
);
echo $url; // "https://username:password@example.com:8080/dir1/dir2/file.php?key=value#section"

// Relative paths
$url = new URL(new Path(filename: 'page.html', absolute: false));
echo $url; // "page.html"
```

### Working with paths

```php
// Create from string
$path = Path::fromString('/dir1/dir2/file.php');

// Access components
$path->directory; // ['dir1', 'dir2']
$path->filename;  // 'file.php'
$path->absolute;  // true

// Get directory path
$path->dirname(); // "/dir1/dir2/"
```

### Manipulating query parameters

```php
$query = new Query(['page' => '1', 'sort' => 'name']);

// Access values with type safety
$page = $query->getInt('page');        // 1
$sort = $query->get('sort');           // 'name'
$missing = $query->get('foo', 'bar');  // 'bar' (default)

// Check for parameters
$query->has('page'); // true

// Require parameters (throws exception if missing)
$page = $query->requireInt('page');

// Create modified copies
$newQuery = $query->withArg('limit', 10);
$newQuery = $query->withArgs(['page' => 2, 'limit' => 10]);
$newQuery = $query->withoutArg('sort');
```

### Modifying URLs immutably

```php
$url = new URL(
    Path::fromString('/page'),
    new Query(['id' => '123']),
    scheme: Scheme::HTTP,
    host: new Host('example.com')
);

// Create modified versions
$https = $url->withScheme(Scheme::HTTPS);
$newPath = $url->withPath(Path::fromString('/other'));
$newQuery = $url->withQuery(new Query(['id' => '456']));

// Original URL is unchanged
echo $url;   // "http://example.com/page?id=123"
echo $https; // "https://example.com/page?id=123"
```

### Resolving relative links

URLs include a `withLinkStringApplied()` method that allows updating URLs using a variety of relative URL strings, including relative paths, fragments, and both partial and full query string updates.

```php
$base = new URL(
    Path::fromString('/dir1/dir2/page.html'),
    new Query(['a' => '1'])
);

// Apply relative links (like HTML <a href="...">)
$url = $base->withLinkStringApplied('other.html');
echo $url; // "/dir1/dir2/other.html"

$url = $base->withLinkStringApplied('../file.html');
echo $url; // "/dir1/file.html"

$url = $base->withLinkStringApplied('?b=2');
echo $url; // "/dir1/dir2/page.html?b=2"

$url = $base->withLinkStringApplied('&b=2');
echo $url; // "/dir1/dir2/page.html?a=1&b=2"

$url = $base->withLinkStringApplied('#section');
echo $url; // "/dir1/dir2/page.html#section"
```

## Limitations

- **HTTP/HTTPS only**: The library intentionally only supports HTTP and HTTPS schemes. This is a security feature to prevent parsing of arbitrary schemes like `javascript:`, `data:`, etc. (It does strictly allow using any backed enum as the scheme, so you could extend it to support more schemes if you like.)

- **No query parameter arrays**: Query parameters are limited to scalar types (strings, integers, floats, booleans). Arrays and objects are not supported to keep the implementation simple and focused.

- **PHP 8.3+ required**: The library uses modern PHP features including readonly classes, typed properties, and the `BackedEnum` type, requiring PHP 8.3 or higher.

- **Immutable only**: All components are readonly and immutable. You cannot modify a URL or its components in place; you must use the `with*()` methods to create new instances with your changes.

## Installation

```bash
composer require joby/smol-url
```