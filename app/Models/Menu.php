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
        'slug',
        'icon',
        'route',
        'order',
        'is_show',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }
}
