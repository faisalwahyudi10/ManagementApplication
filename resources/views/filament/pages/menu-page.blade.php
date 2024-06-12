<x-filament-panels::page>
    <x-filament-panels::form wire:submit="createMenu">
        <x-filament::section heading="List Menu" description="Ini merupakan list meni yang ada diaplikasi">
            {{ $this->menuListForm }}
        </x-filament::section>
        <div class="flex justify-end mb-5">
            <x-filament::button icon="heroicon-c-check" type="submit">
                Save
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
