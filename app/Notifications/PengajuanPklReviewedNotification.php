<?php

namespace App\Notifications;

use App\Models\PengajuanPkl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengajuanPklReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public PengajuanPkl $pengajuan,
        public string $status, // 'approved' | 'rejected'
        public ?string $alasan = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification (stored in DB).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->status === 'approved' ? 'pengajuan-approved' : 'pengajuan-rejected',
            'pengajuan_id' => $this->pengajuan->id,
            'perusahaan' => $this->pengajuan->perusahaan?->nama,
            'status' => $this->status,
            'alasan_penolakan' => $this->alasan,
            'message' => $this->status === 'approved'
                ? 'Pengajuan PKL Anda disetujui.'
                : 'Pengajuan PKL Anda ditolak.' . ($this->alasan ? ' Alasan: ' . $this->alasan : ''),
        ];
    }
}
