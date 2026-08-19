<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionReport extends Model
{
    /** @use HasFactory<\Database\Factories\SessionReportFactory> */
    use HasFactory;

    protected $fillable = ['class_session_id', 'progress', 'observations', 'recommendations'];

    public function classSession()
    {
        return $this->belongsTo(ClassSession::class);
    }
}
