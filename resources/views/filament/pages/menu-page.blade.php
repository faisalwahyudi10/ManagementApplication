<x-filament-panels::page>
    <x-filament::section heading="List Menu" description="Ini merupakan list meni yang ada diaplikasi">
        <x-filament-panels::form wire:submit="updateProfile">
            {{ $this->menuListForm }}
            {{-- <div class="flex justify-end mb-5">
                <x-filament-panels::form.actions :actions="$this->getUpdateProfileFormActions()" />
            </div> --}}
        </x-filament-panels::form>
    </x-filament::section>
</x-filament-panels::page>
