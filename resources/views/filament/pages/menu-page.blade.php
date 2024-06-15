<x-filament-panels::page>
    <x-filament-panels::form wire:submit="submit">
        <x-filament::section heading="List Menu" description="Ini merupakan list menu yang ada diaplikasi">
            {{-- @if($isUpdated)
                <div>Hahahaha</div>
            @endif --}}
            {{ $this->menuListForm }}
        </x-filament::section>
        <div class="flex justify-end mb-5">
            <x-filament::button icon="heroicon-c-check" type="submit">
                Save
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
