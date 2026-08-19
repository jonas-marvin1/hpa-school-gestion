<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\PaymentDueNotification;
use App\Notifications\AssignmentReminderNotification;

class SendMockNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mock:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send mock notifications to test the bell system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $students = User::role('student')->get();
        foreach ($students as $student) {
            $student->notify(new PaymentDueNotification([
                'amount' => '150 €',
                'due_date' => now()->addDays(3)->format('d/m/Y')
            ]));
            $student->notify(new AssignmentReminderNotification([
                'class_name' => 'Promotion 2024',
                'action_url' => route('student.dashboard')
            ]));
        }

        $coaches = User::role('coach')->get();
        foreach ($coaches as $coach) {
            $coach->notify(new AssignmentReminderNotification([
                'class_name' => 'Promotion 2024 - A',
                'action_url' => route('coach.dashboard')
            ]));
        }

        $this->info('Mock notifications sent successfully to students and coaches!');
    }
}
