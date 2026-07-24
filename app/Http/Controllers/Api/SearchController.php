<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Tmdb;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, Tmdb $tmdb): JsonResponse
    {
        $query = trim($request->string('q'));
        $type = $request->string('type', 'multi');

        if (strlen($query) < 2) {
            return $this->trending($tmdb);
        }

        $endpoint = match ((string) $type) {
            'movie' => '/search/movie',
            'tv' => '/search/tv',
            'person' => '/search/person',
            default => '/search/multi',
        };

        $data = $tmdb->get($endpoint, [
            'query' => $query,
            'page' => 1,
        ]);

        $results = collect($data['results'] ?? [])
            ->take(8)
            ->map(fn (array $item): array => $this->formatResult($item, $tmdb, (string) $type))
            ->values()
            ->all();

        return response()->json([
            'results' => $results,
            'total' => $data['total_results'] ?? 0,
            'query' => $query,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatResult(array $item, Tmdb $tmdb, string $searchType): array
    {
        $mediaType = $item['media_type'] ?? $searchType;
        if ($mediaType === 'multi') {
            $mediaType = isset($item['title']) ? 'movie' : (isset($item['name']) && isset($item['first_air_date']) ? 'tv' : 'person');
        }

        $title = $item['title'] ?? $item['name'] ?? 'Unknown';
        $year = '';
        $image = '';

        if ($mediaType === 'person') {
            $image = ! empty($item['profile_path'])
                ? $tmdb->imageUrl($item['profile_path'], 'w92')
                : '';
            $department = $item['known_for_department'] ?? '';

            return [
                'id' => $item['id'],
                'title' => $title,
                'type' => 'person',
                'image' => $image,
                'subtitle' => $department,
                'rating' => null,
                'url' => route('people.detail', $item['id']),
            ];
        }

        $image = ! empty($item['poster_path'])
            ? $tmdb->imageUrl($item['poster_path'], 'w92')
            : '';
        $date = $item['release_date'] ?? $item['first_air_date'] ?? '';
        $year = $date ? substr($date, 0, 4) : '';
        $rating = ! empty($item['vote_average']) ? round($item['vote_average'], 1) : null;

        $routeName = $mediaType === 'tv' ? 'tv.detail' : 'movies.detail';

        return [
            'id' => $item['id'],
            'title' => $title,
            'type' => $mediaType,
            'image' => $image,
            'subtitle' => $year,
            'rating' => $rating,
            'url' => route($routeName, $item['id']),
        ];
    }

    private function trending(Tmdb $tmdb): JsonResponse
    {
        $data = $tmdb->trending('all', 'day');

        $results = collect($data['results'] ?? [])
            ->take(6)
            ->map(fn (array $item): array => $this->formatResult($item, $tmdb, 'multi'))
            ->values()
            ->all();

        return response()->json([
            'results' => $results,
            'total' => 0,
            'query' => '',
            'trending' => true,
        ]);
    }
}
