@php
    use Filament\Support\Enums\ActionSize;
@endphp

<x-filament-panels::page>
    <x-filament::section class="-mt-[1rem]">
        <div class="grid grid-cols-2">
            <div class="flex flex-row space-x-5">
                <div class="relative w-24 h-24">
                    <img src="{{ $user->getFilamentAvatarUrl() }}" alt="Profile" class="w-24 h-24 rounded-xl border border-gray-50 dark:border-gray-700 max-w-sm mx-auto relative z-0">
                    <a x-on:click="$dispatch('open-modal', {id: 'upload-avatar-modal'})" class="absolute w-full h-full cursor-pointer rounded-xl top-0 left-0 bg-black opacity-0 z-10 transition-opacity duration-300 hover:opacity-50 ">
                        <x-heroicon-c-camera class="w-8 h-8 text-white ml-auto pr-1 opacity-100" />
                    </a>
                </div>
                <div class="py-1 space-y-1.5 my-auto">
                    <div class="flex space-x-1.5">
                        <div class="font-semibold text-base">{{ $user->name }}</div>
                        <x-filament::badge color="{{ $user->is_active ? 'success' : 'danger' }}" class="!text-[0.70rem]/[1rem] !rounded-xl w-fit h-fit">{{ $user->is_active ? 'Active' : 'Inactive' }}</x-filament::badge>
                    </div>
                    <div class="text-sm text-gray-700">
                        {{ $user->email }}
                    </div>
                    <div class="flex flex-wrap gap-2 pt-0.5">
                        @foreach ($user->roles as $permission)
                            <x-filament::badge color="primary" class="w-fit h-fit !text-[0.72rem]/[1rem]">
                                {{ $permission->name }}
                            </x-filament::badge>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex flex-col items-end space-y-3">
                    {{ $this->editProfileAction }}
                    {{ $this->changePasswordAction }}
                </div>
            </div>
        </div>
    </x-filament::section>

    {{ $this->profileInfolist }}

    <x-filament-actions::modals />

    <x-filament::modal id="upload-avatar-modal" width="4xl">
        <x-filament-panels::form wire:submit="updateAvatar">
            {{ $this->form }}
            <div class="flex justify-start">
                <x-filament::button type="submit" wire:target="updateAvatar">
                    Simpan
                </x-filament::button>
            </div>
        </x-filament-panels::form>
    </x-filament::modal>
</x-filament-panels::page>
