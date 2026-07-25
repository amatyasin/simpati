<x-filament-panels::page>

    {{-- Stats Summary Cards: Media Partner --}}
    <div>
        <h3 style="font-size: 14px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
            <span>📊</span> Ringkasan Status Mitra Media
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px;">
            @php $ps = $this->getPartnerStats(); @endphp
            
            <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Total Mitra</p>
                <p style="font-size: 32px; font-weight: 900; color: #111827; margin: 6px 0 0 0; line-height: 1;">{{ $ps['total'] }}</p>
            </div>
            
            <div style="background-color: #ecfdf5; border: 1px solid #d1fae5; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size: 11px; font-weight: 700; color: #047857; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Terverifikasi</p>
                <p style="font-size: 32px; font-weight: 900; color: #065f46; margin: 6px 0 0 0; line-height: 1;">{{ $ps['approved'] }}</p>
            </div>
            
            <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size: 11px; font-weight: 700; color: #b45309; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Menunggu Verifikasi</p>
                <p style="font-size: 32px; font-weight: 900; color: #92400e; margin: 6px 0 0 0; line-height: 1;">{{ $ps['pending'] }}</p>
            </div>
            
            <div style="background-color: #eff6ff; border: 1px solid #dbeafe; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size: 11px; font-weight: 700; color: #1d4ed8; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Butuh Revisi</p>
                <p style="font-size: 32px; font-weight: 900; color: #1e40af; margin: 6px 0 0 0; line-height: 1;">{{ $ps['revision'] }}</p>
            </div>
        </div>
    </div>

    {{-- Stats Summary Cards: Documents --}}
    <div>
        <h3 style="font-size: 14px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
            <span>📂</span> Ringkasan Status Dokumen
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px;">
            @php $ds = $this->getDocumentStats(); @endphp
            
            <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Total Dokumen</p>
                <p style="font-size: 32px; font-weight: 900; color: #111827; margin: 6px 0 0 0; line-height: 1;">{{ $ds['total'] }}</p>
            </div>
            
            <div style="background-color: #ecfdf5; border: 1px solid #d1fae5; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size: 11px; font-weight: 700; color: #047857; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Disetujui</p>
                <p style="font-size: 32px; font-weight: 900; color: #065f46; margin: 6px 0 0 0; line-height: 1;">{{ $ds['approved'] }}</p>
            </div>
            
            <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size: 11px; font-weight: 700; color: #b45309; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Menunggu</p>
                <p style="font-size: 32px; font-weight: 900; color: #92400e; margin: 6px 0 0 0; line-height: 1;">{{ $ds['pending'] }}</p>
            </div>
            
            <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size: 11px; font-weight: 700; color: #b91c1c; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Kedaluwarsa</p>
                <p style="font-size: 32px; font-weight: 900; color: #991b1b; margin: 6px 0 0 0; line-height: 1;">{{ $ds['expired'] }}</p>
            </div>
            
            <div style="background-color: #fff7ed; border: 1px solid #ffedd5; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size: 11px; font-weight: 700; color: #c2410c; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Segera Berakhir</p>
                <p style="font-size: 32px; font-weight: 900; color: #9a3412; margin: 6px 0 0 0; line-height: 1;">{{ $ds['expiring_soon'] }}</p>
            </div>
        </div>
    </div>

    {{-- Export: Mitra Media --}}
    <x-filament::section>
        <x-slot name="heading">Ekspor Data Mitra Media</x-slot>
        <x-slot name="description">Filter dan unduh laporan daftar mitra media dalam format Excel.</x-slot>

        {{ $this->partnerFilterForm }}

        <div style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 12px;">
            <x-filament::button
                wire:click="exportPartnersExcel"
                icon="heroicon-o-table-cells"
                color="success"
                size="md"
            >
                Ekspor Excel (.xlsx)
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- Export: Dokumen --}}
    <x-filament::section style="margin-top: 12px;">
        <x-slot name="heading">Ekspor Data Dokumen Administratif</x-slot>
        <x-slot name="description">Filter dan unduh laporan dokumen beserta status verifikasi dan kedaluwarsa.</x-slot>

        {{ $this->docFilterForm }}

        <div style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 12px;">
            <x-filament::button
                wire:click="exportDocumentsExcel"
                icon="heroicon-o-table-cells"
                color="success"
                size="md"
            >
                Ekspor Excel (.xlsx)
            </x-filament::button>
        </div>
    </x-filament::section>

</x-filament-panels::page>
