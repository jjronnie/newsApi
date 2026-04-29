<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiJobLog;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class AiJobLogController extends Controller
{
    public function index()
    {
        $logs = AiJobLog::latest()->paginate(20);

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
        ]);
    }

    public function destroy(AiJobLog $log): JsonResponse
    {
        try {
            $log->delete();

            return response()->json([
                'success' => true,
                'message' => 'Log deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete log: '.$e->getMessage(),
            ], 500);
        }
    }
}
