<?php

namespace App\Console\Commands;

use App\Services\SourceResolver;
use App\Services\Tmdb;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:pre-warm-sources')]
#[Description('Pre-cache streaming sources for trending and popular content')]
class PreWarmSources extends Command
{
    public function handle(Tmdb $tmdb, SourceResolver $resolver): int
    {
        $items = [];

        try {
            $trending = $tmdb->trending('all', 'day');
            foreach (array_slice($trending['results'] ?? [], 0, 20) as $item) {
                $type = $item['media_type'] ?? 'movie';
                if (in_array($type, ['movie', 'tv'], true)) {
                    $items[] = ['id' => $item['id'], 'type' => $type];
                }
            }
        } catch (\Throwable) {
            $this->warn('Could not fetch trending items.');
        }

        try {
            $popular = $tmdb->popular('movie');
            foreach (array_slice($popular['results'] ?? [], 0, 10) as $item) {
                $items[] = ['id' => $item['id'], 'type' => 'movie'];
            }
        } catch (\Throwable) {
        }

        try {
            $popularTv = $tmdb->popular('tv');
            foreach (array_slice($popularTv['results'] ?? [], 0, 10) as $item) {
                $items[] = ['id' => $item['id'], 'type' => 'tv'];
            }
        } catch (\Throwable) {
        }

        $warmed = $resolver->preWarm($items);
        $this->info("Pre-warmed sources for {$warmed} titles.");

        return self::SUCCESS;
    }
}
