<?php

namespace App\Console\Commands;

use App\Models\TimerSetting;
use Illuminate\Console\Command;

class TimerControl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timer:control {action} {hours?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Control the countdown timer (start, stop, restart, set)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $hours = $this->argument('hours');

        $timer = TimerSetting::current();

        switch ($action) {
            case 'start':
                $timer->update(['is_active' => true]);
                $this->info('✅ Timer started successfully!');
                $this->displayStatus($timer);
                break;

            case 'stop':
                $timer->update(['is_active' => false]);
                $this->info('⏸️  Timer stopped successfully!');
                $this->displayStatus($timer);
                break;

            case 'restart':
                $hoursToAdd = (int)($hours ?? 48);
                $timer->update([
                    'is_active' => true,
                    'end_time' => now()->addHours($hoursToAdd),
                ]);
                $this->info("🔄 Timer restarted with {$hoursToAdd} hours!");
                $this->displayStatus($timer);
                break;

            case 'set':
                if (!$hours) {
                    $this->error('❌ Please provide hours. Example: php artisan timer:control set 24');
                    return;
                }
                $hoursToSet = (int)$hours;
                $timer->update([
                    'end_time' => now()->addHours($hoursToSet),
                ]);
                $this->info("⏱️  Timer set to {$hoursToSet} hours from now!");
                $this->displayStatus($timer);
                break;

            case 'status':
                $this->displayStatus($timer);
                break;

            default:
                $this->error('❌ Invalid action. Available actions: start, stop, restart, set, status');
                $this->line('');
                $this->line('Examples:');
                $this->line('  php artisan timer:control start           - Start the timer');
                $this->line('  php artisan timer:control stop            - Stop the timer');
                $this->line('  php artisan timer:control restart         - Restart with 48 hours');
                $this->line('  php artisan timer:control restart 24      - Restart with 24 hours');
                $this->line('  php artisan timer:control set 12          - Set timer to 12 hours');
                $this->line('  php artisan timer:control status          - Show current status');
                break;
        }
    }

    /**
     * Display timer status
     */
    private function displayStatus(TimerSetting $timer)
    {
        $this->line('');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('📊 Timer Status');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('Status:    ' . ($timer->is_active ? '🟢 Active' : '🔴 Stopped'));
        $this->line('End Time:  ' . $timer->end_time->format('Y-m-d H:i:s'));

        if ($timer->is_active) {
            $now = now();
            $diff = $timer->end_time->diff($now);

            if ($timer->end_time->isPast()) {
                $this->line('Time Left: ⏰ Timer has expired!');
            } else {
                $hours = $diff->days * 24 + $diff->h;
                $minutes = $diff->i;
                $seconds = $diff->s;
                $this->line("Time Left: ⏰ {$hours}h {$minutes}m {$seconds}s");
            }
        }
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('');
    }
}
