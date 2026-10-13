@php
    $fields = [
        'lecturer' => ['Giảng viên', true, 'text', 'dl-lecturers', 'VD: TS. Nguyễn Văn A', 'name'],
        'subject' => ['Môn', true, 'text', 'dl-subjects', 'VD: Nhiếp ảnh căn bản', 'off'],
        'lecturer_phone' => ['SĐT giảng viên', false, 'tel', null, 'VD: 0901 234 567', 'tel'],
        'on_duty' => ['Người trực', false, 'text', 'dl-duty', 'Tên người trực', 'off'],
    ];
@endphp
<div class="row2">
@foreach ($fields as $name => [$label, $required, $type, $list, $ph, $ac])
    <div class="field {{ $e->has($name) ? 'has-err' : '' }}" @if ($name === 'lecturer_phone') style="grid-column: 1" @endif>
        <label for="{{ $p }}-{{ $name }}">{{ $label }}
            @if ($required)<span class="req">*</span>@else<span class="opt">(không bắt buộc)</span>@endif
        </label>
        <input type="{{ $type }}" id="{{ $p }}-{{ $name }}" name="{{ $name }}" @if ($list) list="{{ $list }}" @endif
               placeholder="{{ $ph }}" autocomplete="{{ $ac }}" @if ($required) required @endif
               maxlength="{{ $name === 'lecturer_phone' ? 20 : ($name === 'subject' ? 200 : 150) }}"
               value="{{ $useOld ? old($name) : '' }}">
        @if ($e->has($name))<div class="err">{{ $e->first($name) }}</div>@endif
    </div>
@endforeach
</div>
