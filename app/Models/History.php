<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

# Семантически неправильное название
# Лучше подошло бы Promt
class History extends Model
{
    protected $fillable = [
        'user_id',
        'prompt',
        'image_path'
    ];
}
