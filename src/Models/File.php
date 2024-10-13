<?php

namespace Vaskiq\LaravelFileLayer\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $table = 'file_layer_files';

    protected $fillable = [
        'name',
        'path',
        'type',
        'size',
        'mime_type',
        'extension',
        'created_at',
        'updated_at',
    ];
}
