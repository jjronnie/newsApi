<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class JobController extends Controller
{
    public function index()
    {
        $jobs = DB::table('jobs')
            ->select(['id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Jobs/Index', [
            'jobs' => $jobs,
        ]);
    }

    public function show($id)
    {
        $job = DB::table('jobs')->find($id);

        if (! $job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $payload = json_decode($job->payload, true);
        $batchId = $payload['batchId'] ?? null;

        $batch = null;
        if ($batchId) {
            $batch = DB::table('job_batches')->where('id', $batchId)->first();
        }

        $failedJob = DB::table('failed_jobs')
            ->where('payload', 'like', '%"id":"'.$job->id.'"%')
            ->orWhere('payload', 'like', '%"jobId":"'.$job->id.'"%')
            ->first();

        return response()->json([
            'job' => $job,
            'batch' => $batch,
            'failed_job' => $failedJob,
        ]);
    }
}
