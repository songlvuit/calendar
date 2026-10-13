@extends('layouts.app')

@php
    $dow = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
    $base = array_filter([
        'view' => $view,
        'date' => $anchor->format('Y-m-d'),
        'subject' => $filters['subject'],
        'lecturer' => $filters['lecturer'],
        'cancelled' => $filters['cancelled'] ? 1 : null,
    ], fn ($v) => $v !== null && $v !== '');
    $link = fn (array $over) => route('schedules.index', array_filter(array_merge($base, $over), fn ($v) => $v !== null && $v !== ''));

    // Dữ liệu cho hộp thoại sửa
    $payload = $schedules->mapWithKeys(fn ($s) => [$s->id => [
        'id' => $s->id,
        'date' => $s->scheduled_at->format('Y-m-d'),
        'dateLabel' => $dow[$s->scheduled_at->dayOfWeekIso].', '.$s->scheduled_at->format('d/m/Y'),
        'time' => $s->scheduled_at->format('H:i'),
        'lecturer' => $s->lecturer,
        'subject' => $s->subject,
        'lecturer_phone' => $s->lecturer_phone,
        'on_duty' => $s->on_duty,
        'cancelled' => $s->trashed(),
        'cancelledAt' => $s->deleted_at?->format('H:i d/m/Y'),
        'url' => route('schedules.update', $s->id, false),
        'deleteUrl' => route('schedules.destroy', $s->id, false),
    ]]);
    $openModal = old('_modal');
@endphp

@section('title', 'Lịch trực giảng · '.$title)

