<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tabel Nilai {{ $student->name }}</title>
    <style>
        @page {
            margin: 12mm 9mm;
        }

        body {
            color: #111111;
            font-family: "Times New Roman", DejaVu Serif, serif;
            font-size: 11px;
            margin: 0;
        }

        .student-control-sheet {
            width: 100%;
        }

        .sheet-header {
            display: table;
            margin: 0 auto;
            min-height: 74px;
            text-align: left;
        }

        .logo-cell {
            display: table-cell;
            padding-right: 12px;
            text-align: center;
            vertical-align: middle;
            width: 78px;
        }

        .logo-cell img {
            height: 68px;
            object-fit: contain;
            width: 68px;
        }

        .logo-fallback {
            border: 1.5px solid #111111;
            border-radius: 34px;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            font-weight: bold;
            height: 48px;
            line-height: 1.15;
            padding-top: 20px;
            text-align: center;
            width: 68px;
        }

        .brand-cell {
            display: table-cell;
            vertical-align: middle;
        }

        .brand-title {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 18px;
            font-weight: bold;
            line-height: 1.05;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .brand-subtitle {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .brand-contact {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8.8px;
            line-height: 1.3;
            margin-top: 4px;
        }

        .top-rule {
            border-top: 2px solid #111111;
            margin: 7px 0 9px;
        }

        .identity-row {
            display: table;
            margin-bottom: 8px;
            width: 100%;
        }

        .identity-field {
            display: table-cell;
            width: 50%;
        }

        .identity-label,
        .identity-colon,
        .identity-value {
            display: inline-block;
            font-size: 10.5px;
            vertical-align: bottom;
        }

        .identity-label {
            width: 42px;
        }

        .identity-colon {
            width: 8px;
        }

        .identity-value {
            border-bottom: 1px solid #111111;
            min-height: 14px;
            padding: 0 3px 1px;
            width: 72%;
        }

        .control-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .control-table .col-no { width: 30px; }
        .control-table .col-date { width: 58px; }
        .control-table .col-reading { width: 22%; }
        .control-table .col-mark { width: 23px; }
        .control-table .col-sign { width: 44px; }

        .control-table th,
        .control-table td {
            border: 0.7px solid #111111;
            font-size: 9.2px;
            line-height: 1.15;
            padding: 3px 3px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .control-table th {
            background-color: #eeeeee;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8.4px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .control-table tbody tr {
            height: 20px;
        }

        .blank-row td {
            height: 18px;
        }

        .text-center {
            text-align: center;
        }

        .mark-cell {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-weight: bold;
        }

        .empty-state {
            color: #444444;
            padding: 12px;
            text-align: center;
        }

        .notes {
            font-size: 9.2px;
            margin-top: 9px;
        }

        .notes ol {
            margin: 3px 0 0 17px;
            padding: 0;
        }

        .summary-section {
            margin-top: 8px;
        }

        .summary-title {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .summary-table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .summary-table th,
        .summary-table td {
            border: 0.7px solid #111111;
            font-size: 8.8px;
            line-height: 1.15;
            padding: 3px 3px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .summary-table th {
            background-color: #eeeeee;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
        }

        .evaluation-table th:nth-child(1) { width: 64px; }
        .evaluation-table th:nth-child(3) { width: 48px; }
        .evaluation-table th:nth-child(4) { width: 86px; }

        .note-table th:nth-child(1) { width: 64px; }
    </style>
</head>
<body>
    @include('grades.student-control-table', [
        'logoSrc' => extension_loaded('gd') ? public_path('images/logo.PNG') : null,
    ])
</body>
</html>
