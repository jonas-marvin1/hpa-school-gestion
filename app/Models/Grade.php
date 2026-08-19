<?php

namespace App\Models;

use App\Models\Concerns\RecordsRevisions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    /** @use HasFactory<\Database\Factories\GradeFactory> */
    use HasFactory;

    // Toute correction ou revision de note est consignee dans le journal.
    use RecordsRevisions;

    protected $fillable = ['submission_id', 'coach_id', 'score', 'feedback'];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
