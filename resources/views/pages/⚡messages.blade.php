<?php

use App\Models\Conversation;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Messages — StreamVault')]
class extends Component
{
    public string $searchQuery = '';

    public int $newMessageUserId = 0;

    /** @var array<int, array{id: int, name: string}> */
    public array $searchResults = [];

    public function searchUsers(): void
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = User::where('id', '!=', auth()->id())
            ->where('name', 'like', "%{$this->searchQuery}%")
            ->limit(5)
            ->get(['id', 'name'])
            ->toArray();
    }

    public function startConversation(int $userId): void
    {
        $conversation = Conversation::findOrStartBetween(auth()->id(), $userId);
        $this->redirect(route('messages.thread', $conversation->id), navigate: true);
    }

    public function with(): array
    {
        $userId = auth()->id();

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $unreadCounts = [];
        foreach ($conversations as $conversation) {
            $unreadCounts[$conversation->id] = $conversation->messages()
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')
                ->count();
        }

        return [
            'conversations' => $conversations,
            'unreadCounts' => $unreadCounts,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-3xl">
        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <div class="mb-2 flex items-center gap-3">
                    <span class="h-6 w-1 rounded-full bg-blue-500"></span>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-400/80">Social</p>
                </div>
                <h1 class="text-3xl font-bold tracking-tight">Messages</h1>
            </div>
        </div>

        {{-- New conversation search --}}
        <div class="mb-6 rounded-xl border border-white/[0.06] bg-white/[0.02] p-4" x-data="{ open: false }">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input type="text"
                       wire:model.live.debounce.300ms="searchQuery"
                       wire:keyup="searchUsers"
                       @focus="open = true"
                       placeholder="Start a new conversation..."
                       class="flex-1 bg-transparent text-sm text-zinc-200 placeholder-zinc-600 outline-none">
            </div>
            @if(count($searchResults) > 0)
                <div class="mt-3 space-y-1 border-t border-white/[0.06] pt-3">
                    @foreach($searchResults as $result)
                        <button wire:click="startConversation({{ $result['id'] }})"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-white/[0.04]">
                            <div class="flex size-8 items-center justify-center rounded-full bg-blue-600/20 text-xs font-bold text-blue-400">
                                {{ Str::upper(Str::substr($result['name'], 0, 1)) }}
                            </div>
                            <span class="text-sm text-zinc-200">{{ $result['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Conversations list --}}
        @if($conversations->isEmpty())
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] py-20 text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-white/[0.04]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                </div>
                <p class="text-lg font-medium text-zinc-400">No conversations yet</p>
                <p class="mt-1 text-sm text-zinc-600">Search for a user above to start a conversation.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($conversations as $conversation)
                    @php $other = $conversation->otherUser(auth()->id()); @endphp
                    <a href="{{ route('messages.thread', $conversation->id) }}"
                       class="flex items-center gap-4 rounded-2xl border p-4 transition hover:border-white/[0.12] hover:bg-white/[0.04]
                           {{ ($unreadCounts[$conversation->id] ?? 0) > 0 ? 'border-blue-500/20 bg-blue-500/[0.03]' : 'border-white/[0.06] bg-white/[0.02]' }}"
                       wire:navigate>
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-blue-600/20 text-sm font-bold text-blue-400">
                            {{ Str::upper(Str::substr($other->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-zinc-200">{{ $other->name }}</p>
                                @if($conversation->latestMessage)
                                    <p class="shrink-0 text-xs text-zinc-600">{{ $conversation->latestMessage->created_at->diffForHumans(short: true) }}</p>
                                @endif
                            </div>
                            @if($conversation->latestMessage)
                                <p class="mt-0.5 truncate text-sm {{ ($unreadCounts[$conversation->id] ?? 0) > 0 ? 'font-medium text-zinc-300' : 'text-zinc-500' }}">
                                    @if($conversation->latestMessage->sender_id === auth()->id())
                                        <span class="text-zinc-600">You: </span>
                                    @endif
                                    {{ Str::limit($conversation->latestMessage->body, 60) }}
                                </p>
                            @else
                                <p class="mt-0.5 text-sm text-zinc-600">No messages yet</p>
                            @endif
                        </div>
                        @if(($unreadCounts[$conversation->id] ?? 0) > 0)
                            <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">
                                {{ $unreadCounts[$conversation->id] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
