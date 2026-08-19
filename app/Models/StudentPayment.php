<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'program_id',
        'payment_plan_id',
        'amount',
        'due_date',
        'paid_date',
        'status',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /** Plan de paiement auquel cette echeance appartient (facultatif). */
    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }
}
