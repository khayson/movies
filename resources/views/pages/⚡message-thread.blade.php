<?php

use App\Models\Conversation;
use App\Models\Message;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public int $conversationId;

    public string $body = '';

    public function mount(int $conversationId): void
    {
        $this->conversationId = $conversationId;

        $conversation = Conversation::findOrFail($conversationId);
        abort_unless($conversation->involvedUser(auth()->id()), 403);
    }

    public function sendMessage(): void
    {
        $this->validate(['body' => 'required|string|max:2000']);

        $conversation = Conversation::findOrFail($this->conversationId);
        abort_unless($conversation->involvedUser(auth()->id()), 403);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => $this->body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $this->body = '';
    }

    public function with(): array
    {
        $conversation = Conversation::with(['userOne', 'userTwo'])->findOrFail($this->conversationId);
        $other = $conversation->otherUser(auth()->id());

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender')
            ->oldest()
            ->get();

        return [
            'conversation' => $conversation,
            'other' => $other,
            'messages' => $messages,
        ];
    }
};
?>

<div wire:poll.5s>
    <div class="mx-auto max-w-3xl">
        {{-- Header --}}
        <div class="mb-6 flex items-center gap-4">
            <a href="{{ route('messages') }}" class="flex size-9 items-center justify-center rounded-lg border border-white/[0.08] text-zinc-400 transition hover:border-white/[0.15] hover:text-white" wire:navigate>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </a>
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-blue-600/20 text-sm font-bold text-blue-400">
                    {{ Str::upper(Str::substr($other->name, 0, 1)) }}
                </div>
                <div>
                    <a href="{{ route('user.profile', $other->id) }}" class="font-semibold text-zinc-200 hover:text-amber-400 transition" wire:navigate>{{ $other->name }}</a>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="mb-4 space-y-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4" style="min-height: 300px; max-height: 60vh; overflow-y: auto;" x-data x-init="$el.scrollTop = $el.scrollHeight" x-effect="$el.scrollTop = $el.scrollHeight">
            @forelse($messages as $message)
                @php $isMine = $message->sender_id === auth()->id(); @endphp
                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] rounded-2xl px-4 py-2.5 {{ $isMine ? 'bg-blue-600 text-white' : 'bg-white/[0.06] text-zinc-200' }}">
                        <p class="text-sm whitespace-pre-wrap">{{ $message->body }}</p>
                        <p class="mt-1 text-[10px] {{ $isMine ? 'text-blue-200/60' : 'text-zinc-600' }}">
                            {{ $message->created_at->format('g:i A') }}
                            @if($isMine && $message->read_at)
                                &middot; Read
                            @endif
                        </p>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <p class="text-zinc-500">No messages yet. Say hello!</p>
                </div>
            @endforelse
        </div>

        {{-- Input --}}
        <form wire:submit="sendMessage" class="flex gap-3">
            <input type="text"
                   wire:model="body"
                   placeholder="Type a message..."
                   class="flex-1 rounded-xl border border-white/[0.08] bg-white/[0.04] px-4 py-3 text-sm text-zinc-200 placeholder-zinc-600 outline-none transition focus:border-blue-500/30 focus:bg-white/[0.06]"
                   autocomplete="off">
            <button type="submit"
                    class="flex size-11 items-center justify-center rounded-xl bg-blue-600 text-white transition hover:bg-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                </svg>
            </button>
        </form>
    </div>

    <div class="pb-16"></div>
</div>
