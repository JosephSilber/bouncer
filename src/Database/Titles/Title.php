<?php

namespace Silber\Bouncer\Database\Titles;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

abstract class Title
{
    /**
     * The human-readable title.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Create a new title instance for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return static
     */
    public static function from(Model $model)
    {
        return new static($model);
    }

    /**
     * Convert the given string into a human-readable format.
     *
     * @param  string  $value
     * @return string
     */
    protected function humanize($value)
    {
        // Older versions of Laravel's inflector strip out spaces
        // in the original string, so we'll first swap out all
        // spaces with underscores, then convert them back.
        $value = str_replace(' ', '_', $value);

        // First we'll convert the string to snake case. Then we'll
        // convert all dashes and underscores to spaces. Finally,
        // we'll add a space before a pound (Laravel doesn't).
        $value = Str::snake($value);

        $value = preg_replace('~(?:-|_)+~', ' ', $value);

        $value = preg_replace('~([^ ])(?:#)+~', '$1 #', $value);

        return ucfirst($value);
    }

    /**
     * Get the title as a string.
     *
     * @return string
     */
    public function toString()
    {
        return $this->title;
    }
}
