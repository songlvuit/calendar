<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    private const VIEWS = ['week', 'month', 'lecturer'];

    /** Thông báo lỗi tiếng Việt. */
    private const MESSAGES = [
        'required' => 'Vui lòng nhập :attribute.',
        'date_format' => ':attribute không đúng định dạng.',
        'max' => ':attribute không được dài quá :max ký tự.',
        'regex' => ':attribute không hợp lệ.',
    ];

    private const ATTRIBUTES = [
        'date' => 'ngày',
        'time' => 'giờ có mặt',
        'lecturer' => 'tên giảng viên',
        'subject' => 'môn',
        'lecturer_phone' => 'số điện thoại',
        'on_duty' => 'người trực',
        'password' => 'mật khẩu',
    ];

    public function index(Request $request): View
    {
        $view = in_array($request->query('view'), self::VIEWS, true) ? $request->query('view') : 'week';

        try {
            $anchor = CarbonImmutable::createFromFormat('Y-m-d', (string) $request->query('date'))->startOfDay();
        } catch (\Throwable) {
            $anchor = CarbonImmutable::today();
        }

        // Khoảng thời gian hiển thị
        if ($view === 'week') {
            $from = $anchor->startOfWeek(CarbonImmutable::MONDAY);
            $to = $from->addDays(6)->endOfDay();
            $prev = $from->subWeek();
            $next = $from->addWeek();
            $title = $from->format('d/m').' – '.$to->format('d/m/Y');
        } else {
            $from = $anchor->startOfMonth();
            $to = $anchor->endOfMonth();
            $prev = $from->subMonth();
            $next = $from->addMonth();
            $title = 'Tháng '.$from->format('n/Y');
        }

        $filters = [
            'subject' => trim((string) $request->query('subject', '')),
            'lecturer' => trim((string) $request->query('lecturer', '')),
            'cancelled' => $request->boolean('cancelled'),
        ];

        $query = Schedule::query()
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderBy('scheduled_at');
        if ($filters['cancelled']) {
            $query->withTrashed();
        }
        if ($filters['subject'] !== '') {
            $query->where('subject', $filters['subject']);
        }
        if ($filters['lecturer'] !== '') {
            $query->where('lecturer', $filters['lecturer']);
        }
        $schedules = $query->get();

        // Gom theo ngày -> buổi
        $byDay = [];
        foreach ($schedules as $s) {
            $byDay[$s->scheduled_at->format('Y-m-d')][$s->isMorning() ? 'morning' : 'afternoon'][] = $s;
        }

        // Các ô ngày cần vẽ
        if ($view === 'month') {
            $gridStart = $from->startOfWeek(CarbonImmutable::MONDAY);
            $gridEnd = $to->endOfWeek(CarbonImmutable::SUNDAY);
        } else {
            $gridStart = $from;
            $gridEnd = $to;
        }
        $days = collect(CarbonPeriod::create($gridStart, $gridEnd->startOfDay()))
            ->map(fn ($d) => CarbonImmutable::instance($d));

        // Danh sách gợi ý (lọc + tự điền)
        $allLecturers = Schedule::query()->select('lecturer')->distinct()->orderBy('lecturer')->pluck('lecturer');
        $allSubjects = Schedule::query()->select('subject')->distinct()->orderBy('subject')->pluck('subject');
        $allOnDuty = Schedule::query()->whereNotNull('on_duty')->select('on_duty')->distinct()->orderBy('on_duty')->pluck('on_duty');
        // SĐT gần nhất của từng giảng viên để tự điền
        $phones = Schedule::query()->whereNotNull('lecturer_phone')->orderBy('updated_at')
            ->get(['lecturer', 'lecturer_phone'])->pluck('lecturer_phone', 'lecturer');

        return view('schedules.index', [
            'view' => $view,
            'anchor' => $anchor,
            'title' => $title,
            'prev' => $prev,
            'next' => $next,
            'from' => $from,
            'days' => $days,
            'byDay' => $byDay,
            'schedules' => $schedules,
            'byLecturer' => $schedules->groupBy('lecturer')->sortKeys(),
            'filters' => $filters,
            'allLecturers' => $allLecturers,
            'allSubjects' => $allSubjects,
            'allOnDuty' => $allOnDuty,
            'phones' => $phones,
            'today' => CarbonImmutable::today(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateWithModal($request, [
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ] + $this->detailRules(), 'create');

        $schedule = Schedule::create([
            'scheduled_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['time']),
        ] + $this->details($data));

        return redirect()->back()->with('success', 'Đã thêm lịch: '.$schedule->lecturer.' – '.$schedule->scheduled_at->format('H:i d/m/Y').'.');
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $modal = 'edit-'.$schedule->id;
        $currentDate = $schedule->scheduled_at->format('Y-m-d');

        // Không cho phép đổi ngày: nếu có gửi lên ngày khác thì từ chối.
        if ($request->filled('date') && $request->input('date') !== $currentDate) {
            throw ValidationException::withMessages(['date' => 'Không được phép đổi ngày của lịch đã tạo.'])
                ->redirectTo(url()->previous());
        }

        $data = $this->validateWithModal($request, [
            'time' => ['required', 'date_format:H:i'],
        ] + $this->detailRules(), $modal);

        // Giữ nguyên ngày, chỉ cập nhật giờ phút.
        $schedule->update([
            'scheduled_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $currentDate.' '.$data['time']),
        ] + $this->details($data));

        return redirect()->back()->with('success', 'Đã cập nhật lịch của '.$schedule->lecturer.'.');
    }

    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        $expected = (string) config('schedule.delete_password');
        $given = (string) $request->input('password', '');
        $bag = 'delete';

        if ($expected === '') {
            return redirect()->back()->withInput(['_modal' => 'edit-'.$schedule->id])
                ->withErrors(['password' => 'Chưa cấu hình mật khẩu huỷ lịch (SCHEDULE_DELETE_PASSWORD).'], $bag);
        }
        if (! hash_equals($expected, $given)) {
            return redirect()->back()->withInput(['_modal' => 'edit-'.$schedule->id])
                ->withErrors(['password' => $given === '' ? 'Vui lòng nhập mật khẩu.' : 'Mật khẩu không đúng.'], $bag);
        }

        $schedule->delete(); // xoá mềm: vẫn xem lại được bằng "Hiện lịch đã huỷ"

        return redirect()->back()->with('success', 'Đã huỷ lịch của '.$schedule->lecturer.' ngày '.$schedule->scheduled_at->format('d/m/Y').'.');
    }

    private function detailRules(): array
    {
        return [
            'lecturer' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:200'],
            'lecturer_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+().\s-]{8,20}$/'],
            'on_duty' => ['nullable', 'string', 'max:150'],
        ];
    }

    private function details(array $data): array
    {
        return [
            'lecturer' => trim($data['lecturer']),
            'subject' => trim($data['subject']),
            'lecturer_phone' => filled($data['lecturer_phone'] ?? null) ? trim($data['lecturer_phone']) : null,
            'on_duty' => filled($data['on_duty'] ?? null) ? trim($data['on_duty']) : null,
        ];
    }

    /** Validate; nếu lỗi thì quay lại trang trước và mở lại đúng hộp thoại. */
    private function validateWithModal(Request $request, array $rules, string $modal): array
    {
        $validator = Validator::make($request->all(), $rules, self::MESSAGES, self::ATTRIBUTES);
        if ($validator->fails()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->back()
                    ->withInput($request->except('password') + ['_modal' => $modal])
                    ->withErrors($validator)
            );
        }

        return $validator->validated();
    }
}
