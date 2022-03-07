<img src="https://user-images.githubusercontent.com/1403741/39606419-587dbb1e-4f03-11e8-8e54-1bb2f39fb0f5.jpg">

# Bouncer

<p>
<a href="https://github.com/JosephSilber/bouncer/actions"><img src="https://github.com/JosephSilber/bouncer/workflows/Tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/silber/bouncer"><img src="https://poser.pugx.org/silber/bouncer/d/total.svg" alt="Total Downloads"></a>
<a href="https://github.com/JosephSilber/bouncer/blob/master/LICENSE.txt"><img src="https://poser.pugx.org/silber/bouncer/license.svg" alt="License"></a>
</p>

Bouncer is an elegant, framework-agnostic approach to managing roles and abilities for any app using Eloquent models.

## Table of Contents

<details><summary>Click to expand</summary><p>

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
  - [Forbidding an ability](#forbidding-an-ability)
  - [Unforbidding an ability](#unforbidding-an-ability)
  - [Checking a user's roles](#checking-a-users-roles)
  - [Querying users by their roles](#querying-users-by-their-roles)
  - [Getting all roles for a user](#getting-all-roles-for-a-user)
  - [Getting all abilities for a user](#getting-all-abilities-for-a-user)
  - [Authorizing users](#authorizing-users)
  - [Blade directives](#blade-directives)
  - [Refreshing the cache](#refreshing-the-cache)
- [Multi-tenancy](#multi-tenancy)
  - [The scope middleware](#the-scope-middleware)
  - [Customizing Bouncer's scope](#customizing-bouncers-scope)
- [Configuration](#configuration)
  - [Cache](#cache)
  - [Tables](#tables)
  - [Custom models](#custom-models)
  - [User Model](#user-model)
  - [Ownership](#ownership)
- [FAQ](#faq)
  - [Where do I set up my app's roles and abilities?](#where-do-i-set-up-my-apps-roles-and-abilities)
  - [Can I use a different set of roles & abilities for the public & dashboard sections of my site, respectively?](#can-i-use-a-different-set-of-roles--abilities-for-the-public--dashboard-sections-of-my-site-respectively)
  - [I'm trying to run the migration, but I'm getting a SQL error that the "specified key was too long"](#im-trying-to-run-the-migration-but-im-getting-a-sql-error-that-the-specified-key-was-too-long)
  - [I'm trying to run the migration, but I'm getting a SQL error that there is a "Syntax error or access violation: 1064 ... to use near json not null)"](#im-trying-to-run-the-migration-but-im-getting-a-sql-error-that-there-is-a-syntax-error-or-access-violation-1064--to-use-near-json-not-null)
- [Console commands](#console-commands)
  - [`bouncer:clean`](#bouncerclean)
- [Cheat sheet](#cheat-sheet)
- [Alternative](#alternative)
- [License](#license)
</p></details>

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

When you check abilities at Laravel's gate, Bouncer will automatically be consulted. If Bouncer sees an ability that has been granted to the current user (whether directly, or through a role) it'll authorize the check.

## Installation

> **Note**: Bouncer requires PHP 7.2+ and Laravel/Eloquent 6.0+
> 
> If you're not up to date, use [Bouncer RC6](https://github.com/JosephSilber/bouncer/tree/v1.0.0-rc.6). It supports all the way back to PHP 5.5 & Laravel 5.1, and has no known bugs.

### Installing Bouncer in a Laravel app

1) Install Bouncer with [composer](https://getcomposer.org/doc/00-intro.md):

    ```
    composer require silber/bouncer
    ```

2) Add Bouncer's trait to your user model:

    ```php
    use Silber\Bouncer\Database\HasRolesAndAbilities;

    class User extends Model
    {
        use HasRolesAndAbilities;
    }
    ```

3) Now, to run Bouncer's migrations. First publish the migrations into your app's `migrations` directory, by running the following command:

    ```
    php artisan vendor:publish --tag="bouncer.migrations"
    ```

4) Finally, run the migrations:

    ```
    php artisan migrate
    ```

#### Facade

Whenever you use the `Bouncer` facade in your code, remember to add this line to your namespace imports at the top of the file:

```php
use Bouncer;
```

For more information about Laravel Facades, refer to [the Laravel documentation](https://laravel.com/docs/9.x/facades).

### Installing Bouncer in a non-Laravel app

1) Install Bouncer with [composer](https://getcomposer.org/doc/00-intro.md):

    ```
    composer require silber/bouncer
    ```

2) Set up the database with [the Eloquent Capsule component](https://github.com/illuminate/database/blob/master/README.md):

    ```php
    use Illuminate\Database\Capsule\Manager as Capsule;

    $capsule = new Capsule;

    $capsule->addConnection([/* connection config */]);

    $capsule->setAsGlobal();
    ```

    Refer to [the Eloquent Capsule documentation](https://github.com/illuminate/database/blob/master/README.md) for more details.

3) Run the migrations by either of the following methods:

    - Use a tool such as [vagabond](https://github.com/michaeldyrynda/vagabond) to run Laravel migrations outside of a Laravel app. You'll find the necessary migrations in [the migrations stub file](https://github.com/JosephSilber/bouncer/blob/master/migrations/create_bouncer_tables.php#L18-L79).

    - Alternatively, you can run [the raw SQL](https://github.com/JosephSilber/bouncer/blob/master/migrations/sql/MySQL.sql) directly in your database.

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

    If you're using dependency injection in your app, you may register the `Bouncer` instance as a singleton in the container:

    ```php
    use Silber\Bouncer\Bouncer;
    use Illuminate\Container\Container;

    Container::getInstance()->singleton(Bouncer::class, function () {
        return Bouncer::create();
    });
    ```

    You can now inject `Bouncer` into any class that needs it.

    The `create` method creates a `Bouncer` instance with sensible defaults. To fully customize it, use the `make` method to get a factory instance. Call `create()` on the factory to create the `Bouncer` instance:

    ```php
    use Silber\Bouncer\Bouncer;

    $bouncer = Bouncer::make()
             ->withCache($customCacheInstance)
             ->create();
    ```

    Check out [the `Factory` class](https://github.com/JosephSilber/bouncer/blob/c974953a0b1d8d187023002cdfae1800f3ccdb02/src/Factory.php) to see all the customizations available.

6) Set which model is used as the user model throughout your app:

    ```php
    $bouncer->useUserModel(User::class);
    ```

    For additional configuration, check out [the Configuration section](#configuration) below.

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

If you want to add additional attributes to the role/ability, such as a human-readable title, you can manually create them using the `role` and `ability` methods on the `Bouncer` class:

```php
$admin = Bouncer::role()->firstOrCreate([
    'name' => 'admin',
    'title' => 'Administrator',
]);

$ban = Bouncer::ability()->firstOrCreate([
    'name' => 'ban-users',
    'title' => 'Ban users',
]);

Bouncer::allow($admin)->to($ban);
```

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

> **Note:** if the user has a role that allows them to `ban-users`, they will still have that ability. To disallow it, either remove the ability from the role or retract the role from the user.

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

> **Note**: the `disallow` method only removes abilities that were previously given to this user/role. If you want to disallow a subset of what a more-general ability has allowed, use [the `forbid` method](#forbidding-an-ability).

### Forbidding an ability

Bouncer also allows you to `forbid` a given ability, for more fine-grained control. At times you may wish to grant a user/role an ability that covers a wide range of actions, but then restrict a small subset of those actions.

Here are some examples:

- You might allow a user to generally view all documents, but have a specific highly-classified document that they should not be allowed to view:

    ```php
    Bouncer::allow($user)->to('view', Document::class);

    Bouncer::forbid($user)->to('view', $classifiedDocument);
    ```

- You may wish to allow your `superadmin`s to do everything in your app, including adding/removing users. Then you may have an `admin` role that can do everything _besides_ managing users:

    ```php
    Bouncer::allow('superadmin')->everything();

    Bouncer::allow('admin')->everything();
    Bouncer::forbid('admin')->toManage(User::class);
    ```

- You may wish to occasionally ban users, removing their permission to all abilities. However, actually removing all of their roles & abilities would mean that when the ban is removed we'll have to figure out what their original roles and abilities were.

    Using a forbidden ability means that they can keep all their existing roles and abilities, but still not be authorized for anything. We can accomplish this by creating a special `banned` role, for which we'll forbid everything:

    ```php
    Bouncer::forbid('banned')->everything();
    ```

    Then, whenever we want to ban a user, we'll assign them the `banned` role:

    ```php
    Bouncer::assign('banned')->to($user);
    ```

    To remove the ban, we'll simply retract the role from the user:

    ```php
    Bouncer::retract('banned')->from($user);
    ```

As you can see, Bouncer's forbidden abilities gives you a lot of granular control over the permissions in your app.

### Unforbidding an ability

To remove a forbidden ability, use the `unforbid` method:

```php
Bouncer::unforbid($user)->to('view', $classifiedDocument);
```

> **Note**: this will remove any previously-forbidden ability. It will _not_ authomatically allow the ability if it's not already allowed by a different regular ability granted to this user/role.

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

### Querying users by their roles

You can query your users by whether they have a given role:

```php
$users = User::whereIs('admin')->get();
```

You may also pass in multiple roles, to query for users that have _any_ of the given roles:

```php
$users = User::whereIs('superadmin', 'admin')->get();
```

To query for users who have _all_ of the given roles, use the `whereIsAll` method:

```php
$users = User::whereIsAll('sales', 'marketing')->get();
```

### Getting all roles for a user

You can get all roles for a user directly from the user model:

```php
$roles = $user->getRoles();
```

### Getting all abilities for a user

You can get all abilities for a user directly from the user model:

```php
$abilities = $user->getAbilities();
```

This will return a collection of the user's allowed abilities, including any abilities granted to the user through their roles.

You can also get a list of abilities that have been _explicitly_ forfidden:

```php
$forbiddenAbilities = $user->getForbiddenAbilities();
```

### Authorizing users

Authorizing users is handled directly at [Laravel's `Gate`](https://laravel.com/docs/9.x/authorization#gates), or on the user model (`$user->can($ability)`).

For convenience, the `Bouncer` class provides these passthrough methods:

```php
Bouncer::can($ability);
Bouncer::can($ability, $model);

Bouncer::canAny($abilities);
Bouncer::canAny($abilities, $model);

Bouncer::cannot($ability);
Bouncer::cannot($ability, $model);

Bouncer::authorize($ability);
Bouncer::authorize($ability, $model);
```

These call directly into their equivalent methods on the `Gate` class.

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

> **Note:** fully refreshing the cache for all users uses [cache tags](https://laravel.com/docs/9.x/cache#cache-tags) if they're available. Not all cache drivers support this. Refer to [Laravel's documentation](https://laravel.com/docs/9.x/cache#cache-tags) to see if your driver supports cache tags. If your driver does not support cache tags, calling `refresh` might be a little slow, depending on the amount of users in your system.

Alternatively, you can refresh the cache only for a specific user:

```php
Bouncer::refreshFor($user);
```

## Multi-tenancy

Bouncer fully supports multi-tenant apps, allowing you to seamlessly integrate Bouncer's roles and abilities for all tenants within the same app.

### The scope middleware

To get started, first publish [the scope middleware](https://github.com/JosephSilber/bouncer/blob/master/middleware/ScopeBouncer.php) into your app:

```
php artisan vendor:publish --tag="bouncer.middleware"
```

The middleware will now be published to `app/Http/Middleware/ScopeBouncer.php`. This middleware is where you tell Bouncer which tenant to use for the current request. For example, assuming your users all have an `account_id` attribute, this is what your middleware would look like:

```php
public function handle($request, Closure $next)
{
    $tenantId = $request->user()->account_id;

    Bouncer::scope()->to($tenantId);

    return $next($request);
}
```

You are of course free to modify this middleware to fit your app's needs, such as pulling the tenant information from a subdomain et al.

Now with the middleware in place, be sure to register it in your [HTTP Kernel](https://github.com/laravel/laravel/blob/73cff166c79cdeaef1c6b7ec6e71a33a7ea3012d/app/Http/Kernel.php#L30-L38):

```php
protected $middlewareGroups = [
    'web' => [
        // Keep the existing middleware here, and add this:
        \App\Http\Middleware\ScopeBouncer::class,
    ]
];
```

All of Bouncer's queries will now be scoped to the given tenant.

### Customizing Bouncer's scope

Depending on your app's setup, you may not actually want _all_ of the queries to be scoped to the current tenant. For example, you may have a fixed set of roles/abilities that are the same for all tenants, and only allow your users to control which users are assigned which roles, and which roles have which abilities. To achieve this, you can tell Bouncer's scope to only scope the relationships between Bouncer's models, but not the models themselves:

```php
Bouncer::scope()->to($tenantId)->onlyRelations();
```

Furthermore, your app might not even allow its users to control which abilities a given role has. In that case, tell Bouncer's scope to exclude role abilities from the scope, so that those relationships stay global across all tenants:

```php
Bouncer::scope()->to($tenantId)->onlyRelations()->dontScopeRoleAbilities();
```

If your needs are even more specialized than what's outlined above, you can create your own [`Scope`](https://github.com/JosephSilber/bouncer/blob/ab2b92d4d2379be3220daaf0d4185ea10237ff2b/src/Contracts/Scope.php) with whatever custom logic you need:

```php
use Silber\Bouncer\Contracts\Scope;

class MyScope implements Scope
{
    // Whatever custom logic your app needs
}
```

Then, in a service provider, register your custom scope:

```php
Bouncer::scope(new MyScope);
```

Bouncer will call the methods on the `Scope` interface at various points in its execution. You are free to handle them according to your specific needs.

## Configuration

Bouncer ships with sensible defaults, so most of the time there should be no need for any configuration. For finer-grained control, Bouncer can be customized by calling various configuration methods on the `Bouncer` class.

If you only use one or two of these config options, you can stick them into your [main `AppServiceProvider`'s `boot` method](https://github.com/laravel/laravel/blob/e077976680bdb2644698fb8965a1e2a8710b5d4b/app/Providers/AppServiceProvider.php#L24-L27). If they start growing, you may create a separate `BouncerServiceProvider` class in [your `app/Providers` directory](https://github.com/laravel/laravel/tree/e077976680bdb2644698fb8965a1e2a8710b5d4b/app/Providers) (remember to register it in [the `providers` config array](https://github.com/laravel/laravel/blob/e077976680bdb2644698fb8965a1e2a8710b5d4b/config/app.php#L171-L178)).

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

Bouncer's published migration uses the table names from this configuration, so be sure to have these in place before actually running the migration.

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

## FAQ

There are some concepts in Bouncer that people keep on asking about, so here's a short list of some of those topics:

### Where do I set up my app's roles and abilities?

Seeding the initial roles and abilities can be done in a regular [Laravel seeder](https://laravel.com/docs/9.x/seeding) class. Start by creating a specific seeder file for Bouncer:

```
php artisan make:seeder BouncerSeeder
```

Place all of your seeding roles & abilities code in [the seeder's `run` method](https://github.com/laravel/framework/blob/f50e2004dfa40de895cd841a0a94acef5b417900/src/Illuminate/Database/Console/Seeds/stubs/seeder.stub#L12-L15). Here's an example of what that might look like:

```php
use Bouncer;
use Illuminate\Database\Seeder;

class BouncerSeeder extends Seeder
{
    public function run()
    {
        Bouncer::allow('superadmin')->everything();

        Bouncer::allow('admin')->everything();
        Bouncer::forbid('admin')->toManage(User::class);

        Bouncer::allow('editor')->to('create', Post::class);
        Bouncer::allow('editor')->toOwn(Post::class);

        // etc.
    }
}
```


To actually run it, pass the seeder's class name to the `class` option of the `db:seed` command:

```
php artisan db:seed --class=BouncerSeeder
```

### Can I use a different set of roles & abilities for the public & dashboard sections of my site, respectively?

Bouncer's [`scope`](#the-scope-middleware) can be used to section off different parts of the site, creating a silo for each one of them with its own set of roles & abilities:

1. Create a `ScopeBouncer` [middleware](https://laravel.com/docs/9.x/middleware#defining-middleware) that takes an `$identifier` and sets it as the current scope:

    ```php
    use Bouncer, Closure;

    class ScopeBouncer
    {
        public function handle($request, Closure $next, $identifier)
        {
            Bouncer::scope()->to($identifier);

            return $next($request);
        }
    }
    ```

2. Register this new middleware as a route middleware in your [HTTP Kernel class](https://github.com/laravel/laravel/blob/73cff166c79cdeaef1c6b7ec6e71a33a7ea3012d/app/Http/Kernel.php#L53-L60):

    ```php
    protected $routeMiddleware = [
        // Keep the other route middleware, and add this:
        'scope-bouncer' => \App\Http\Middleware\ScopeBouncer::class,
    ];
    ```

3. In your [route service provider](https://github.com/laravel/laravel/blob/73cff166c79cdeaef1c6b7ec6e71a33a7ea3012d/app/Providers/RouteServiceProvider.php), apply this middleware with a different identifier for the public routes and the dashboard routes, respectively:

    ```php
    Route::middleware(['web', 'scope-bouncer:1'])
         ->namespace($this->namespace)
         ->group(base_path('routes/public.php'));

    Route::middleware(['web', 'scope-bouncer:2'])
         ->namespace($this->namespace)
         ->group(base_path('routes/dashboard.php'));
    ```

That's it. All roles and abilities will now be separately scoped for each section of your site. To fine-tune the extent of the scope, see [Customizing Bouncer's scope](#customizing-bouncers-scope).

### I'm trying to run the migration, but I'm getting a SQL error that the "specified key was too long"

Starting with Laravel 5.4, the default database character set is now `utf8mb4`. If you're using older versions of some databases (MySQL below 5.7.7, or MariaDB below 10.2.2) with Laravel 5.4+, you'll get a SQL error when trying to create an index on a string column. To fix this, change Laravel's default string length in your `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Schema;

public function boot()
{
    Schema::defaultStringLength(191);
}
```

You can read more in [this Laravel News article](https://laravel-news.com/laravel-5-4-key-too-long-error).

## I'm trying to run the migration, but I'm getting a SQL error that there is a "Syntax error or access violation: 1064 ... to use near json not null)"

JSON columns are a relatively new addition to MySQL (5.7.8) and MariaDB (10.2.7). If you're using an older version of these databases, you cannot use JSON columns.

The best solution would be to upgrade your DB. If that's not currently possible, you can change [your published migration file](https://github.com/JosephSilber/bouncer/blob/2e31b84e9c1f6c2b86084df2af9d05299ba73c62/migrations/create_bouncer_tables.php#L25) to use a `text` column instead:

```diff
- $table->json('options')->nullable();
+ $table->text('options')->nullable();
```

## Console commands

### `bouncer:clean`

The `bouncer:clean` command deletes unused abilities. Running this command will delete 2 types of unused abilities:

- **Unassigned abilities** - abilities that are not assigned to anyone. For example:

    ```php
    Bouncer::allow($user)->to('view', Plan::class);

    Bouncer::disallow($user)->to('view', Plan::class);
    ```

    At this point, the "view plans" ability is not assigned to anyone, so it'll get deleted.

    > **Note**: depending on the context of your app, you may not want to delete these. If you let your users manage abilities in your app's UI, you probably _don't_ want to delete unassigned abilities. See below.

- **Orphaned abilities** - model abilities whose models have been deleted:

    ```php
    Bouncer::allow($user)->to('delete', $plan);

    $plan->delete();
    ```

    Since the plan no longer exists, the ability is no longer of any use, so it'll get deleted.

If you only want to delete one type of unused ability, run it with one of the following flags:

```
php artisan bouncer:clean --unassigned
php artisan bouncer:clean --orphaned
```

If you don't pass it any flags, it will delete both types of unused abilities.

To automatically run this command periodically, add it to [your console kernel's schedule](https://laravel.com/docs/9.x/scheduling#defining-schedules):

```php
$schedule->command('bouncer:clean')->weekly();
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

// You can also forbid specific abilities with the same syntax...
Bouncer::forbid($user)->to('delete', $post);

// And also remove a forbidden ability with the same syntax...
Bouncer::unforbid($user)->to('delete', $post);

// Re-syncing a user's abilities
Bouncer::sync($user)->abilities($abilities);

// Assigning & retracting roles from users
Bouncer::assign('admin')->to($user);
Bouncer::retract('admin')->from($user);

// Assigning roles to multiple users by ID
Bouncer::assign('admin')->to([1, 2, 3]);

// Re-syncing a user's roles
Bouncer::sync($user)->roles($roles);

// Checking the current user's abilities
$boolean = Bouncer::can('ban-users');
$boolean = Bouncer::can('edit', Post::class);
$boolean = Bouncer::can('delete', $post);

$boolean = Bouncer::cannot('ban-users');
$boolean = Bouncer::cannot('edit', Post::class);
$boolean = Bouncer::cannot('delete', $post);

// Checking a user's roles
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
$boolean = $user->isNotAn('admin', 'moderator');

// Querying users by their roles
$users = User::whereIs('superadmin')->get();
$users = User::whereIs('superadmin', 'admin')->get();
$users = User::whereIsAll('sales', 'marketing')->get();

$abilities = $user->getAbilities();
$forbidden = $user->getForbiddenAbilities();
```

## Alternative

Among the bajillion packages that [Spatie](https://spatie.be) has so graciously bestowed upon the community, you'll find the excellent [laravel-permission](https://github.com/spatie/laravel-permission) package. Like Bouncer, it nicely integrates with Laravel's built-in gate and permission checks, but has a different set of design choices when it comes to syntax, DB structure & features.

## License

Bouncer is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
