<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAffiliateClickRequest;
use App\Models\AffiliateClick;
use Illuminate\Http\JsonResponse;

class AffiliateClickController extends Controller
{
    public function store(StoreAffiliateClickRequest $request): JsonResponse
    {
        $data = $request->validated();

        AffiliateClick::create([
            'user_id' => $request->user()?->id,
            'service_name' => $data['service_name'],
            'service_id' => $data['service_id'] ?? '',
            'tmdb_id' => $data['tmdb_id'],
            'media_type' => $data['media_type'],
            'link' => $data['link'],
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['ok' => true]);
    }
}
