<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = 'file_layer_files';

    protected $guarded = ['id'];

    protected $casts = [
        'last_modified' => 'datetime',
    ];
}