@section('content')
    <div class="app-title">
        <div>
            <h1>Lịch trực giảng</h1>
            <p>Bấm vào một lịch để xem hoặc sửa · Bấm <b>+</b> trong ô Sáng/Chiều để thêm nhanh</p>
        </div>
        <button type="button" class="btn btn-primary" data-create="{{ ($view === 'week' && $today->between($from, $from->addDays(6))) || $view !== 'week' ? $today->format('Y-m-d') : $from->format('Y-m-d') }}" data-time="07:30">＋ Thêm lịch</button>
    </div>

    @if (session('success'))
        <div class="alert alert-ok" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any() && ! $openModal)
        <div class="alert alert-err" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="toolbar">
        <nav class="seg" aria-label="Kiểu xem">
            <a href="{{ $link(['view' => 'week']) }}" class="{{ $view === 'week' ? 'on' : '' }}">Tuần</a>
            <a href="{{ $link(['view' => 'month']) }}" class="{{ $view === 'month' ? 'on' : '' }}">Tháng</a>
            <a href="{{ $link(['view' => 'lecturer']) }}" class="{{ $view === 'lecturer' ? 'on' : '' }}">Theo giảng viên</a>
        </nav>
        <a class="btn btn-icon" href="{{ $link(['date' => $prev->format('Y-m-d')]) }}" aria-label="Trước">‹</a>
        <div class="range">{{ $title }}</div>
        <a class="btn btn-icon" href="{{ $link(['date' => $next->format('Y-m-d')]) }}" aria-label="Sau">›</a>
        <a class="btn btn-soft" href="{{ $link(['date' => null]) }}">Hôm nay</a>
    </div>

    <form class="filters" method="get" action="{{ route('schedules.index') }}" id="filters">
        <input type="hidden" name="view" value="{{ $view }}">
        <input type="hidden" name="date" value="{{ $anchor->format('Y-m-d') }}">
        <select name="subject" aria-label="Lọc theo môn">
            <option value="">Mọi môn</option>
            @foreach ($allSubjects as $sub)
                <option value="{{ $sub }}" @selected($filters['subject'] === $sub)>{{ $sub }}</option>
            @endforeach
        </select>
        <select name="lecturer" aria-label="Lọc theo giảng viên">
            <option value="">Mọi giảng viên</option>
            @foreach ($allLecturers as $lec)
                <option value="{{ $lec }}" @selected($filters['lecturer'] === $lec)>{{ $lec }}</option>
            @endforeach
        </select>
        <label class="check"><input type="checkbox" name="cancelled" value="1" @checked($filters['cancelled'])> Hiện lịch đã huỷ</label>
        <noscript><button class="btn">Lọc</button></noscript>
    </form>

    @if ($view === 'lecturer')
        @if ($byLecturer->isEmpty())
            <div class="blank">Không có lịch nào trong {{ mb_strtolower($title) }}.</div>
        @else
            <div class="people">
                @foreach ($byLecturer as $lecturer => $items)
                    <section class="person">
                        <h3>
                            <span>{{ $lecturer }}</span>
                            <small>{{ $items->reject->trashed()->count() }} buổi @if ($items->first()->lecturer_phone) · {{ $items->first()->lecturer_phone }} @endif</small>
                        </h3>
                        <ul>
                            @foreach ($items as $s)
                                <li>@include('schedules._event', ['s' => $s, 'withDate' => true])</li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    @else
        <div class="cal {{ $view }}">
            @if ($view === 'month')
                <div class="dow-row">@foreach ($dow as $d)<div>{{ $d }}</div>@endforeach</div>
            @endif
            <div class="cal-grid">
                @foreach ($days as $day)
                    @php
                        $key = $day->format('Y-m-d');
                        $classes = [];
                        if ($day->isSameDay($today)) $classes[] = 'today';
                        if ($day->isWeekend()) $classes[] = 'weekend';
                        if ($view === 'month' && $day->month !== $from->month) $classes[] = 'outside';
                    @endphp
                    <div class="day {{ implode(' ', $classes) }}">
                        <div class="day-head">
                            @if ($view === 'week'){{ $dow[$day->dayOfWeekIso] }} @endif<span>{{ $day->format('d/m') }}</span>
                        </div>
                        @foreach (['morning' => ['Sáng', '07:30'], 'afternoon' => ['Chiều – Tối', '13:30']] as $slot => [$label, $defTime])
                            <div class="slot">
                                <div class="slot-label">{{ $label }}</div>
                                <button type="button" class="add" data-create="{{ $key }}" data-time="{{ $defTime }}"
                                        aria-label="Thêm lịch {{ $label }} {{ $day->format('d/m') }}">+</button>
                                @forelse ($byDay[$key][$slot] ?? [] as $s)
                                    @include('schedules._event', ['s' => $s, 'compact' => $view === 'month'])
                                @empty
                                    <div class="empty">—</div>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Gợi ý nhập nhanh --}}
    <datalist id="dl-lecturers">@foreach ($allLecturers as $v)<option value="{{ $v }}">@endforeach</datalist>
    <datalist id="dl-subjects">@foreach ($allSubjects as $v)<option value="{{ $v }}">@endforeach</datalist>
    <datalist id="dl-duty">@foreach ($allOnDuty as $v)<option value="{{ $v }}">@endforeach</datalist>

    {{-- ===== Hộp thoại THÊM ===== --}}
    <dialog id="dlg-create" aria-labelledby="dlg-create-title">
        <form method="post" action="{{ route('schedules.store') }}" novalidate>
            @csrf
            <input type="hidden" name="_modal" value="create">
            <div class="dlg-head"><h2 id="dlg-create-title">Thêm lịch</h2><button type="button" class="x" data-close aria-label="Đóng">×</button></div>
            <div class="dlg-body">
                @php($e = $openModal === 'create' ? $errors : new \Illuminate\Support\ViewErrorBag)
                <div class="row2">
                    <div class="field {{ $e->has('date') ? 'has-err' : '' }}">
                        <label for="c-date">Ngày <span class="req">*</span></label>
                        <input type="date" id="c-date" name="date" required value="{{ $openModal === 'create' ? old('date') : '' }}">
                        @if ($e->has('date'))<div class="err">{{ $e->first('date') }}</div>@endif
                    </div>
                    <div class="field {{ $e->has('time') ? 'has-err' : '' }}">
                        <label for="c-time">Giờ có mặt <span class="req">*</span> <span class="session-pill" data-pill></span></label>
                        <input type="time" id="c-time" name="time" required step="300" value="{{ $openModal === 'create' ? old('time') : '' }}">
                        @if ($e->has('time'))<div class="err">{{ $e->first('time') }}</div>@endif
                    </div>
                </div>
                @include('schedules._fields', ['p' => 'c', 'e' => $e, 'useOld' => $openModal === 'create'])
            </div>
            <div class="dlg-foot">
                <button type="button" class="btn" data-close>Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu lịch</button>
            </div>
        </form>
    </dialog>

    {{-- ===== Hộp thoại SỬA / XEM ===== --}}
    <dialog id="dlg-edit" aria-labelledby="dlg-edit-title">
        @php($editing = str_starts_with((string) $openModal, 'edit-'))
        @php($e = $editing ? $errors : new \Illuminate\Support\ViewErrorBag)
        <form method="post" action="" id="edit-form" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="_modal" value="">
            <div class="dlg-head"><h2 id="dlg-edit-title">Sửa lịch</h2><button type="button" class="x" data-close aria-label="Đóng">×</button></div>
            <div class="dlg-body">
                <div class="note" data-cancelled-note hidden></div>
                <div class="row2">
                    <div class="field">
                        <label for="u-date">Ngày</label>
                        <input type="text" id="u-date" readonly tabindex="-1" value="">
                        @if ($e->has('date'))<div class="err">{{ $e->first('date') }}</div>@else<div class="hint">Không thể đổi ngày của lịch đã tạo.</div>@endif
                    </div>
                    <div class="field {{ $e->has('time') ? 'has-err' : '' }}">
                        <label for="u-time">Giờ có mặt <span class="req">*</span> <span class="session-pill" data-pill></span></label>
                        <input type="time" id="u-time" name="time" required step="300">
                        @if ($e->has('time'))<div class="err">{{ $e->first('time') }}</div>@endif
                    </div>
                </div>
                @include('schedules._fields', ['p' => 'u', 'e' => $e, 'useOld' => false])
            </div>
            <div class="dlg-foot" data-edit-actions>
                <button type="button" class="btn" data-close>Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
        <form method="post" action="" id="delete-form" class="dlg-body" style="padding-top:0">
            @csrf
            @method('DELETE')
            @php($de = $errors->getBag('delete'))
            <details class="danger-zone" @if ($de->any()) open @endif>
                <summary>Huỷ lịch này</summary>
                <div class="inline">
                    <input type="password" name="password" placeholder="Nhập mật khẩu huỷ lịch" autocomplete="off" aria-label="Mật khẩu huỷ lịch">
                    <button type="submit" class="btn btn-danger">Huỷ lịch</button>
                </div>
                @if ($de->any())<div class="field"><div class="err">{{ $de->first('password') }}</div></div>@endif
            </details>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
