<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Ability;

use RuntimeException;
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
     * @param \Illuminate\Contracts\Cache\Store $cache
     * @param string $roleModelClass
     * @param string $abilityModelClass
     */
    public function __construct($cache, $roleModelClass = 'Silber\Bouncer\Database\Role', $abilityModelClass = 'Silber\Bouncer\Database\Ability')
    {
        parent::__construct($roleModelClass, $abilityModelClass);
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
     * @param  null|\Illuminate\Database\Eloquent\Model  $user
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function refresh(Model $user = null)
    {
        if ( ! is_null($user)) {
            return $this->refreshFor($user);
        }

        if ( ! $this->cache instanceof TaggedCache) {
            throw new RuntimeException('Your cache driver does not support blanket cache purging. Use [refreshFor] instead.');
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
    public function refreshFor(Model $user)
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
        return call_user_func($this->abilityModelClass."::hydrate", $abilities);
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
