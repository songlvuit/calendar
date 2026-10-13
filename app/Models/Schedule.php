<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use SoftDeletes;

    /** Giờ bắt đầu buổi chiều: từ 12:00 trở đi tính là "Chiều – Tối". */
    public const AFTERNOON_FROM_HOUR = 12;

    protected $fillable = ['scheduled_at', 'lecturer', 'subject', 'lecturer_phone', 'on_duty'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function isMorning(): bool
    {
        return $this->scheduled_at->hour < self::AFTERNOON_FROM_HOUR;
    }

    public function sessionLabel(): string
    {
        return $this->isMorning() ? 'Sáng' : 'Chiều – Tối';
    }
}
