# iRouter
 [![Build Status](https://img.shields.io/travis/penobit/iRouter/master)](https://travis-ci.org/penobit/iRouter) [![Latest Stable Version](https://poser.pugx.org/penobit/irouter/v/stable.svg)](https://packagist.org/packages/penobit/irouter) [![License](https://poser.pugx.org/penobit/irouter/license.svg)](https://packagist.org/packages/penobit/irouter)

- [iRouter](#irouter)
  - [Installation](#installation)
  - [Requirements](#requirements)
  - [Features](#features)
  - [Examples](#examples)
    - [Adding a route](#adding-a-route)
    - [Adding multiple routes at once](#adding-multiple-routes-at-once)
    - [Dynamic routes (with parameters)](#dynamic-routes-with-parameters)
    - [Match types](#match-types)
      - [adding a match type](#adding-match-types)
      - [Default match types](#default-match-types)

## Installation
You can install this package using composer or download last release from github and include the iRouter.php file
But i strongly recommend using composer
For installing package using composer use this command

```
composer require penobit/irouter
```

## Requirements

You need PHP >= 5.6 to use iRouter, although we highly recommend you [use an officially supported PHP version](https://secure.php.net/supported-versions.php) that is not EOL.

## Features

- Can be used with all HTTP Methods
- Dynamic routing with named route parameters
- Reversed routing
- Flexible regular expression routing
- Custom regexes

# Examples 

## Adding a route

```php
$router = new iRouter();

// map homepage
$router->map('GET', '/', function() {
    require __DIR__ . '/views/home.php';
});
``` 

## Adding multiple routes at once

```php
// Add multiple routes
$router->addRoutes(array(
  array('GET','/users/[i:id]', 'users#get', 'update_user'),
  array('PUT','/users/[i:id]', 'users#create', 'update_user'),
  array('PATCH','/users/[i:id]', 'users#update', 'update_user'),
  array('DELETE','/users/[i:id]', 'users#delete', 'delete_user')
));
``` 

## Dynamic routes (with parameters)

```php
// dynamic named route
$router->map('GET|POST', '/users/[i:id]/', function($id) {
  $user = .....
  require __DIR__ . '/views/user/details.php';
}, 'user-details');
``` 

## Generate url by named routes

```php
// echo URL to user-details page for ID 5
echo $router->generate('user-details', ['id' => 5]); // Output: "/users/5"
``` 

## Match types

### Adding match types

```php
// Add match type
$router->addMatchTypes(array('customType' => '[a-zA-Z]{2}[0-9]?'));
$router->map('GET', '/use-match-type/[customType:paramName]', function() {
    require __DIR__ . '/views/home.php';
});
```

### Default match types

```
*                    // Match all request URIs
[i]                  // Match an integer
[i:id]               // Match an integer as 'id'
[a:action]           // Match alphanumeric characters as 'action'
[h:key]              // Match hexadecimal characters as 'key'
[:action]            // Match anything up to the next / or end of the URI as 'action'
[create|edit:action] // Match either 'create' or 'edit' as 'action'
[*]                  // Catch all (lazy, stops at the next trailing slash)
[*:trailing]         // Catch all as 'trailing' (lazy)
[**:trailing]        // Catch all (possessive - will match the rest of the URI)
.[:format]?          // Match an optional parameter 'format' - a / or . before the block is also optional
```

<!-- - [Install iRouter](http://penobit.com/repositories/iRouter/usage/install.html)
- [Rewrite all requests to iRouter](http://penobit.com/repositories/iRouter/usage/rewrite-requests.html)
- [Map your routes](http://penobit.com/repositories/iRouter/usage/mapping-routes.html)
- [Match requests](http://penobit.com/repositories/iRouter/usage/matching-requests.html)
- [Process the request your preferred way](http://penobit.com/repositories/iRouter/usage/processing-requests.html) -->
