<?php

namespace App\Console\Commands;

use App\Models\WpCategory;
use App\Services\AiContentWriter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ai:generate-test {topic? : The topic to generate content for}')]
#[Description('Generate a test article using AI Content Writer')]
class AiGenerateTest extends Command
{
    public function handle()
    {
        $topic = $this->argument('topic') ?? 'How to install Laravel on Ubuntu for beginners';

        $this->info("Generating article for topic: {$topic}");

        $categories = WpCategory::all()->toArray();

        if (empty($categories)) {
            $this->warn('No WordPress categories found. Using default categories.');
            $categories = [
                ['name' => 'Tutorials', 'slug' => 'tutorials'],
                ['name' => 'Laravel', 'slug' => 'laravel'],
                ['name' => 'Linux', 'slug' => 'linux'],
            ];
        }

        $writer = new AiContentWriter($topic, $categories);

        $this->info('Calling AI service...');

        try {
            $article = $writer->generateAndStore();

            $this->info('Article generated successfully!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $article->id],
                    ['Title', $article->title],
                    ['Slug', $article->slug],
                    ['Focus Keyword', $article->focus_keyword],
                    ['Status', $article->status],
                    ['Meta Title', substr($article->meta_title, 0, 50).'...'],
                    ['Meta Description', substr($article->meta_description, 0, 50).'...'],
                ]
            );

            $this->info("Article saved to database with ID: {$article->id}");
        } catch (\Exception $e) {
            $this->error('Failed to generate article: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