(function () {
    const data = @json($payload);
    const phones = @json($phones);
    const openModal = @json($openModal);
    const oldInput = @json($editing ? request()->old() : null);
    const AFTERNOON = {{ \App\Models\Schedule::AFTERNOON_FROM_HOUR }};

    const dlgCreate = document.getElementById('dlg-create');
    const dlgEdit = document.getElementById('dlg-edit');
    const editForm = document.getElementById('edit-form');
    const delForm = document.getElementById('delete-form');

    // Hiện nhãn Sáng / Chiều – Tối theo giờ đang chọn
    function pill(dlg) {
        const t = dlg.querySelector('input[type=time]'), p = dlg.querySelector('[data-pill]');
        const upd = () => {
            if (!t.value) { p.textContent = ''; p.hidden = true; return; }
            const pm = parseInt(t.value, 10) >= AFTERNOON;
            p.hidden = false; p.textContent = pm ? 'Chiều – Tối' : 'Sáng'; p.classList.toggle('pm', pm);
        };
        t.addEventListener('input', upd); upd();
        return upd;
    }
    const pillC = pill(dlgCreate), pillU = pill(dlgEdit);

    // Tự điền SĐT khi chọn giảng viên đã có
    document.querySelectorAll('input[name=lecturer]').forEach(inp => {
        inp.addEventListener('change', () => {
            const phone = inp.form.querySelector('input[name=lecturer_phone]');
            if (phone && !phone.value && phones[inp.value.trim()]) phone.value = phones[inp.value.trim()];
        });
    });

    function openCreate(date, time) {
        const f = dlgCreate.querySelector('form');
        f.date.value = date || '';
        f.time.value = time || '';
        dlgCreate.querySelectorAll('.err').forEach(e => e.remove());
        dlgCreate.querySelectorAll('.has-err').forEach(e => e.classList.remove('has-err'));
        pillC(); dlgCreate.showModal();
        f.lecturer.focus();
    }

    function openEdit(id, fromOld) {
        const s = data[id]; if (!s) return;
        const src = fromOld && oldInput ? { ...s, ...oldInput } : s;
        editForm.action = s.url; delForm.action = s.deleteUrl;
        editForm._modal.value = 'edit-' + s.id;
        editForm.querySelector('#u-date').value = s.dateLabel;
        editForm.time.value = src.time ?? s.time;
        ['lecturer', 'subject', 'lecturer_phone', 'on_duty'].forEach(k => editForm[k].value = src[k] ?? '');
        const ro = !!s.cancelled;
        editForm.querySelectorAll('input:not([type=hidden])').forEach(i => { if (i.id !== 'u-date') i.readOnly = ro; });
        editForm.querySelector('[type=submit]').hidden = ro;
        delForm.hidden = ro;
        const note = editForm.querySelector('[data-cancelled-note]');
        note.hidden = !ro; note.textContent = ro ? 'Lịch này đã bị huỷ lúc ' + s.cancelledAt + '. Chỉ xem, không thể sửa.' : '';
        dlgEdit.querySelector('#dlg-edit-title').textContent = ro ? 'Lịch đã huỷ' : 'Sửa lịch';
        if (!fromOld) {
            dlgEdit.querySelectorAll('.err').forEach(e => e.remove());
            dlgEdit.querySelectorAll('.has-err').forEach(e => e.classList.remove('has-err'));
            delForm.querySelector('details').open = false;
            delForm.password.value = '';
        }
        pillU(); dlgEdit.showModal();
    }

    document.addEventListener('click', e => {
        const c = e.target.closest('[data-create]');
        if (c) { openCreate(c.dataset.create, c.dataset.time); return; }
        const o = e.target.closest('[data-open]');
        if (o) { openEdit(o.dataset.open, false); return; }
        if (e.target.closest('[data-close]')) e.target.closest('dialog').close();
    });
    [dlgCreate, dlgEdit].forEach(d => d.addEventListener('click', e => { if (e.target === d) d.close(); }));

    delForm.addEventListener('submit', e => {
        if (!delForm.password.value) { e.preventDefault(); delForm.password.focus(); return; }
        if (!confirm('Bạn chắc chắn muốn huỷ lịch này?')) e.preventDefault();
    });

    // Bộ lọc tự áp dụng
    document.querySelectorAll('#filters select, #filters input[type=checkbox]').forEach(el =>
        el.addEventListener('change', () => document.getElementById('filters').submit()));

    // Mở lại hộp thoại khi có lỗi
    if (openModal === 'create') { pillC(); dlgCreate.showModal(); }
    else if (openModal && openModal.startsWith('edit-')) openEdit(openModal.slice(5), true);
})();
</script>
@endpush
