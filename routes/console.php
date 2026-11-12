<?php

use Illuminate\Support\Facades\Schedule;

// Pemeliharaan rutin (butuh cron: * * * * * php artisan schedule:run).
Schedule::command('queue:prune-batches --hours=48')->daily();
Schedule::command('queue:prune-failed --hours=168')->daily();
