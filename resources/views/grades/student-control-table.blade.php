@php
    $className = $classGroup?->name ?? '-';
    $subjectName = $classGroup?->subject?->name;
    $semesterName = $classGroup?->semester?->name;
    $classDisplay = collect([$className, $subjectName, $semesterName])->filter()->implode(' / ');
    $displayRows = collect($rows);
    $emptyRows = max(0, 22 - $displayRows->count());
@endphp

<div class="student-control-sheet">
    <div class="sheet-header">
        <div class="logo-cell">
            @if (! empty($logoSrc))
                <img src="{{ $logoSrc }}" alt="Logo Asy-Syams">
            @else
                <div class="logo-fallback">ASY<br>SYAMS</div>
            @endif
        </div>
        <div class="brand-cell">
            <div class="brand-title">{{ $institution['name'] }}</div>
            <div class="brand-subtitle">{{ $institution['subtitle'] }}</div>
            @if (! empty($institution['address']) || ! empty($institution['phone']) || ! empty($institution['email']))
                <div class="brand-contact">
                    {{ collect([$institution['address'], $institution['phone'], $institution['email']])->filter()->implode(' | ') }}
                </div>
            @endif
        </div>
    </div>

    <div class="top-rule"></div>

    <div class="identity-row">
        <div class="identity-field">
            <span class="identity-label">Nama</span>
            <span class="identity-colon">:</span>
            <span class="identity-value">{{ $student->name }}</span>
        </div>
        <div class="identity-field identity-class">
            <span class="identity-label">Kelas</span>
            <span class="identity-colon">:</span>
            <span class="identity-value">{{ $classDisplay }}</span>
        </div>
    </div>

    <table class="control-table">
        <colgroup>
            <col class="col-no">
            <col class="col-date">
            <col class="col-reading">
            <col class="col-mark">
            <col class="col-mark">
            <col class="col-mark">
            <col class="col-reading">
            <col class="col-mark">
            <col class="col-mark">
            <col class="col-reading">
            <col class="col-sign">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">NO</th>
                <th rowspan="2">TANGGAL</th>
                <th rowspan="2">ZIYADAH</th>
                <th colspan="3">KET</th>
                <th rowspan="2">MUROJAAH</th>
                <th colspan="2">KET</th>
                <th rowspan="2">TAHSIN</th>
                <th rowspan="2">TTD</th>
            </tr>
            <tr>
                <th>L</th>
                <th>C</th>
                <th>TL</th>
                <th>B</th>
                <th>K</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($displayRows as $row)
                <tr>
                    <td class="text-center">{{ $row['number'] }}</td>
                    <td class="text-center">{{ $row['date']?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $row['ziyadah'] ?: '' }}</td>
                    <td class="text-center mark-cell">{{ $row['ziyadah_score'] === 'L' ? 'V' : '' }}</td>
                    <td class="text-center mark-cell">{{ $row['ziyadah_score'] === 'C' ? 'V' : '' }}</td>
                    <td class="text-center mark-cell">{{ $row['ziyadah_score'] === 'TL' ? 'V' : '' }}</td>
                    <td>{{ $row['murojaah'] ?: '' }}</td>
                    <td class="text-center mark-cell">{{ $row['murojaah_score'] === 'B' ? 'V' : '' }}</td>
                    <td class="text-center mark-cell">{{ $row['murojaah_score'] === 'K' ? 'V' : '' }}</td>
                    <td>{{ $row['tahsin'] ?: '' }}</td>
                    <td></td>
                </tr>
            @endforeach

            @if ($displayRows->isEmpty())
                <tr>
                    <td colspan="11" class="empty-state">Belum ada data assessment untuk santri ini.</td>
                </tr>
                @php $emptyRows = 21; @endphp
            @endif

            @for ($i = 0; $i < $emptyRows; $i++)
                <tr class="blank-row">
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="notes">
        <div>Keterangan:</div>
        <ol>
            <li>Beri tanda centang (V) pada kolom keterangan.</li>
            <li>Kolom Ziyadah: Lancar (L), Cukup (C), Tidak Lancar (TL).</li>
            <li>Kolom Murojaah: Baik (B), Kurang (K).</li>
        </ol>
    </div>
</div>
