<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório OS - {{ $report->os_number }}</title>
    <style>
        @page {
            margin: 20px 25px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.4;
        }
        .table-full {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
        }
        .meta-table th, .meta-table td {
            border: 1px solid #dddddd;
            padding: 5px 8px;
            text-align: left;
        }
        .meta-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            width: 25%;
        }
        .photo-card {
            page-break-inside: avoid;
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
            padding: 8px;
            background-color: #fafafa;
        }
        .photo-img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .photo-obs {
            margin-top: 6px;
            font-size: 10px;
            color: #4b5563;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            font-size: 9px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <table class="table-full header-table">
        <tr>
            <td style="width: 30%;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="max-height: 45px;">
                @else
                    <h2 style="margin: 0; color: #1e3a8a;">{{ $company->name ?? 'FotoOS' }}</h2>
                @endif
            </td>
            <td style="width: 70%; text-align: right;">
                <h3 style="margin: 0;">RELATÓRIO FOTOGRÁFICO DE SERVIÇO</h3>
                <span style="font-size: 10px; color: #6b7280;">OS Nº: <strong>{{ $report->os_number }}</strong> | Data Oficial: {{ $report->server_created_at->format('d/m/Y H:i') }}</span>
            </td>
        </tr>
    </table>

    <table class="table-full meta-table">
        <tr>
            <th>Unidade:</th>
            <td>{{ $report->unit->name ?? 'N/A' }}</td>
            <th>Setores:</th>
            <td>{{ $report->sectors->pluck('name')->join(', ') ?: 'Nenhum' }}</td>
        </tr>
        <tr>
            <th>Técnicos:</th>
            <td>{{ $report->technicians ?: 'Não informado' }}</td>
            <th>Status:</th>
            <td>Finalizado</td>
        </tr>
        @if($report->history)
        <tr>
            <th>Histórico / Escopo:</th>
            <td colspan="3">{{ $report->history }}</td>
        </tr>
        @endif
    </table>

    <div style="margin-top: 15px;">
        @forelse($report->photos as $index => $photo)
            @php
                $photoPath = storage_path('app/public/' . $photo->processed_path);
            @endphp
            <div class="photo-card">
                <div style="font-weight: bold; margin-bottom: 4px; color: #374151;">
                    Evidência Fotográfica #{{ $index + 1 }}
                </div>
                @if(file_exists($photoPath))
                    <img src="{{ $photoPath }}" class="photo-img">
                @else
                    <div style="padding: 20px; text-align: center; color: #ef4444;">Imagem não localizada no armazenamento físico.</div>
                @endif
                @if($photo->observation)
                    <div class="photo-obs">
                        <strong>Obs:</strong> {{ $photo->observation }}
                    </div>
                @endif
            </div>
        @empty
            <p style="text-align: center; color: #9ca3af;">Nenhuma evidência fotográfica anexada a este relatório.</p>
        @endforelse
    </div>

    <div class="footer">
        Documento autenticado digitalmente em {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
