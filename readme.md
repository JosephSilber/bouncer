# Bouncer

<p>
<a href="https://travis-ci.org/JosephSilber/bouncer"><img src="https://travis-ci.org/JosephSilber/bouncer.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/silber/bouncer"><img src="https://poser.pugx.org/silber/bouncer/d/total.svg" alt="Total Downloads"></a>
<a href="https://github.com/JosephSilber/bouncer/blob/master/LICENSE.txt"><img src="https://poser.pugx.org/silber/bouncer/license.svg" alt="License"></a>
</p>

Bouncer is an elegant, framework-agnostic approach to managing roles and abilities for any app using Eloquent models.

- [Introduction](#introduction)
- [Installation](#installation)
  - [Installing Bouncer in a Laravel app](#installing-bouncer-in-a-laravel-app)
  - [Installing Bouncer in a non-Laravel app](#installing-bouncer-in-a-non-laravel-app)
  - [Enabling cache](#enabling-cache)
- [Usage](#usage)
  - [Creating roles and abilities](#creating-roles-and-abilities)
  - [Assigning roles to a user](#assigning-roles-to-a-user)
  - [Giving a user an ability directly](#giving-a-user-an-ability-directly)
  - [Restricting an ability to a model](#restricting-an-ability-to-a-model)
  - [Allowing a user or role to "own" a model](#allowing-a-user-or-role-to-own-a-model)
  - [Retracting a role from a user](#retracting-a-role-from-a-user)
  - [Removing an ability](#removing-an-ability)
  - [Checking a user's roles](#checking-a-users-roles)
  - [Getting all abilities for a user](#getting-all-abilities-for-a-user)
  - [Authorizing users](#authorizing-users)
  - [Blade directives](#blade-directives)
  - [Refreshing the cache](#refreshing-the-cache)
- [Configuration](#configuration)
  - [Cache](#cache)
  - [Tables](#tables)
  - [Custom models](#custom-models)
  - [User Model](#user-model)
  - [Ownership](#ownership)
- [Cheat sheet](#cheat-sheet)
- [Alternative](#alternative)
- [License](#license)

## Introduction

Bouncer is an elegant, framework-agnostic approach to managing roles and abilities for any app using Eloquent models. With an expressive and fluent syntax, it stays out of your way as much as possible: use it when you want, ignore it when you don't.

For a quick, glanceable list of Bouncer's features, check out [the cheat sheet](#cheat-sheet).

Bouncer works well with other abilities you have hard-coded in your own app. Your code always takes precedence: if your code allows an action, Bouncer will not interfere.

Once installed, you can simply tell the bouncer what you want to allow at the gate:

```php
// Give a user the ability to create posts
Bouncer::allow($user)->to('create', Post::class);

// Alternatively, do it through a role
Bouncer::allow('admin')->to('create', Post::class);
Bouncer::assign('admin')->to($user);

// You can also grant an ability only to a specific model
Bouncer::allow($user)->to('edit', $post);
```

When you check abilities at the gate, the bouncer will be consulted first. If he sees an ability that has been granted to the current user (whether directly, or through a role) he'll authorize the check.

## Installation

### Installing Bouncer in a Laravel app

Install Bouncer with [composer](https://getcomposer.org/doc/00-intro.md):

```
$ composer require silber/bouncer v1.0.0-beta.4
```

> In Laravel 5.5, [service providers and aliases are automatically registered](https://laravel.com/docs/5.5/packages#package-discovery). If you're using Laravel 5.5, skip ahead directly to step 3 (do not pass go, but do collect $200).

Once the composer installation completes, you can add the service provider and alias the facade. Open `config/app.php`, and make the following changes:

1) Add a new item to the `providers` array:

    ```php
    Silber\Bouncer\BouncerServiceProvider::class,
    ```

2) Add a new item to the `aliases` array:

    ```php
    'Bouncer' => Silber\Bouncer\BouncerFacade::class,
    ```

    This part is optional. If you don't want to use the facade, you can skip step 2.

3) Add Bouncer's trait to your user model:

    ```php
    use Silber\Bouncer\Database\HasRolesAndAbilities;

    class User extends Model
    {
        use HasRolesAndAbilities;
    }
    ```

4) Now, to run Bouncer's migrations, first publish the migrations into your app's `migrations` directory, by running the following command:

    ```
    php artisan vendor:publish --tag="bouncer.migrations"
    ```

5) Finally, run the migrations:

    ```
    php artisan migrate
    ```

#### Facade

Whenever you use the `Bouncer` facade in your code, remember to add this line to your namespace imports at the top of the file:

```php
use Bouncer;
```

For more information about Laravel Facades, refer to [the Laravel documentation](https://laravel.com/docs/5.5/facades).

### Installing Bouncer in a non-Laravel app

1) Install Bouncer with [composer](https://getcomposer.org/doc/00-intro.md):

    ```
    $ composer require silber/bouncer v1.0.0-beta.4
    ```

2) Set up the database with [the Eloquent Capsule component](https://github.com/illuminate/database/blob/master/README.md):

    ```php
    use Illuminate\Database\Capsule\Manager as Capsule;

    $capsule = new Capsule;

    $capsule->addConnection([/* connection config */]);

    $capsule->setAsGlobal();
    ```

    Refer to [the Eloquent Capsule documentation](https://github.com/illuminate/database/blob/master/README.md) for more details.

3) Run the migrations. You'll find the necessary migrations in [the migrations stub file](https://github.com/JosephSilber/bouncer/blob/master/migrations/create_bouncer_tables.php#L17-L73) in Bouncer's source code.

4) Add Bouncer's trait to your user model:

    ```php
    use Illuminate\Database\Eloquent\Model;
    use Silber\Bouncer\Database\HasRolesAndAbilities;

    class User extends Model
    {
        use HasRolesAndAbilities;
    }
    ```

5) Create an instance of Bouncer:

    ```php
    use Silber\Bouncer\Bouncer;

    $bouncer = Bouncer::create();

    // If you are in a request with a current user
    // that you'd wish to check permissions for,
    // pass that user to the "create" method:
    $bouncer = Bouncer::create($user);
    ```

    The `create` method creates a `Bouncer` instance with sensinsle defaults. To fully customize it, use the `make` method to get a factory instance:

    ```php
    use Silber\Bouncer\Bouncer;

    $bouncer = Bouncer::make()
             ->withCache($customCacheInstance)
             ->create();
    ```

    Check out [the `Factory` class](https://github.com/JosephSilber/bouncer/blob/c974953a0b1d8d187023002cdfae1800f3ccdb02/src/Factory.php) to see all the customizations available.

6) Register Bouncer with the container (optional):

    If you're using dependency injection in your app, you may register the Bouncer instance as a singleton in the container:

    ```php
    use Silber\Bouncer\Bouncer;
    use Illuminate\Container\Container;

    Container::getInstance()->singleton(Bouncer::class, function () {
        return Bouncer::create();
    });
    ```

    You can now inject Bouncer into any class that needs it.

### Enabling cache

By default, Bouncer's queries are cached for the current request. For better performance, you may want to [enable cross-request caching](#cache).

## Usage

Adding roles and abilities to users is made extremely easy. You do not have to create a role or an ability in advance. Simply pass the name of the role/ability, and Bouncer will create it if it doesn't exist.

> **Note:** the examples below all use the `Bouncer` facade. If you don't use facades, you can instead inject an instance of `Silber\Bouncer\Bouncer` into your class.

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

Here too you can accomplish the same directly off of the user:

```php
$user->allow('ban-users');
```

### Restricting an ability to a model

Sometimes you might want to restrict an ability to a specific model type. Simply pass the model name as a second argument:

```php
Bouncer::allow($user)->to('edit', Post::class);
```

If you want to restrict the ability to a specific model instance, pass in the actual model instead:

```php
Bouncer::allow($user)->to('edit', $post);
```

### Allowing a user or role to "own" a model

Use the `toOwn` method to allow users to manage _their own_ models:

```php
Bouncer::allow($user)->toOwn(Post::class);
```

Now, when checking at the gate whether the user may perform an action on a given post, the post's `user_id` will be compared to the logged-in user's `id` ([this can be customized](#ownership)). If they match, the gate will allow the action.

The above will grant all abilities on a user's "owned" models. You can restrict the abilities by following it up with a call to the `to` method:

```php
Bouncer::allow($user)->toOwn(Post::class)->to('view');

// Or pass it an array of abilities:
Bouncer::allow($user)->toOwn(Post::class)->to(['view', 'update']);
```

You can also allow users to own all _types_ of models in your application:

```php
Bouncer::allow($user)->toOwnEverything();

// And to restrict ownership to a given ability
Bouncer::allow($user)->toOwnEverything()->to('view');
```

### Retracting a role from a user

The bouncer can also retract a previously-assigned role from a user:

```php
Bouncer::retract('admin')->from($user);
```

Or do it directly on the user:

```php
$user->retract('admin');
```

### Removing an ability

The bouncer can also remove an ability previously granted to a user:

```php
Bouncer::disallow($user)->to('ban-users');
```

Or directly on the user:

```php
$user->disallow('ban-users');
```

> **Note:** if the user has a role that allows them to `ban-users` they will still have that ability. To disallow it, either remove the ability from the role or retract the role from the user.

If the ability has been granted through a role, tell the bouncer to remove the ability from the role instead:

```php
Bouncer::disallow('admin')->to('ban-users');
```

To remove an ability for a specific model type, pass in its name as a second argument:

```php
Bouncer::disallow($user)->to('delete', Post::class);
```

> **Warning:** if the user has an ability to `delete` a specific `$post` instance, the code above will *not* remove that ability. You will have to remove the ability separately - by passing in the actual `$post` as a second argument - as shown below.

To remove an ability for a specific model instance, pass in the actual model instead:

```php
Bouncer::disallow($user)->to('delete', $post);
```

### Checking a user's roles

> **Note**: Generally speaking, you should not have a need to check roles directly. It is better to allow a role certain abilities, then check for those abilities instead. If what you need is very general, you can create very broad abilities. For example, an `access-dashboard` ability is always better than checking for `admin` or `editor` roles directly. For the rare occasion that you do want to check a role, that functionality is available here.

The bouncer can check if a user has a specific role:

```php
Bouncer::is($user)->a('moderator');
```

If the role you're checking starts with a vowel, you might want to use the `an` alias method:

```php
Bouncer::is($user)->an('admin');
```

For the inverse, you can also check if a user *doesn't* have a specific role:

```php
Bouncer::is($user)->notA('moderator');

Bouncer::is($user)->notAn('admin');
```

You can check if a user has one of many roles:

```php
Bouncer::is($user)->a('moderator', 'editor');
```

You can also check if the user has all of the given roles:

```php
Bouncer::is($user)->all('editor', 'moderator');
```

You can also check if a user has none of the given roles:

```php
Bouncer::is($user)->notAn('editor', 'moderator');
```

These checks can also be done directly on the user:

```php
$user->isAn('admin');
$user->isA('subscriber');

$user->isNotAn('admin');
$user->isNotA('subscriber');

$user->isAll('editor', 'moderator');
```

### Getting all abilities for a user

You can get all abilities for a user directly from the user model:

```php
$abilities = $user->getAbilities();
```

This will return a collection of the user's abilities, including any abilities granted to the user through their roles.

### Authorizing users

Authorizing users is handled directly at [Laravel's `Gate`](https://laravel.com/docs/5.5/authorization#gates), or on the user model (`$user->can($ability)`).

For convenience, the bouncer class provides two passthrough methods:

```php
Bouncer::can($ability);
Bouncer::cannot($ability);
```

These call directly into the `Gate` class.

### Blade directives

Bouncer does not add its own blade directives. Since Bouncer works directly with Laravel's gate, simply use its `@can` directive to check for the current user's abilities:

```html
@can ('update', $post)
    <a href="{{ route('post.update', $post) }}">Edit Post</a>
@endcan
```

Since checking for roles directly is generally [not recommended](#checking-a-users-roles), Bouncer does not ship with a separate directive for that. If you still insist on checking for roles, you can do so using the general `@if` directive:

```php
@if ($user->isAn('admin'))
    //
@endif
```

### Refreshing the cache

All queries executed by Bouncer are cached for the current request. If you enable [cross-request caching](#cache), the cache will persist across different requests.

Whenever you need, you can fully refresh the bouncer's cache:

```php
Bouncer::refresh();
```

> **Note:** fully refreshing the cache for all users uses [cache tags](http://laravel.com/docs/5.5/cache#cache-tags) if they're available. Not all cache drivers support this. Refer to [Laravel's documentation](http://laravel.com/docs/5.5/cache#cache-tags) to see if your driver supports cache tags. If your driver does not support cache tags, calling `refresh` might be a little slow, depending on the amount of users in your system.

Alternatively, you can refresh the cache only for a specific user:

```php
Bouncer::refreshFor($user);
```

## Configuration

Bouncer ships with sensible defaults, so most of the time there should be no need for any configuration. For finer-grained control, Bouncer can be customized by calling various configuration methods on the `Bouncer` class.

If you only use one or two of these config options, you can stick them into your [main `AppServiceProvider`'s `boot` method](https://github.com/laravel/laravel/blob/bf3785d/app/Providers/AppServiceProvider.php#L14-L17). If they start growing, you may create a separate `BouncerServiceProvider` class in [your `app/Providers` directory](https://github.com/laravel/laravel/tree/bf3785d0bc3cd166119d8ed45c2f869bbc31021c/app/Providers) (remember to register it in [the `providers` config array](https://github.com/laravel/laravel/blob/bf3785d0bc3cd166119d8ed45c2f869bbc31021c/config/app.php#L140-L145)). 

### Cache

By default, all queries executed by Bouncer are cached for the current request. For better performance, you may want to use cross-request caching:

```php
Bouncer::cache();
```

> **Warning:** if you enable cross-request caching, you are responsible to refresh the cache whenever you make changes to user's roles/abilities. For how to refresh the cache, read [refreshing the cache](#refreshing-the-cache).

On the contrary, you may at times wish to _completely disable_ the cache, even within the same request:

```php
Bouncer::dontCache();
```

This is particularly useful in unit tests, when you want to run assertions against roles/abilities that have just been granted.

### Tables

To change the database table names used by Bouncer, pass an associative array to the `tables` method. The keys should be Bouncer's default table names, and the values should be the table names you wish to use. You do not have to pass in all tables names; only the ones you wish to change.

```php
Bouncer::tables([
    'abilities' => 'my_abilities',
    'permissions' => 'granted_abilities',
]);
```

Bouncer's published migration uses the table names from this configuration, so be sure to have these in place before actually running the migration file.

### Custom models

You can easily extend Bouncer's built-in `Role` and `Ability` models:

```php
use Silber\Bouncer\Database\Ability;

class MyAbility extends Ability
{
    // custom code
}
```

```php
use Silber\Bouncer\Database\Role;

class MyRole extends Role
{
    // custom code
}
```

Alternatively, you can use Bouncer's `IsAbility` and `IsRole` traits without actually extending any of Bouncer's models:

```php
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Concerns\IsAbility;

class MyAbility extends Model
{
    use IsAbility;

    // custom code
}
```

```php
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Concerns\IsRole;

class MyRole extends Model
{
    use IsRole;

    // custom code
}
```

If you use the traits instead of extending Bouncer's models, be sure to set the proper `$table` name and `$fillable` fields yourself.

Regardless of which method you use, the next step is to actually tell Bouncer to use your custom models:

```php
Bouncer::useAbilityModel(MyAbility::class);
Bouncer::useRoleModel(MyRole::class);
```

### User Model

By default, Bouncer automatically [uses the user model of the default auth guard](https://github.com/JosephSilber/bouncer/blob/462f312/src/BouncerServiceProvider.php#L171-L190).

If you're using Bouncer with a non-default guard, and it uses a different user model, you should let Bouncer know about the user model you want to use:

```php
Bouncer::useUserModel(\App\Admin::class);
```

### Ownership

In Bouncer, the concept of ownership is used to [allow users to perform actions on models they "own"](#allowing-a-user-or-role-to-own-a-model).

By default, Bouncer will check the model's `user_id` against the current user's primary key. If needed, this can be set to a different attribute:

```php
Bouncer::ownedVia('userId');
```

If different models use different columns for ownership, you can register them separately:

```php
Bouncer::ownedVia(Post::class, 'created_by');
Bouncer::ownedVia(Order::class, 'entered_by');
```

For greater control, you can pass a closure with your custom logic:

```php
Bouncer::ownedVia(Game::class, function ($game, $user) {
    return $game->team_id == $user->team_id;
});
```

## Cheat Sheet

```php
// Adding abilities for users
Bouncer::allow($user)->to('ban-users');
Bouncer::allow($user)->to('edit', Post::class);
Bouncer::allow($user)->to('delete', $post);

Bouncer::allow($user)->everything();
Bouncer::allow($user)->toManage(Post::class);
Bouncer::allow($user)->toManage($post);
Bouncer::allow($user)->to('view')->everything();

Bouncer::allow($user)->toOwn(Post::class);
Bouncer::allow($user)->toOwnEverything();

// Removing abilities uses the same syntax, e.g.
Bouncer::disallow($user)->to('delete', $post);
Bouncer::disallow($user)->toManage(Post::class);
Bouncer::disallow($user)->toOwn(Post::class);

// Adding & removing abilities for roles
Bouncer::allow('admin')->to('ban-users');
Bouncer::disallow('admin')->to('ban-users');

// Re-sync a user's abilities
Bouncer::sync($user)->abilities($abilities);

// Assigning & retracting roles from users
Bouncer::assign('admin')->to($user);
Bouncer::retract('admin')->from($user);

// Re-sync a user's roles
Bouncer::sync($user)->roles($roles);

$boolean = Bouncer::can('ban-users');
$boolean = Bouncer::can('edit', Post::class);
$boolean = Bouncer::can('delete', $post);

$boolean = Bouncer::cannot('ban-users');
$boolean = Bouncer::cannot('edit', Post::class);
$boolean = Bouncer::cannot('delete', $post);

$boolean = Bouncer::is($user)->a('subscriber');
$boolean = Bouncer::is($user)->an('admin');
$boolean = Bouncer::is($user)->notA('subscriber');
$boolean = Bouncer::is($user)->notAn('admin');
$boolean = Bouncer::is($user)->a('moderator', 'editor');
$boolean = Bouncer::is($user)->all('moderator', 'editor');

Bouncer::cache();
Bouncer::dontCache();

Bouncer::refresh();
Bouncer::refreshFor($user);
```

Some of this functionality is also available directly on the user model:

```php
$user->allow('ban-users');
$user->allow('edit', Post::class);
$user->allow('delete', $post);

$user->disallow('ban-users');
$user->disallow('edit', Post::class);
$user->disallow('delete', $post);

$user->assign('admin');
$user->retract('admin');

$boolean = $user->isAn('admin');
$boolean = $user->isAn('editor', 'moderator');
$boolean = $user->isAll('moderator', 'editor');
$boolean = $user->isNot('subscriber', 'moderator');

$abilities = $user->getAbilities();
```

## Alternative

Among the bajillion packages that [Spatie](https://spatie.be) has so graciously bestowed upon the community, you'll find the excellent [laravel-permission](https://github.com/spatie/laravel-permission) package. Like Bouncer, it nicely integrates with Laravel's built-in gate and permission checks, but has a different set of design choices when it comes to syntax, DB structure & features. [Povilas Korop](https://twitter.com/@povilaskorop) did an excellent job comparing the two [in an article on Laravel News](https://laravel-news.com/two-best-roles-permissions-packages).

## License

Bouncer is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
