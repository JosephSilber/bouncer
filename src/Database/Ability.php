<?php

namespace Silber\Bouncer\Database;

use Illuminate\Database\Eloquent\Model;

class Ability extends Model
{
    use Concerns\IsAbility;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'title'];

    /**
     * Constructor.
     *
     * @param array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Models::table('abilities');

        parent::__construct($attributes);
    }
    
    /**
     * Return a sluged title that contains the entity type and id if they exists
     *
     * @return string
     */
    public function getFullTitleAttribute()
    {
        $fullTitle[] = $this->title;
        $fullTitle[] = class_basename($this->entity_type) ?: null;
        $fullTitle[] = $this->entity_id ?: null;
        $fullTitle = array_filter($fullTitle);
        return str_slug(implode(' ', $fullTitle));
    }
}
