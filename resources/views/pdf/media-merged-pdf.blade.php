<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BERKAS LEGALITAS MITRA MEDIA - {{ $media->brand_name }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 35px;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #0284c7;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .app-name {
            font-size: 26px;
            font-weight: bold;
            color: #0369a1;
            letter-spacing: 2px;
            margin: 0;
        }
        .sub-title {
            font-size: 15px;
            font-weight: 600;
            color: #475569;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .meta-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 6px 10px;
            font-size: 12px;
            vertical-align: top;
        }
        .meta-label {
            width: 35%;
            font-weight: bold;
            color: #334155;
        }
        .meta-value {
            width: 65%;
            color: #0f172a;
        }
        .doc-list {
            margin-top: 15px;
        }
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .doc-table th {
            background-color: #0284c7;
            color: #ffffff;
            padding: 8px 10px;
            text-align: left;
        }
        .doc-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-approved { background-color: #dcfce7; color: #166534; }
        .badge-pending  { background-color: #fef9c3; color: #854d0e; }
        .badge-revision { background-color: #e0f2fe; color: #075985; }
        .badge-rejected { background-color: #fee2e2; color: #991b1b; }
        .badge-expired  { background-color: #f1f5f9; color: #475569; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="app-name">SIMPATI</h1>
        <div class="sub-title">BERKAS LEGALITAS MITRA MEDIA</div>
    </div>

    <div class="meta-card">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Nama Media / Brand:</td>
                <td class="meta-value"><strong>{{ $media->brand_name }}</strong></td>
            </tr>
            <tr>
                <td class="meta-label">Nama Perusahaan (Badan Hukum):</td>
                <td class="meta-value">{{ $media->company_name }}</td>
            </tr>
            <tr>
                <td class="meta-label">Kategori Media:</td>
                <td class="meta-value">{{ $media->mediaCategory?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Tanggal Generate:</td>
                <td class="meta-value">{{ now()->format('d/m/Y H:i') }} WIB</td>
            </tr>
            <tr>
                <td class="meta-label">Jumlah Dokumen Lampiran:</td>
                <td class="meta-value">
                    <strong>{{ $availableCount }} dari {{ $totalRequiredCount }} dokumen wajib</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-list">
        <h3 style="font-size: 13px; margin-bottom: 8px;">Daftar Lampiran Dokumen</h3>
        <table class="doc-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 35%;">Jenis Dokumen</th>
                    <th style="width: 25%;">Nomor Dokumen</th>
                    <th style="width: 15%;">Masa Berlaku</th>
                    <th style="width: 20%;">Status Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($documents as $index => $doc)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $doc->documentType?->name ?? 'Dokumen' }}</strong></td>
                        <td>{{ $doc->document_number ?? '-' }}</td>
                        <td>{{ $doc->expiration_date ? $doc->expiration_date->format('d/m/Y') : 'Seumur Hidup' }}</td>
                        <td>
                            @php
                                $statusStr = is_object($doc->verification_status) ? $doc->verification_status->value : $doc->verification_status;
                            @endphp
                            <span class="badge badge-{{ $statusStr }}">
                                {{ strtoupper($statusStr) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem Informasi Mitra Pers dan Media Integratif (SIMPATI).
    </div>
</body>
</html>
