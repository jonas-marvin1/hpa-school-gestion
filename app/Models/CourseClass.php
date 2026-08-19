<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseClass extends Model
{
    /** @use HasFactory<\Database\Factories\CourseClassFactory> */
    use HasFactory;

    protected $fillable = ['level_id', 'name', 'start_date', 'end_date', 'location'];

    public function users()
    {
        // withPivot('role') est indispensable : sans lui, le role porte par
        // le pivot n'est pas charge et tout filtrage sur ce role renvoie vide.
        return $this->belongsToMany(User::class, 'course_class_user')->withPivot('role');
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }
}
