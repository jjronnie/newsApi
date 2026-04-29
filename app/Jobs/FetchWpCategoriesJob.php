<?php

namespace App\Jobs;

use App\Models\WpCategory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchWpCategoriesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function handle(): void
    {
        $wpUrl = config('wordpress.url');
        $username = config('wordpress.username');
        $password = config('wordpress.app_password');

        try {
            $response = Http::withBasicAuth($username, $password)
                ->get($wpUrl.'/wp-json/wp/v2/categories', [
                    'per_page' => 100,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Failed to fetch categories: '.$response->body());
            }

            $categories = $response->json();

            foreach ($categories as $category) {
                WpCategory::updateOrCreate(
                    ['wp_id' => $category['id']],
                    [
                        'name' => $category['name'],
                        'slug' => $category['slug'],
                    ]
                );
            }

            Log::info('WP Categories fetched successfully', [
                'count' => count($categories),
            ]);
        } catch (\Exception $e) {
            Log::error('FetchWpCategoriesJob failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
