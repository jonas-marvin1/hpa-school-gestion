<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    // Journalise les changements, dont les passages de niveau : l'historique
    // de progression se lit ainsi dans la table `revisions`, sans structure
    // dediee. Les secrets sont exclus par le trait.
    use \App\Models\Concerns\RecordsRevisions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
        'avatar',
        'password',
        'english_level_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function courseClasses()
    {
        return $this->belongsToMany(CourseClass::class)->withPivot('role');
    }

    /** Niveau d'anglais courant de l'apprenant (null tant qu'il n'est pas positionné). */
    public function englishLevel()
    {
        return $this->belongsTo(EnglishLevel::class, 'english_level_id');
    }

    /**
     * Fait passer l'apprenant au palier indique.
     *
     * La promotion est volontairement manuelle : c'est le coach ou
     * l'administrateur qui la decide. Le changement est journalise par le
     * trait RecordsRevisions, ce qui donne l'historique de progression sans
     * table supplementaire.
     */
    public function changerNiveau(EnglishLevel $niveau): void
    {
        $this->english_level_id = $niveau->id;
        $this->save();
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class, 'coach_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'coach_id');
    }
}
