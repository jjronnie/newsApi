<?php

namespace Database\Seeders;

use App\Models\ArticleTopic;
use Illuminate\Database\Seeder;

class ArticleTopicsSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            [
                'topic_title' => 'How to get started with Laravel in Uganda',
                'focus_keyword' => 'Laravel Uganda',
                'description' => 'A beginner-friendly guide to starting Laravel development in Uganda',
                'status' => 'pending',
            ],
            [
                'topic_title' => 'Setting up a development environment on Ubuntu',
                'focus_keyword' => 'Ubuntu development setup',
                'description' => 'Step-by-step guide for setting up Ubuntu for web development',
                'status' => 'pending',
            ],
            [
                'topic_title' => 'Cybersecurity tips for small businesses in Kampala',
                'focus_keyword' => 'cybersecurity Kampala',
                'description' => 'Essential cybersecurity practices for small businesses',
                'status' => 'pending',
            ],
            [
                'topic_title' => 'How to use AI tools for content creation',
                'focus_keyword' => 'AI content creation',
                'description' => 'Practical guide to using AI tools for creating content',
                'status' => 'pending',
            ],
            [
                'topic_title' => 'Flutter app development for beginners',
                'focus_keyword' => 'Flutter beginners',
                'description' => 'Getting started with Flutter mobile app development',
                'status' => 'pending',
            ],
            [
                'topic_title' => 'WordPress vs Laravel: Which to choose',
                'focus_keyword' => 'WordPress vs Laravel',
                'description' => 'Comparison guide for choosing the right platform',
                'status' => 'pending',
            ],
        ];

        foreach ($topics as $topic) {
            ArticleTopic::updateOrCreate(
                ['topic_title' => $topic['topic_title']],
                $topic
            );
        }
    }
}
