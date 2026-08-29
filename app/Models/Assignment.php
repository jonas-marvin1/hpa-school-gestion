<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    /** @use HasFactory<\Database\Factories\AssignmentFactory> */
    use HasFactory;

    protected $fillable = ['course_class_id', 'student_id', 'coach_id', 'title', 'description', 'type', 'evaluation_link', 'attachment', 'due_date'];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    // Createur de l'evaluation, quel que soit son role (coach ou
    // gestionnaire) : nom conserve tel quel, voir dette technique CLAUDE.md.
    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    // Vide = attribuee a toute la classe. Renseignee = a cet apprenant seul.
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
