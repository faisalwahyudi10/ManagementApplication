<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kra8\Snowflake\HasSnowflakePrimary;
use Spatie\Permission\Models\Role as Model;

class Role extends Model
{
    use HasFactory, HasSnowflakePrimary;

    protected $fillable = ['name', 'guard_name'];
}
