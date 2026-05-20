<style>
    .student-control-sheet {
        background: #ffffff;
        border: 1px solid #111111;
        margin: 0 auto;
        max-width: 980px;
        padding: 18px 18px 14px;
    }

    .sheet-header {
        align-items: center;
        display: flex;
        gap: 18px;
        justify-content: center;
        min-height: 82px;
        text-align: left;
    }

    .logo-cell {
        flex: 0 0 82px;
        text-align: center;
    }

    .logo-cell img {
        display: inline-block;
        height: 74px;
        object-fit: contain;
        width: 74px;
    }

    .logo-fallback {
        align-items: center;
        border: 2px solid #111111;
        border-radius: 50%;
        display: inline-flex;
        font-family: Arial, sans-serif;
        font-size: 11px;
        font-weight: 800;
        height: 70px;
        justify-content: center;
        line-height: 1.1;
        text-align: center;
        width: 70px;
    }

    .brand-cell {
        flex: 0 1 auto;
    }

    .brand-title {
        font-family: Arial, sans-serif;
        font-size: 21px;
        font-weight: 800;
        line-height: 1.05;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .brand-subtitle {
        font-family: Arial, sans-serif;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0;
        margin-top: 3px;
        text-transform: uppercase;
    }

    .brand-contact {
        font-family: Arial, sans-serif;
        font-size: 11px;
        line-height: 1.35;
        margin-top: 5px;
    }

    .top-rule {
        border-top: 2px solid #111111;
        margin: 8px 0 12px;
    }

    .identity-row {
        display: flex;
        gap: 36px;
        margin-bottom: 10px;
    }

    .identity-field {
        align-items: baseline;
        display: grid;
        flex: 1;
        grid-template-columns: 52px 12px 1fr;
    }

    .identity-label,
    .identity-colon,
    .identity-value {
        font-size: 13px;
    }

    .identity-value {
        border-bottom: 1px solid #111111;
        min-height: 18px;
        padding: 0 4px 2px;
    }

    .control-table {
        border-collapse: collapse;
        table-layout: fixed;
        width: 100%;
    }

    .control-table .col-no { width: 40px; }
    .control-table .col-date { width: 76px; }
    .control-table .col-reading { width: 22%; }
    .control-table .col-mark { width: 30px; }
    .control-table .col-sign { width: 62px; }

    .control-table th,
    .control-table td {
        border: 1px solid #111111;
        font-size: 12px;
        line-height: 1.2;
        padding: 4px 5px;
        vertical-align: top;
        word-break: break-word;
    }

    .control-table th {
        background: #f3f4f6;
        font-family: Arial, sans-serif;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
        vertical-align: middle;
    }

    .control-table tbody tr {
        height: 28px;
    }

    .blank-row td {
        height: 24px;
    }

    .text-center {
        text-align: center;
    }

    .mark-cell {
        font-family: Arial, sans-serif;
        font-weight: 700;
    }

    .empty-state {
        color: #4b5563;
        padding: 14px;
        text-align: center;
    }

    .notes {
        font-size: 12px;
        margin-top: 12px;
    }

    .notes ol {
        margin: 4px 0 0 18px;
        padding: 0;
    }
</style>
