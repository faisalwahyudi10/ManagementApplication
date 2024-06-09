<x-filament::section>
    <div class="space-y-4">
        <div class="text-base font-semibold">
            {{ $record->name.' Role' }}
        </div>
        <div class="space-y-3">
            <div class="text-sm font-medium">Permissions : </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($record->permissions as $permission)
                    <x-filament::badge color="primary" class="w-fit">
                        {{ $permission->name }}
                    </x-filament::badge>
                @endforeach
            </div>
        </div>
    </div>
</x-filament::section>