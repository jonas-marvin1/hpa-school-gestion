<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionQuota extends Model
{
    use HasFactory;

    protected $fillable = ['course_class_id', 'year', 'month', 'quota'];

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }
}
