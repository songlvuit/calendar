@php($pm = ! $s->isMorning())
<button type="button" class="ev {{ $pm ? 'pm' : '' }} {{ $s->trashed() ? 'cancelled' : '' }}" data-open="{{ $s->id }}"
        title="{{ $s->scheduled_at->format('H:i d/m/Y') }} · {{ $s->lecturer }} · {{ $s->subject }}">
    @if ($compact ?? false)
        <div class="ev-row"><span class="ev-time">{{ $s->scheduled_at->format('H:i') }}</span><span class="ev-lect">{{ $s->lecturer }}</span></div>
    @elseif ($withDate ?? false)
        <div class="ev-row">
            <span class="ev-time">{{ $s->scheduled_at->format('H:i · d/m') }}</span>
            <span>
                <span class="ev-sub" style="color:var(--text)">{{ $s->subject }}</span>
                @if ($s->trashed())<span class="tag">Đã huỷ</span>@endif
                @if ($s->on_duty)<div class="ev-duty">Trực: {{ $s->on_duty }}</div>@endif
            </span>
        </div>
    @else
        <div class="ev-time">{{ $s->scheduled_at->format('H:i') }} @if ($s->trashed())<span class="tag">Đã huỷ</span>@endif</div>
        <div class="ev-lect">{{ $s->lecturer }}</div>
        <div class="ev-sub">{{ $s->subject }}</div>
        @if ($s->on_duty)<div class="ev-duty">Trực: {{ $s->on_duty }}</div>@endif
    @endif
</button>
