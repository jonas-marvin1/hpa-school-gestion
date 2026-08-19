<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Toutes les minutes, et non une fois par jour : le rappel de cours doit
// partir 20 minutes avant la seance, ce qu'un passage quotidien a 08:00 ne
// permet pas. La commande est peu couteuse et ne fait rien s'il n'y a rien
// a envoyer. withoutOverlapping evite qu'un passage lent en chevauche un autre.
Schedule::command('reminders:send')
    ->everyMinute()
    ->withoutOverlapping();
