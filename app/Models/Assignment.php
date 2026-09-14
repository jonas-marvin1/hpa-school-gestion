<?php

namespace App\Models;

use Carbon\Carbon;
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

    // Sert aussi d'historique : voir la migration et dateLimitePour() ci-dessous.
    public function deadlineExtensions()
    {
        return $this->hasMany(AssignmentDeadlineExtension::class);
    }

    /**
     * Date limite effective de ce devoir pour un apprenant donne.
     *
     * Point d'entree unique pour decider si un rendu est en retard (fiche du
     * 14/09/2026, point 3) : aucun autre endroit du code ne doit lire
     * due_date directement dans ce but, voir CLAUDE.md.
     *
     * Une prolongation individuelle (student_id renseigne) fait toujours
     * foi si elle existe, meme si due_date a ete repoussee plus loin depuis
     * par une prolongation collective : c'est une decision ciblee pour cet
     * apprenant precis, elle ne se laisse pas ecraser par une decision
     * generale posterieure. A defaut, due_date reflete deja la derniere
     * prolongation collective, qui l'ecrit directement (voir le service
     * d'octroi) : il n'y a donc rien d'autre a regarder.
     */
    public function dateLimitePour(User $student): Carbon
    {
        $prolongation = $this->deadlineExtensions()
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        return $prolongation ? Carbon::parse($prolongation->new_due_date) : Carbon::parse($this->due_date);
    }
}
