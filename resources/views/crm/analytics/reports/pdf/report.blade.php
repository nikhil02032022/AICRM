<!DOCTYPE html>
{{-- BRD: CRM-AR-019 — Generic PDF template for all 9 standard reports; rendered via spipu/html2pdf --}}
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #111; }

        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 8px; margin-bottom: 12px; }
        .header-title { font-size: 15pt; font-weight: bold; color: #1e1b4b; }
        .header-meta { font-size: 8pt; color: #555; margin-top: 3px; }

        .filters { background: #f5f5f5; border: 1px solid #ddd; padding: 6px 10px;
                   font-size: 8pt; color: #444; margin-bottom: 12px; border-radius: 3px; }

        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        thead tr { background-color: #4f46e5; color: #fff; }
        thead th { padding: 6px 8px; font-size: 8.5pt; text-align: left; font-weight: bold;
                   border: 1px solid #3730a3; white-space: nowrap; }
        tbody tr:nth-child(even) { background-color: #f8f8ff; }
        tbody tr:nth-child(odd)  { background-color: #ffffff; }
        tbody td { padding: 5px 8px; font-size: 8pt; border: 1px solid #e2e2e2;
                   vertical-align: top; word-break: break-word; }
        tfoot tr { background-color: #e8e8f0; font-weight: bold; }
        tfoot td { padding: 5px 8px; font-size: 8pt; border-top: 2px solid #4f46e5; border: 1px solid #ddd; }

        .footer { margin-top: 14px; font-size: 7.5pt; color: #888; text-align: right;
                  border-top: 1px solid #ddd; padding-top: 5px; }
        .no-data { text-align: center; padding: 20px; color: #888; font-style: italic; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-title">{{ $title }}</div>
        <div class="header-meta">
            Generated: {{ $generatedAt }}
            @if(!empty($filterSummary))
                &nbsp;&bull;&nbsp; {{ $filterSummary }}
            @endif
        </div>
    </div>

    @if(!empty($filterDetails))
    <div class="filters">
        @foreach($filterDetails as $label => $value)
            @if($value)
                <strong>{{ $label }}:</strong> {{ $value }}
                @if(!$loop->last) &nbsp;&bull;&nbsp; @endif
            @endif
        @endforeach
    </div>
    @endif

    @if($rows->isEmpty())
        <p class="no-data">No data found for the selected filters.</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                    <td>{{ $cell ?? '' }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            {{ $rows->count() }} {{ Str::plural('row', $rows->count()) }} exported
            &bull; {{ $title }}
        </div>
    @endif

</body>
</html>
