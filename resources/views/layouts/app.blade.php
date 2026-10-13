<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lịch trực giảng')</title>
    <style>
        :root {
            --bg: #f6f7f9; --surface: #fff; --surface-2: #f1f3f6; --border: #e3e6ea; --border-soft: #eceef1;
            --text: #1f2328; --muted: #6b7280; --faint: #9aa1ab;
            --accent: #4f46e5; --accent-soft: #eef0ff; --accent-text: #4338ca;
            --morning: #fff7e6; --morning-line: #f0b44c; --afternoon: #eef4ff; --afternoon-line: #6b8cff;
            --danger: #c2410c; --danger-soft: #fff1ea; --ok: #15803d; --ok-soft: #ecfdf3;
            --radius: 12px;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            background: var(--bg); color: var(--text);
            font: 15px/1.45 "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        a { color: inherit; text-decoration: none; }
        button, input, select { font: inherit; color: inherit; }
        .wrap { max-width: 1400px; margin: 0 auto; padding: 20px 16px 48px; }
        .app-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
        .app-title h1 { font-size: 20px; margin: 0; letter-spacing: -.01em; }
        .app-title p { margin: 2px 0 0; color: var(--muted); font-size: 13px; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 40px; padding: 0 16px;
               border: 1px solid var(--border); background: var(--surface); border-radius: 10px; cursor: pointer; white-space: nowrap; }
        .btn:hover { background: var(--surface-2); }
        .btn-primary { background: var(--accent); border-color: var(--accent); color: #fff; font-weight: 600; }
        .btn-primary:hover { background: var(--accent-text); }
        .btn-soft { background: var(--surface-2); }
        .btn-danger { background: var(--danger); border-color: var(--danger); color: #fff; font-weight: 600; }
        .btn-danger:hover { filter: brightness(.95); background: var(--danger); }
        .btn-icon { width: 40px; padding: 0; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        /* Toolbar */
        .toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .seg { display: inline-flex; padding: 4px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; }
        .seg a { padding: 7px 16px; border-radius: 9px; color: #4b5563; }
        .seg a.on { background: var(--surface); color: var(--accent); box-shadow: 0 1px 2px rgba(0,0,0,.06); font-weight: 500; }
        .range { font-size: 20px; font-weight: 600; min-width: 200px; text-align: center; letter-spacing: -.01em; }
        .filters { display: flex; justify-content: flex-end; align-items: center; gap: 12px; margin: 14px 0 16px; flex-wrap: wrap; }
        .filters select { height: 42px; min-width: 200px; padding: 0 12px; border: 1px solid var(--border); border-radius: 10px; background: var(--surface); }
        .check { display: inline-flex; align-items: center; gap: 8px; color: #4b5563; cursor: pointer; }
        .check input { width: 17px; height: 17px; accent-color: var(--accent); }

        /* Alerts */
        .alert { padding: 10px 14px; border-radius: 10px; margin-bottom: 12px; font-size: 14px; }
        .alert-ok { background: var(--ok-soft); color: var(--ok); border: 1px solid #c6ecd3; }
        .alert-err { background: var(--danger-soft); color: var(--danger); border: 1px solid #fbd3c0; }

        /* Calendar grid */
        .cal { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
        .day { border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); display: flex; flex-direction: column; min-width: 0; }
        .day:nth-child(7n) { border-right: 0; }
        .day.weekend { background: #fafbfc; }
        .day.outside { background: #fafbfc; }
        .day.outside .day-head, .day.outside .slot { opacity: .5; }
        .day-head { padding: 12px 14px; border-bottom: 1px solid var(--border); font-weight: 600; white-space: nowrap; }
        .day-head span { font-weight: 400; color: var(--muted); }
        .day.today .day-head { background: var(--accent-soft); color: var(--accent); }
        .day.today .day-head span { color: var(--accent); opacity: .8; }
        .slot { position: relative; padding: 12px 12px 14px; min-height: 150px; flex: 1; }
        .slot + .slot { border-top: 1px dashed var(--border); }
        .slot-label { font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin: 0 2px 8px; }
        .empty { color: #c3c8cf; padding: 0 2px; }
        .add { position: absolute; top: 8px; right: 8px; width: 26px; height: 26px; border-radius: 8px; border: 1px dashed var(--border);
               background: transparent; color: var(--muted); cursor: pointer; font-size: 17px; line-height: 1; opacity: 0; transition: opacity .12s; }
        .slot:hover .add, .add:focus-visible { opacity: 1; }
        .add:hover { border-style: solid; color: var(--accent); border-color: var(--accent); background: var(--accent-soft); }
        @media (hover: none) { .add { opacity: 1; } }

        .ev { display: block; width: 100%; text-align: left; border: 0; border-left: 3px solid var(--morning-line); background: var(--morning);
              border-radius: 8px; padding: 7px 9px; margin-bottom: 7px; cursor: pointer; }
        .ev.pm { border-left-color: var(--afternoon-line); background: var(--afternoon); }
        .ev:hover { filter: brightness(.97); }
        .ev-time { font-weight: 700; font-size: 13px; font-variant-numeric: tabular-nums; }
        .ev-lect { font-weight: 600; font-size: 14px; overflow-wrap: anywhere; }
        .ev-sub { font-size: 13px; color: #4b5563; overflow-wrap: anywhere; }
        .ev-duty { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .ev.cancelled { opacity: .55; background: var(--surface-2); border-left-color: var(--faint); }
        .ev.cancelled .ev-lect, .ev.cancelled .ev-sub { text-decoration: line-through; }
        .tag { display: inline-block; font-size: 11px; font-weight: 600; padding: 0 6px; border-radius: 6px; background: #e5e7eb; color: #4b5563; margin-left: 4px; }

        /* Month view: compact */
        .cal.month .slot { min-height: 64px; padding: 8px 8px 10px; }
        .cal.month .slot-label { font-size: 11px; margin-bottom: 4px; }
        .cal.month .ev { padding: 4px 7px; margin-bottom: 4px; }
        .cal.month .ev-lect { font-size: 13px; }
        .cal.month .ev-sub, .cal.month .ev-duty { display: none; }
        .cal.month .ev-row { gap: 6px; min-width: 0; }
        .cal.month .ev-row .ev-time { min-width: 0; flex: none; }
        .cal.month .ev-row .ev-lect { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; }
        .cal.month .day-head { padding: 8px 10px; }
        .dow-row { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border-bottom: 1px solid var(--border); background: var(--surface-2); }
        .dow-row div { padding: 8px 10px; font-size: 13px; font-weight: 600; color: var(--muted); }

        /* Lecturer view */
        .people { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
        .person { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .person h3 { margin: 0; padding: 12px 14px; font-size: 15px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; gap: 8px; }
        .person h3 small { font-weight: 400; color: var(--muted); }
        .person ul { list-style: none; margin: 0; padding: 8px; }
        .person li .ev { margin: 0 0 6px; }
        .ev-row { display: flex; gap: 10px; align-items: baseline; }
        .ev-row .ev-time { min-width: 108px; }
        .blank { background: var(--surface); border: 1px dashed var(--border); border-radius: var(--radius); padding: 40px 16px; text-align: center; color: var(--muted); }

        /* Dialog */
        dialog { border: 0; border-radius: 16px; padding: 0; width: min(520px, calc(100vw - 32px)); box-shadow: 0 20px 60px rgba(15,23,42,.25); color: var(--text); }
        dialog::backdrop { background: rgba(15,23,42,.35); }
        .dlg-head { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border); }
        .dlg-head h2 { margin: 0; font-size: 17px; }
        .dlg-body { padding: 16px 20px; }
        .dlg-foot { display: flex; justify-content: flex-end; gap: 8px; padding: 14px 20px; border-top: 1px solid var(--border); background: #fafbfc; }
        .x { border: 0; background: transparent; font-size: 22px; line-height: 1; cursor: pointer; color: var(--muted); width: 32px; height: 32px; border-radius: 8px; }
        .x:hover { background: var(--surface-2); }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .field { margin-bottom: 12px; }
        .field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
        .field label .req { color: var(--danger); }
        .field label .opt { color: var(--faint); font-weight: 400; }
        .field input { width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--border); border-radius: 10px; background: #fff; }
        .field input:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 0 3px var(--accent-soft); }
        .field input[readonly] { background: var(--surface-2); color: var(--muted); }
        .field .hint { font-size: 12px; color: var(--muted); margin-top: 4px; }
        .field .err { font-size: 12.5px; color: var(--danger); margin-top: 4px; }
        .field.has-err input { border-color: var(--danger); }
        .session-pill { display: inline-block; font-size: 12px; padding: 1px 8px; border-radius: 999px; background: var(--morning); color: #9a6100; margin-left: 6px; font-weight: 600; }
        .session-pill.pm { background: var(--afternoon); color: #2f4fd0; }
        .danger-zone { margin-top: 6px; padding: 12px; border: 1px solid #fbd3c0; background: var(--danger-soft); border-radius: 10px; }
        .danger-zone summary { cursor: pointer; color: var(--danger); font-weight: 600; font-size: 14px; }
        .danger-zone .inline { display: flex; gap: 8px; margin-top: 10px; }
        .danger-zone .inline input { flex: 1; height: 40px; padding: 0 12px; border: 1px solid #f5c2a8; border-radius: 10px; }
        .note { font-size: 13px; color: var(--muted); background: var(--surface-2); border-radius: 10px; padding: 10px 12px; margin-bottom: 12px; }

        @media (max-width: 900px) {
            .cal.week .cal-grid { grid-template-columns: 1fr; }
            .cal.week .day { border-right: 0; }
            .cal.week .slot { min-height: 0; }
            .cal.week .day-head { position: sticky; top: 0; z-index: 1; background: var(--surface); }
            .cal.week .day.today .day-head { background: var(--accent-soft); }
            .cal.month .ev-lect { display: none; }
            .cal.month .ev { padding: 3px 5px; }
            .cal.month .slot-label { display: none; }
            .cal.month .ev-time { font-size: 11px; }
            .cal.month .slot { min-height: 40px; padding: 6px 4px; }
            .cal.month .day-head { padding: 6px; font-size: 13px; }
            .dow-row div { padding: 6px 4px; font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .add { top: 4px; right: 4px; }
            .toolbar { gap: 8px; }
            .seg { width: 100%; }
            .seg a { flex: 1; text-align: center; padding: 7px 6px; }
            .range { min-width: 0; font-size: 17px; flex: 1; }
            .filters { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
            .filters select { min-width: 0; width: 100%; }
            .filters .check { grid-column: 1 / -1; }
            .row2 { grid-template-columns: 1fr; }
            .app-title .btn-primary { width: 100%; }
        }
    </style>
</head>
<body>
<div class="wrap">
    @yield('content')
</div>
@stack('scripts')
</body>
</html>
