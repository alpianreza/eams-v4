<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $fillable = ['type', 'filename', 'path', 'size_bytes', 'status', 'created_by'];
}
