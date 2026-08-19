<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    /** @use HasFactory<\Database\Factories\ClassSessionFactory> */
    use HasFactory;

    protected $fillable = ['course_class_id', 'coach_id', 'start_time', 'end_time', 'status', 'intervention_type', 'amount', 'online_link', 'payment_id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Tolerance, en minutes, accordee de part et d'autre des horaires reels de
     * la seance pour saisir le rapport de presence.
     */
    public const MARGE_RAPPORT_MINUTES = 15;

    /** Debut de la fenetre de saisie du rapport. */
    public function debutFenetreRapport()
    {
        return $this->start_time->copy()->subMinutes(self::MARGE_RAPPORT_MINUTES);
    }

    /** Fin de la fenetre de saisie du rapport. */
    public function finFenetreRapport()
    {
        return $this->end_time->copy()->addMinutes(self::MARGE_RAPPORT_MINUTES);
    }

    /** Le coach peut-il saisir le rapport maintenant ? */
    public function fenetreRapportOuverte()
    {
        return now()->between($this->debutFenetreRapport(), $this->finFenetreRapport());
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function sessionReport()
    {
        return $this->hasOne(SessionReport::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
