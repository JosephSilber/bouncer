<?php

namespace Silber\Bouncer\Middleware;

use Illuminate\Routing\ControllerMiddlewareOptions;

trait AuthorizesResources
{
    /**
     * Authorize a resource action.
     *
     * @param  string  $name
     * @param  string  $model
     * @param  array  $options
     * @param  \Illuminate\Http\Request  $request
     * @return null|\Illuminate\Routing\ControllerMiddlewareOptions
     */
    public function authorizeResource($name, $model, array $options = [], $request = null)
    {
        $request = $request ?: request();

        $method = array_get(explode('@', $request->route()->getActionName()), 1);

        $map = [
            'index' => 'view', 'create' => 'create', 'store' => 'create', 'show' => 'view',
            'edit' => 'update', 'update' => 'update', 'delete' => 'delete',
        ];

        if (property_exists($this, 'abilityMap')) {
            $map = array_merge($map, $this->abilityMap);
        }

        if ( ! in_array($method, array_keys($map))) {
            if (class_exists(ControllerMiddlewareOptions::class)) {
                return new ControllerMiddlewareOptions($options);
            }

            return null;
        }

        $model = in_array($method, ['index', 'create', 'store']) ? $model : $name;

        return $this->middleware("can:{$map[$method]},{$model}");
    }
}
