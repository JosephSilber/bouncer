<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Ability;

use Exception;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class CachedClipboard extends Clipboard
{
    /**
     * The tag used for caching.
     *
     * @var string
     */
    protected $tag = 'bouncer';

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
     * Get the given user's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $user)
    {
        $key = $this->tag.'-abilities-'.$user->getKey();

        if ($abilities = $this->cache->get($key)) {
            return $this->deserializeAbilities($abilities);
        }

        $abilities = parent::getAbilities($user);

        $this->cache->forever($key, $this->serializeAbilities($abilities));

        return $abilities;
    }

    /**
     * Get the given user's roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $user)
    {
        $key = $this->tag.'-roles-'.$user->getKey();

        return $this->cache->sear($key, function () use ($user) {
            return parent::getRoles($user);
        });
    }

    /**
     * Clear the cache.
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function refresh()
    {
        if ( ! $this->cache instanceof TaggedCache) {
            throw new Exception('Your cache driver does not support blanket cache purging. Use [refreshForUser] instead.');
        }

        $this->cache->flush();

        return $this;
    }

    /**
     * Clear the cache for the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return $this
     */
    public function refreshForUser(Model $user)
    {
        $id = $user->getKey();

        $this->cache->forget($this->tag.'-abilities-'.$id);

        $this->cache->forget($this->tag.'-roles-'.$id);

        return $this;
    }

    /**
     * Deserialize an array of abilities into a collection of models.
     *
     * @param  array  $abilities
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function deserializeAbilities(array $abilities)
    {
        return Ability::hydrate($abilities);
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
