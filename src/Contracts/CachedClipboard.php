<?php

namespace Silber\Bouncer\Contracts;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Model;

interface CachedClipboard extends Clipboard
{
    /**
     * Set the cache instance.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $cache
     * @return $this
     */
    public function setCache(Store $cache);

    /**
     * Get the cache instance.
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getCache();

    /**
     * Get the given authority's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  bool  $allowed
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbilities(Model $authority, $allowed = true);

    /**
     * Get a fresh copy of the given authority's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @param  bool  $allowed
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFreshAbilities(Model $authority, $allowed);

    /**
     * Get the given authority's roles.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return \Illuminate\Support\Collection
     */
    public function getRoles(Model $authority);

    /**
     * Clear the cache.
     *
     * @param  null|\Illuminate\Database\Eloquent\Model  $authority
     * @return $this
     */
    public function refresh($authority = null);

    /**
     * Clear the cache for the given authority.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $authority
     * @return $this
     */
    public function refreshFor(Model $authority);
}
