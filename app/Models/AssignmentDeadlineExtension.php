<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentDeadlineExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'previous_due_date',
        'new_due_date',
        'motif',
        'extended_by',
    ];

    protected $casts = [
        'previous_due_date' => 'datetime',
        'new_due_date' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    // Nul = prolongation collective (ligne d'historique uniquement, voir migration).
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function extendedBy()
    {
        return $this->belongsTo(User::class, 'extended_by');
    }
}
