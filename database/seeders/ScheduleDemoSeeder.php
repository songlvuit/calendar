<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/** Dữ liệu mẫu cho tuần hiện tại: php artisan db:seed --class=ScheduleDemoSeeder */
class ScheduleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $mon = CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY);
        $rows = [
            [0, '07:30', 'ThS. Lê Minh Anh', 'Nhiếp ảnh căn bản', '0901 234 567', 'Hoàng Nam'],
            [0, '13:30', 'TS. Trần Quốc Bảo', 'Ánh sáng studio', '0912 888 101', null],
            [1, '07:00', 'ThS. Lê Minh Anh', 'Bố cục khuôn hình', '0901 234 567', 'Mai Linh'],
            [1, '18:00', 'ThS. Phạm Thu Hà', 'Hậu kỳ Lightroom', null, 'Hoàng Nam'],
            [2, '09:15', 'TS. Trần Quốc Bảo', 'Nhiếp ảnh chân dung', '0912 888 101', null],
            [3, '13:30', 'ThS. Phạm Thu Hà', 'Hậu kỳ Lightroom', null, 'Mai Linh'],
            [4, '07:30', 'ThS. Lê Minh Anh', 'Nhiếp ảnh căn bản', '0901 234 567', null],
            [5, '08:00', 'TS. Trần Quốc Bảo', 'Thực hành ngoại cảnh', '0912 888 101', 'Hoàng Nam'],
        ];
        foreach ($rows as [$d, $t, $lec, $sub, $phone, $duty]) {
            Schedule::create([
                'scheduled_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $mon->addDays($d)->format('Y-m-d').' '.$t),
                'lecturer' => $lec, 'subject' => $sub, 'lecturer_phone' => $phone, 'on_duty' => $duty,
            ]);
        }
    }
}
