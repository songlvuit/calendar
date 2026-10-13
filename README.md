# Lịch trực giảng (Laravel)

Ứng dụng web đặt lịch giảng viên theo **buổi Sáng / Chiều – Tối**, xem theo **Tuần**, **Tháng** hoặc **Theo giảng viên**.

## Tính năng

| Trường | Bắt buộc |
|---|---|
| Thời gian có mặt (ngày + giờ:phút) | Có |
| Giảng viên | Có |
| Môn | Có |
| SĐT giảng viên | Không |
| Người trực | Không |

- Lịch có giờ **trước 12:00** nằm ở ô **Sáng**, từ **12:00** trở đi nằm ở ô **Chiều – Tối**.
- Thêm lịch: nút **＋ Thêm lịch**, hoặc bấm **+** ở góc ô Sáng/Chiều của một ngày (tự điền sẵn ngày và giờ).
- **Ai cũng sửa được** lịch (giờ, giảng viên, môn, SĐT, người trực), nhưng **không đổi được ngày**. Máy chủ cũng kiểm tra lại nên không thể lách qua trình duyệt.
- **Huỷ lịch phải nhập mật khẩu.** Lịch bị huỷ được lưu lại; tick **"Hiện lịch đã huỷ"** để xem lại. Mỗi phút chỉ được thử mật khẩu tối đa 10 lần.
- Lọc theo môn, theo giảng viên. Gõ tên giảng viên đã có thì SĐT được tự điền.
- Dùng tốt trên điện thoại (xem tuần chuyển thành danh sách dọc).

## Cài đặt

Yêu cầu: PHP 8.3+, Composer, extension `pdo_sqlite` (hoặc MySQL).

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Mở file `.env` và **đổi mật khẩu huỷ lịch**:

```
SCHEDULE_DELETE_PASSWORD=mat-khau-cua-ban
```

Tạo cơ sở dữ liệu (mặc định SQLite) và chạy:

```bash
touch database/database.sqlite        # Windows: type nul > database\database.sqlite
php artisan migrate
php artisan serve
```

Mở http://localhost:8000

Muốn có dữ liệu mẫu để xem thử: `php artisan db:seed --class=ScheduleDemoSeeder`

### Dùng MySQL thay SQLite

Trong `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lich_truc
DB_USERNAME=root
DB_PASSWORD=
```

rồi chạy `php artisan migrate`.

## Các file chính

- `app/Models/Schedule.php` – model lịch, quy tắc Sáng/Chiều (`AFTERNOON_FROM_HOUR = 12`)
- `app/Http/Controllers/ScheduleController.php` – xem, thêm, sửa (khoá ngày), huỷ (mật khẩu)
- `database/migrations/2026_10_13_000000_create_schedules_table.php` – bảng `schedules`
- `resources/views/schedules/` – giao diện (`index`, `_event`, `_fields`)
- `resources/views/layouts/app.blade.php` – khung trang + CSS (không cần npm/Vite)
- `config/schedule.php` – đọc mật khẩu huỷ từ `.env`
