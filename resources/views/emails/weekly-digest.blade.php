<x-mail::message>
# Hey {{ $user->name }}!

Here's your weekly recap from **{{ config('app.name') }}**.

---

## Your Week
@if($digestData['watched_this_week'] > 0 || $digestData['reviews_this_week'] > 0)
- Watched **{{ $digestData['watched_this_week'] }}** {{ Str::plural('title', $digestData['watched_this_week']) }}
- Wrote **{{ $digestData['reviews_this_week'] }}** {{ Str::plural('review', $digestData['reviews_this_week']) }}
@if($digestData['streak'] > 0)
- Current streak: **{{ $digestData['streak'] }}** {{ Str::plural('day', $digestData['streak']) }}
@endif
@else
You haven't watched anything this week — time to catch up!
@endif

---

## Trending This Week

@foreach($digestData['trending'] as $item)
- **{{ $item['title'] }}** ({{ $item['type'] }}) — {{ $item['rating'] }}/10
@endforeach

@if(!empty($digestData['friend_activity']))
---

## Friends Activity

@foreach($digestData['friend_activity'] as $friend)
- **{{ $friend['name'] }}** watched {{ $friend['watches'] }} {{ Str::plural('title', $friend['watches']) }}
@endforeach
@endif

<x-mail::button :url="route('dashboard')">
Open Dashboard
</x-mail::button>

Happy watching,<br>
{{ config('app.name') }}
</x-mail::message>
