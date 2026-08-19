<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseMaterial extends Model
{
    /** @use HasFactory<\Database\Factories\CourseMaterialFactory> */
    use HasFactory;

    protected $fillable = ['course_class_id', 'title', 'type', 'file_path'];
}
