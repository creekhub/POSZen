<style>
    @page {
        size: 58mm auto;
        margin: 0;
    }

    .soa-print-header {
        display: none;
    }

    @media print {
        html,
        body {
            width: 58mm;
            min-width: 58mm;
            height: auto;
            min-height: 0;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        body > div,
        body > div > div,
        .min-h-screen,
        .min-h-screen > .mx-auto {
            width: 58mm;
            min-width: 58mm;
            height: auto !important;
            min-height: 0 !important;
            margin: 0;
            padding: 0;
        }

        body * {
            visibility: hidden;
        }

        #state-of-account,
        #state-of-account * {
            visibility: visible;
            color: #000 !important;
            border-color: #000 !important;
        }

        nav {
            display: none !important;
        }

        #state-of-account {
            display: block;
            position: absolute;
            top: 0;
            left: 5mm;
            width: 48mm;
            min-width: 48mm;
            height: auto;
            min-height: 0;
            max-height: none;
            margin: 0 !important;
            padding: 1mm;
            box-sizing: border-box;
            overflow: visible;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            font-size: 9pt;
        }

        #soa-controls,
        #account-customer,
        #soa-summary,
        #state-of-account .soa-action-column {
            display: none !important;
        }

        #state-of-account .soa-table-scroll,
        #state-of-account > .mt-6.overflow-x-auto {
            display: block !important;
            width: 100%;
            margin: 0;
            overflow: visible;
        }

        #state-of-account > div:first-child {
            display: block;
            margin: 0 0 2mm;
            text-align: center;
        }

        #state-of-account > div:first-child > div:first-child p {
            margin: 0;
            font-size: 7pt;
            letter-spacing: 0;
        }

        #state-of-account h2 {
            margin: 1mm 0 0;
            font-size: 11pt;
        }

        #soa-print-customer {
            display: block;
            margin-top: 1mm;
            font-size: 8pt;
            font-weight: 700;
        }

        #soa-table {
            display: table;
            width: 100%;
            min-width: 0;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 7pt;
        }

        #soa-table th,
        #soa-table td {
            padding: 1mm 0.35mm;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        #soa-table th:nth-child(1),
        #soa-table td:nth-child(1) { width: 21%; }

        #soa-table th:nth-child(2),
        #soa-table td:nth-child(2) { width: 21%; }

        #soa-table th:nth-child(3),
        #soa-table td:nth-child(3),
        #soa-table th:nth-child(4),
        #soa-table td:nth-child(4) { width: 29%; }

        .soa-screen-header { display: none; }
        .soa-print-header { display: inline; }
        #soa-table thead { display: table-header-group; border-bottom: 1px solid #000; }
        #soa-table thead th { font-size: 6.4pt; font-weight: 700; }
        #soa-table tr { break-inside: avoid; page-break-inside: avoid; }
        #soa-table tbody tr:nth-child(2n) { border-bottom: 1px dashed #000; }
        #soa-table tbody tr:nth-child(2n) td { padding-top: 0; padding-bottom: 1.5mm; }
        #soa-table tbody tr:nth-child(2n) td > div { margin: 0; padding: 0; border: 0; }
        #soa-table tbody tr:nth-child(2n) > td > div > div > div { gap: 0.5mm; font-size: 6.5pt; }
        #soa-table tbody tr:nth-child(2n) > td > div > div > div > span:first-child { min-width: 0; overflow-wrap: anywhere; }
        #soa-table tbody tr:nth-child(2n) > td > div > div > div > span:last-child { flex-shrink: 0; text-align: right; }
        #soa-table tfoot { display: table-row-group; border-top: 1px solid #000; break-inside: avoid; page-break-inside: avoid; }
        #soa-table tfoot td { padding-top: 1.5mm; font-size: 7.2pt; font-weight: 700; }
        #soa-table .text-right { text-align: right; }

        #state-of-account button,
        #state-of-account input,
        #state-of-account label,
        #state-of-account datalist { display: none !important; }
    }
</style>
