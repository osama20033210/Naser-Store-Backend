<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontSetting extends Model
{
    protected $fillable = ['content'];

    protected function casts(): array { return ['content' => 'array']; }
}
