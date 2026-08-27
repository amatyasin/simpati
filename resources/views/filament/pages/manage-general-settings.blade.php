<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <x-filament::button type="submit" icon="heroicon-o-check-circle" color="primary">
                Simpan Pengaturan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
