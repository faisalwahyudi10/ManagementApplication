<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'type',
        'slug',
        'icon',
        'route',
        'order',
        'is_show',
        'is_custom',
    ];

    protected $casts = [
        'is_show' => 'boolean',
        'is_custom' => 'boolean',
        'type' => Enums\MenuType::class,
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
