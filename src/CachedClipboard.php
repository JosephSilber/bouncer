<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Contracts\CachedClipboard as CachedClipboardContract;

use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class CachedClipboard extends Clipboard implements CachedClipboardContract
{
    /**
     * The tag used for caching.
     *
     * @var string
     */
    protected $tag = 'silber-bouncer';

    /**
     * The cache store.
     *
     * @var \Illuminate\Contracts\Cache\Store
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param \Illuminate\Contracts\Cache\Store  $cache
     */
    public function __construct(Store $cache)
    {
        $this->setCache($cache);
    }

    /**
     * Set the cache instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $cache
     * @return $this
     */
    public function setCache(Store $cache)
    {
        if (method_exists($cache, 'tags')) {
            $cache = $cache->tags($this->tag);
        }

        $this->cache = $cache;

        return $this;
    }

    /**
     * Get the cache instance.
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Get the given authority's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  bool  $allowed
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $authority, $allowed = true)
    {
        $key = $this->getCacheKey($authority, 'abilities', $allowed);

        if (is_array($abilities = $this->cache->get($key))) {
            return $this->deserializeAbilities($abilities);
        }

        $abilities = $this->getFreshAbilities($authority, $allowed);

        $this->cache->forever($key, $this->serializeAbilities($abilities));

        return $abilities;
    }

    /**
     * Get a fresh copy of the given authority's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  bool  $allowed
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFreshAbilities(Model $authority, $allowed)
    {
        return parent::getAbilities($authority, $allowed);
    }

    /**
     * Get the given authority's roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $authority)
    {
        $key = $this->getCacheKey($authority, 'roles');

        return $this->sear($key, function () use ($authority) {
            return parent::getRoles($authority);
        });
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string  $key
     * @param  callable  $callback
     * @return mixed
     */
    protected function sear($key, callable $callback)
    {
        if (is_null($value = $this->cache->get($key))) {
            $this->cache->forever($key, $value = $callback());
        }

        return $value;
    }

    /**
     * Clear the cache.
     *
     * @param  null|\Illuminate\Database\Eloquent\Model  $authority
     * @return $this
     */
    public function refresh($authority = null)
    {
        if ( ! is_null($authority)) {
            return $this->refreshFor($authority);
        }

        if ($this->cache instanceof TaggedCache) {
            $this->cache->flush();
        } else {
            $this->refreshAllIteratively();
        }

        return $this;
    }

    /**
     * Clear the cache for the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return $this
     */
    public function refreshFor(Model $authority)
    {
        $this->cache->forget($this->getCacheKey($authority, 'abilities', true));
        $this->cache->forget($this->getCacheKey($authority, 'abilities', false));
        $this->cache->forget($this->getCacheKey($authority, 'roles'));

        return $this;
    }

    /**
     * Refresh the cache for all roles and users, iteratively.
     *
     * @return void
     */
    protected function refreshAllIteratively()
    {
        foreach (Models::user()->all() as $user) {
            $this->refreshFor($user);
        }

        foreach (Models::role()->all() as $role) {
            $this->refreshFor($role);
        }
    }

    /**
     * Get the cache key for the given model's cache type.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $type
     * @param  bool  $allowed
     * @return string
     */
    protected function getCacheKey(Model $model, $type, $allowed = true)
    {
        return implode('-', [
            $this->tag,
            $type,
            $model->getMorphClass(),
            $model->getKey(),
            $allowed ? 'a' : 'f',
        ]);
    }

    /**
     * Deserialize an array of abilities into a collection of models.
     *
     * @param  array  $abilities
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function deserializeAbilities(array $abilities)
    {
        return Models::ability()->hydrate($abilities);
    }

    /**
     * Serialize a collection of ability models into a plain array.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $abilities
     * @return array
     */
    protected function serializeAbilities(Collection $abilities)
    {
        return $abilities->map(function ($ability) {
            return $ability->getAttributes();
        })->all();
    }
}
