<?php

namespace App\Http\Controllers;

use App\Models\TimerSetting;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    /**
     * Get timer status
     */
    public function getStatus()
    {
        $timer = TimerSetting::current();

        return response()->json([
            'success' => true,
            'timer' => [
                'is_active' => $timer->is_active,
                'end_time' => $timer->end_time->toIso8601String(),
            ],
        ]);
    }
}
