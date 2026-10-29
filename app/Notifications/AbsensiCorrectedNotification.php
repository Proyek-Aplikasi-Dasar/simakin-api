<?php

namespace App\Notifications;

use App\Models\AbsensiSiswa;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AbsensiCorrectedNotification extends Notification
{
    use Queueable;

    public function __construct(public AbsensiSiswa $absensi) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'absensi-corrected',
            'absensi_id' => $this->absensi->id,
            'tanggal' => $this->absensi->tanggal?->toDateString(),
            'status' => $this->absensi->status,
            'message' => 'Presensi Anda telah dikoreksi oleh ' . ($this->absensi->korektor?->name ?? 'admin') . '.',
        ];
    }
}
