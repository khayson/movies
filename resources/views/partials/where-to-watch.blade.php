@if(count($streamingOptions) > 0)
    <section class="mt-12">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold">Where to Watch</h2>
                <p class="mt-1 text-sm text-zinc-500">Support creators — stream legally on these services.</p>
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($streamingOptions as $index => $option)
                <a href="{{ $option['link'] }}" target="_blank" rel="noopener sponsored"
                   onclick="fetch('/api/affiliate-click', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''},body:JSON.stringify({service_name:'{{ addslashes($option['service']) }}',service_id:'{{ addslashes($option['service_id']) }}',tmdb_id:{{ $tmdbId ?? 0 }},media_type:'{{ $mediaType ?? 'movie' }}',link:'{{ addslashes($option['link']) }}'})})"
                   class="flex items-center gap-3 rounded-xl border p-4 transition {{ $index === 0 ? 'border-amber-500/40 bg-amber-500/10 hover:border-amber-400/60 hover:bg-amber-500/15' : 'border-zinc-800 bg-zinc-900/50 hover:border-zinc-600 hover:bg-zinc-800/50' }}">
                    @if(!empty($option['dark_logo']))
                        <img src="{{ $option['dark_logo'] }}" alt="{{ $option['service'] }}" class="size-10 shrink-0 rounded-lg object-contain" loading="lazy">
                    @else
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-800 text-xs font-bold text-zinc-400">
                            {{ Str::upper(Str::substr($option['service'], 0, 2)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-zinc-200 truncate">
                            {{ $option['service'] }}
                            @if($index === 0)
                                <span class="ml-1 rounded bg-amber-500/20 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-300">Recommended</span>
                            @endif
                        </p>
                        <div class="flex items-center gap-2 text-xs text-zinc-500">
                            @php
                                $typeLabel = match($option['type']) {
                                    'subscription' => 'Subscription',
                                    'free' => 'Free',
                                    'addon' => 'Add-on',
                                    'rent' => 'Rent',
                                    'buy' => 'Buy',
                                    default => $option['type'],
                                };
                                $typeColor = match($option['type']) {
                                    'subscription' => 'text-amber-400',
                                    'free' => 'text-green-400',
                                    'addon' => 'text-blue-400',
                                    'rent' => 'text-purple-400',
                                    'buy' => 'text-cyan-400',
                                    default => 'text-zinc-400',
                                };
                            @endphp
                            <span class="{{ $typeColor }} font-medium">{{ $typeLabel }}</span>
                            @if(!empty($option['quality']))
                                <span>{{ Str::upper($option['quality']) }}</span>
                            @endif
                            @if(!empty($option['price']) && isset($option['price']['formatted']))
                                <span>{{ $option['price']['formatted'] }}</span>
                            @endif
                        </div>
                        @if($index === 0)
                            <p class="mt-1 text-xs font-medium text-amber-400/90">Watch now →</p>
                        @endif
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 {{ $index === 0 ? 'text-amber-400' : 'text-zinc-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
            @endforeach
        </div>
    </section>
@endif
