# Bouncer

This package adds a bouncer at Laravel's access gate.

- [Introduction](#introduction)
- [Installation](#installation)
  - [Enabling cache](#enabling-cache)
- [Usage](#usage)
  - [Creating roles and abilities](#creating-roles-and-abilities)
  - [Assigning roles to a user](#assigning-roles-to-a-user)
  - [Giving a user an ability directly](#giving-a-user-an-ability-directly)
  - [Giving an ability for certain models](#giving-an-ability-for-certain-models)
  - [Retracting a role from a user](#retracting-a-role-from-a-user)
  - [Removing an ability](#removing-an-ability)
  - [Checking a user's roles](#checking-a-users-roles)
  - [Getting all abilities for a user](#getting-all-abilities-for-a-user)
  - [Authorizing users](#authorizing-users)
  - [Refreshing the cache](#refreshing-the-cache)
- [Cheat sheet](#cheat-sheet)
- [License](#license)

## Introduction

Bouncer provides a mechanism to handle simple roles and abilities in [Laravel's ACL](http://laravel.com/docs/5.1/authorization). With an expressive and fluent syntax, it stays out of your way as much as possible: use it when you want, ignore it when you don't.

Bouncer works well with other abilities you have hard-coded in your own app. Your code always takes precedence: if your code allows an action, the bouncer will not interfere.

Once installed, you can simply tell the bouncer what you want to allow at the gate:

```php
// Give a user the ability to create posts
Bouncer::allow($user)->to('create', Post::class);

// Alternatively, do it through a role
Bouncer::allow('admin')->to('create', Post::class);
Bouncer::assign('admin')->to($user);

// You can also restrict abilities to a specific model
Bouncer::allow($user)->to('edit', $post);
```

When you check abilities at the gate, the bouncer will be consulted first. If he sees an ability that has been granted to the current user (whether directly, or through a role) he'll authorize the check.

For a quick glanceable list of the bouncer's features, check out [the cheat sheet](#cheat-sheet).

## Installation

Simply install the bouncer package with composer:

```
$ composer require silber/bouncer
```

Once the composer installation completes, you can add the service provider and alias the facade. Open `config/app.php`, and make the following changes:

1) Add a new item to the `providers` array:

```php
Silber\Bouncer\BouncerServiceProvider::class
```

2) Add a new item to the `aliases` array:

```php
'Bouncer' => Silber\Bouncer\BouncerFacade::class
```

This part is optional. If you don't want to use the facade, you can skip step 2.

3) Add the bouncer's trait to your user model:

```php
use Silber\Bouncer\Database\HasRolesAndAbilities;

class User extends Model
{
    use HasRolesAndAbilities;
}
```

4) Now, to run the bouncer's migrations, first publish the package's migrations into your app's `migrations` directory, by running the following command:

```
$ php artisan vendor:publish --provider="Silber\Bouncer\BouncerServiceProvider" --tag="migrations"
```

5) Finally, run the migrations:

```
$ php artisan migrate
```

### Enabling cache

All queries executed by the bouncer are cached for the current request. For better performance, you may want to use a real caching system. To enable cross-request caching, add this to your `AppServiceProvider`'s `boot` method:

```php
Bouncer::cache($this->app['cache']->store());
```

> **Warning:** if you enable caching, you are responsible to refresh the cache whenever you make changes to user's abilities/roles. For how to refresh the cache, read [refreshing the cache](#refreshing-the-cache).

## Usage

Adding roles and abilities to users is made extremely easy. You do not have to create a role or an ability in advance. Simply pass the name of the role/ability, and Bouncer will create it if it doesn't exist.

> **Note:** the examples below all use the `Bouncer` facade. If you don't like facades, you can instead inject an instance of `Silber\Bouncer\Bouncer` into your class.

### Creating roles and abilities

Let's create a role called `admin` and give it the ability to `ban-users` from our site:

```php
Bouncer::allow('admin')->to('ban-users');
```

That's it. Behind the scenes, Bouncer will create both a `Role` model and an `Ability` model for you.

### Assigning roles to a user

To now give the `admin` role to a user, simply tell the bouncer that the given user should be assigned the admin role:

```php
Bouncer::assign('admin')->to($user);
```

Alternatively, you can call the `assign` method directly on the user:

```php
$user->assign('admin');
```

### Giving a user an ability directly

Sometimes you might want to give a user an ability directly, without using a role:

```php
Bouncer::allow($user)->to('ban-users');
```

Here too you can accomplish the same directly off of the user model:

```php
$user->allow('ban-users');
```

### Giving an ability for certain models

Sometimes you might want to only allow users to take action on a specific model. Simply pass the model as a second argument:

```php
Bouncer::allow($user)->to('edit', $post);
```

To allow an ability on all models of a certain type, pass the fully qualified class name instead:

```php
Bouncer::allow($user)->to('edit', Post::class);
```

### Retracting a role from a user

The bouncer can also retract a previously-assigned role from a user:

```php
Bouncer::retract('ban-users')->from($user);
```

Or do it directly on the user:

```php
$user->retract('ban-users');
```

### Removing an ability

The bouncer can also remove an ability previously granted to a user:

```php
Bouncer::disallow($user)->to('ban-users');
```

Or directly on the model:

```php
$user->disallow('ban-users');
```

> **Note:** if the user has a role that allows them to `ban-users` they will still have that ability. To disallow it, either remove the ability from the role or retract the role from the user.

If the ability has been granted through a role, tell the bouncer to remove the ability from the role instead:

```php
Bouncer::disallow('admin')->to('ban-users');
```

To remove an ability for a specific model, pass it as a second argument:

```php
Bouncer::disallow($user)->to('delete', $post);
```

To remove an ability for all models of a given type, pass the fully qualified class name as a second argument:

```php
Bouncer::disallow($user)->to('delete', Post::class);
```

### Checking a user's roles

The bouncer can check if a user has a specific role:

```php
Bouncer::is($user)->a('moderator');
```

If the role you're checking starts with a vowel, you might want to use the `an` alias method:

```php
Bouncer::is($user)->an('admin');
```

To check if a user has one of many roles, pass the roles as an array:

```php
Bouncer::is($user)->a(['moderator', 'editor']);
```

You can also check if the user has all of the given roles:

```php
Bouncer::is($user)->all(['editor', 'moderator']);
```

All of the above checks can also be done directly on the user model:

```php
$user->is('admin');
```

You can also check if a user has all of the given abilities directly from the model:

```php
$user->isAll(['editor', 'moderator']);
```

### Getting all abilities for a user

You can get all abilities for a user directly from the model:

```php
$abilities = $user->getAbilities();
```

This will return a collection of the user's abilities, including any abilities granted to the user through their roles.

### Authorizing users

Authorizing users is handled directly at [Laravel's `Gate`](http://laravel.com/docs/5.1/authorization#checking-abilities), or on the user model (`$user->can($ability)`).

For convenience, the bouncer class provides two passthrough methods:

```php
Bouncer::allows($ability);
Bouncer::denies($ability);
```

These call directly into the `Gate` class.

### Refreshing the cache

All queries executed by the bouncer are cached for the current request. If you enable [cross-request caching](#enabling-cache), the cache will presist across different requests.

Whenever you need, you can fully refresh the bouncer's cache:

```php
Bouncer::refresh();
```

> **Note:** fully refreshing the cache **for all users** requires cache tags. Not all cache drivers support this. Refer to [Laravel's documentation](http://laravel.com/docs/5.1/cache#cache-tags) to see if your driver supports cache tags.

Alternatively, you can refresh the cache only for a specific user:

```php
Bouncer::refreshForUser($user);
```

Refreshing the cache **for a specific user** is available even if your cache driver does not support cache tags.

## Cheat Sheet

```php
Bouncer::allow($user)->to('ban-users');
Bouncer::allow($user)->to('edit', Post::class);
Bouncer::allow($user)->to('delete', $post);

$user->allow('ban-users');
$user->allow('edit', Post::class);
$user->allow('delete', $post);

Bouncer::disallow($user)->to('ban-users');
Bouncer::disallow($user)->to('edit', Post::class);
Bouncer::disallow($user)->to('delete', $post);

$user->disallow('ban-users');
$user->disallow('edit', Post::class);
$user->disallow('delete', $post);

Bouncer::allow('admin')->to('ban-users');
Bouncer::disallow('admin')->to('ban-users');

Bouncer::assign('admin')->to($user);
Bouncer::retract('admin')->from($user);

$user->assign('admin');
$user->retract('admin');

$check = Bouncer::is($user)->a('subscriber');
$check = Bouncer::is($user)->an('admin');
$check = Bouncer::is($user)->a(['moderator', 'editor']);
$check = Bouncer::is($user)->all(['moderator', 'editor']);

$check = $user->is('subscriber');
$check = $user->is(['moderator', 'editor']);
$check = $user->isAll(['moderator', 'editor']);

$check = Bouncer::allows('ban-users');
$check = Bouncer::allows('edit', Post::class);
$check = Bouncer::allows('delete', $post);

$check = Bouncer::denies('ban-users');
$check = Bouncer::denies('edit', Post::class);
$check = Bouncer::denies('delete', $post);

Bouncer::useCache($cache);
Bouncer::refresh();
Bouncer::refreshForUser($user);
```

## License

Bouncer is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
