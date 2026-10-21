<?php

use App\Models\Profile;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Who\'s watching? — StreamVault')]
class extends Component
{
    public bool $managing = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $avatarColor = 'red';

    public function mount(): void
    {
        auth()->user()->activeProfile();
    }

    public function select(int $profileId): void
    {
        auth()->user()->switchProfile($this->ownedProfile($profileId));

        $this->redirectIntended(route('home'), navigate: true);
    }

    public function startCreating(): void
    {
        abort_if(auth()->user()->profiles()->count() >= Profile::MAX_PER_ACCOUNT, 403);

        $this->editingId = 0;
        $this->name = '';
        $this->avatarColor = collect(array_keys(Profile::AVATAR_COLORS))->random();
        $this->resetValidation();
    }

    public function startEditing(int $profileId): void
    {
        $profile = $this->ownedProfile($profileId);

        $this->editingId = $profile->id;
        $this->name = $profile->name;
        $this->avatarColor = $profile->avatar_color;
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:40'],
            'avatarColor' => ['required', Rule::in(array_keys(Profile::AVATAR_COLORS))],
        ]);

        $attributes = ['name' => trim($validated['name']), 'avatar_color' => $validated['avatarColor']];

        if ($this->editingId === 0) {
            abort_if(auth()->user()->profiles()->count() >= Profile::MAX_PER_ACCOUNT, 403);
            auth()->user()->profiles()->create($attributes);
        } elseif ($this->editingId !== null) {
            $this->ownedProfile($this->editingId)->update($attributes);
        }

        $this->cancel();
    }

    public function delete(int $profileId): void
    {
        $user = auth()->user();
        $profile = $this->ownedProfile($profileId);

        abort_if($user->profiles()->count() <= 1, 403);

        $profile->delete();

        if (session('active_profile_id') === $profileId) {
            session()->forget('active_profile_id');
        }

        $this->cancel();
    }

    public function cancel(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->resetValidation();
    }

    private function ownedProfile(int $profileId): Profile
    {
        $profile = auth()->user()->profiles()->find($profileId);

        abort_if($profile === null, 404);

        return $profile;
    }

    public function with(): array
    {
        $profiles = auth()->user()->profiles()->get();

        return [
            'profiles' => $profiles,
            'canAdd' => $profiles->count() < Profile::MAX_PER_ACCOUNT,
            'activeId' => auth()->user()->activeProfile()->id,
        ];
    }
}; ?>

<div class="flex min-h-[80vh] flex-col items-center justify-center px-4 py-16">
    @if($editingId !== null)
        <form wire:submit="save" class="w-full max-w-md">
            <h1 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">{{ $editingId === 0 ? __('Add profile') : __('Edit profile') }}</h1>

            <div class="mt-8 flex items-center gap-5">
                <div class="flex size-20 shrink-0 items-center justify-center rounded-md text-3xl font-bold text-white {{ \App\Models\Profile::AVATAR_COLORS[$avatarColor] ?? 'bg-red-600' }}">
                    {{ mb_strtoupper(mb_substr($name, 0, 1)) ?: '?' }}
                </div>
                <div class="flex-1">
                    <label for="profile-name" class="text-sm text-zinc-400">{{ __('Name') }}</label>
                    <input id="profile-name" type="text" wire:model.live.debounce.250ms="name" maxlength="40" autofocus class="mt-1 w-full rounded-md border border-white/10 bg-zinc-800 px-3 py-2 text-white outline-none focus:border-white/40">
                    @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <fieldset class="mt-6">
                <legend class="text-sm text-zinc-400">{{ __('Color') }}</legend>
                <div class="mt-2 flex gap-3">
                    @foreach(\App\Models\Profile::AVATAR_COLORS as $key => $class)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="avatarColor" value="{{ $key }}" class="peer sr-only">
                            <span class="block size-9 rounded-md ring-offset-2 ring-offset-zinc-950 peer-checked:ring-2 peer-checked:ring-white peer-focus-visible:ring-2 peer-focus-visible:ring-white/60 {{ $class }}" aria-label="{{ ucfirst($key) }}"></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="mt-8 flex flex-wrap gap-3">
                <button type="submit" class="rounded-md bg-white px-6 py-2 text-sm font-bold text-black transition hover:bg-white/80">{{ __('Save') }}</button>
                <button type="button" wire:click="cancel" class="rounded-md border border-white/30 px-6 py-2 text-sm font-semibold text-zinc-300 transition hover:border-white hover:text-white">{{ __('Cancel') }}</button>
                @if($editingId !== 0 && $profiles->count() > 1)
                    <button type="button" wire:click="delete({{ $editingId }})" wire:confirm="{{ __('Delete this profile? Its history, My List and favorites will be removed.') }}" class="ml-auto rounded-md px-4 py-2 text-sm font-semibold text-red-400 transition hover:bg-red-500/10">{{ __('Delete profile') }}</button>
                @endif
            </div>
        </form>
    @else
        <h1 class="text-center text-3xl font-bold tracking-tight sm:text-5xl">{{ $managing ? __('Manage profiles') : __('Who\'s watching?') }}</h1>

        <ul class="mt-10 flex flex-wrap justify-center gap-5 sm:gap-8">
            @foreach($profiles as $profile)
                <li wire:key="profile-{{ $profile->id }}">
                    <button
                        type="button"
                        wire:click="{{ $managing ? "startEditing({$profile->id})" : "select({$profile->id})" }}"
                        class="group flex w-24 flex-col items-center gap-3 sm:w-32"
                    >
                        <span class="relative flex size-24 items-center justify-center rounded-md text-4xl font-bold text-white ring-white transition group-hover:ring-4 group-focus-visible:ring-4 sm:size-32 sm:text-5xl {{ $profile->avatarClass() }}">
                            {{ $profile->initial() }}
                            @if($managing)
                                <span class="absolute inset-0 flex items-center justify-center rounded-md bg-black/50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                </span>
                            @endif
                        </span>
                        <span class="truncate text-sm text-zinc-400 transition group-hover:text-white sm:text-base {{ ! $managing && $profile->id === $activeId ? 'text-white' : '' }}">{{ $profile->name }}</span>
                    </button>
                </li>
            @endforeach

            @if($canAdd)
                <li>
                    <button type="button" wire:click="startCreating" class="group flex w-24 flex-col items-center gap-3 sm:w-32">
                        <span class="flex size-24 items-center justify-center rounded-md border-2 border-zinc-600 text-zinc-500 transition group-hover:border-white group-hover:bg-white/5 group-hover:text-white sm:size-32">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </span>
                        <span class="text-sm text-zinc-400 transition group-hover:text-white sm:text-base">{{ __('Add profile') }}</span>
                    </button>
                </li>
            @endif
        </ul>

        <button type="button" wire:click="$toggle('managing')" class="mt-12 rounded-md border border-zinc-500 px-6 py-2 text-sm font-semibold tracking-widest text-zinc-400 transition hover:border-white hover:text-white">
            {{ $managing ? __('DONE') : __('MANAGE PROFILES') }}
        </button>
    @endif
</div>
