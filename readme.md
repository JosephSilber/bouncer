# Bouncer

Bouncer provides a mechanism to handle simple roles and abilities in [Laravel's ACL](http://laravel.com/docs/5.1/authorization). It stays out of your way as much as possible: use it when you want, ignore it when you don't.

Bouncer works well with other abilities you have hard-coded in your own app. Your code always takes precedence: if your code allows an action, the bouncer will not interfere.

Once installed, you can simply tell the bouncer what you want to allow at the gate:

```php
// Give a user the ability to create posts
Bouncer::allow($user)->to('create-posts');

// Alternatively, do it through a role
Bouncer::allow('admin')->to('create-posts');
Bouncer::assign('admin')->to($user);
```

When you check abilities at the gate, the bouncer will be consulted first. If he sees an ability that has been granted to the current user (whether directly, or through a role) he'll authorize the check.

## Install

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

## Usage

Adding roles and abilities to users is made extremely easy. You do not have to create a role or an ability in advance. Simply pass the name of the role/ability, and Bouncer will create it if it doesn't exist.

### Creating roles and abilities

Let's create a role called `admin`, give it the ability to `ban-users` from our site.

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

Sometimes, you might want to give a user an ability directly, without using a role. Simply tell the bouncer to give the ability directly to the user:

```php
Bouncer::allow($user)->to('ban-users');
```

Here too you can accomplish the same directly off of the user model:

```php
$user->allow('ban-users');
```

### Retracting a role from a user

To retract a role that has been given to a user, tell the bouncer to retract the given role:

```php
Bouncer::retract('ban-users')->from($user);
```

Or do it directly on the user:

```php
$user->retract('ban-users');
```

### Removing an ability that has been previously granted

The bouncer can also remove an ability previously granted to a user:

```php
Bouncer::disallow($user)->to('ban-users');
```

Or directly on the model:

```php
$user->disallow('ban-users');
```

If the ability has been granted through a role, tell the bouncer to remove the ability from the role instead:

```php
Bouncer::disallow('admin')->to('ban-users');
```

### License

Bouncer is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
