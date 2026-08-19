<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = ['coach_id', 'month', 'year', 'total_sessions', 'total_amount', 'status', 'validated_by'];

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function sessions()
    {
        return $this->hasMany(ClassSession::class);
    }
}
