<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
        });

        // Retroactive association of existing payments with class sessions
        $payments = \Illuminate\Support\Facades\DB::table('payments')->get();
        foreach ($payments as $payment) {
            $sessionsIds = \Illuminate\Support\Facades\DB::table('class_sessions')
                ->where('coach_id', $payment->coach_id)
                ->whereMonth('start_time', $payment->month)
                ->whereYear('start_time', $payment->year)
                ->where('status', 'completed')
                ->whereNull('payment_id')
                ->orderBy('start_time', 'asc')
                ->limit($payment->total_sessions)
                ->pluck('id');

            if ($sessionsIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('class_sessions')
                    ->whereIn('id', $sessionsIds)
                    ->update(['payment_id' => $payment->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn('payment_id');
        });
    }
};
