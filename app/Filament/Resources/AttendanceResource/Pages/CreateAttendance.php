<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Models\Member;
use Carbon\Carbon;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $member = Member::find($data['member_id']);

        if (!$member) {
            $this->notify('danger', 'Member tidak ditemukan.');
            $this->halt();
        }

        if (!$member->is_active) {
            $this->notify('danger', "Member {$member->name} tidak aktif. Silakan perpanjang membership terlebih dahulu.");
            $this->halt();
        }

        if ($member->expiry_date) {
            $today = Carbon::now('Asia/Makassar')->startOfDay();
            $expiryDate = Carbon::parse($member->expiry_date)->startOfDay();

            if ($today->gte($expiryDate)) {
                $this->notify('danger', "Member {$member->name} membership sudah berakhir. Silakan perpanjang terlebih dahulu.");
                $this->halt();
            }
        }

        $tanggalInput = Carbon::parse($data['created_at']);
        if ($tanggalInput->startOfDay()->gt(Carbon::now('Asia/Makassar')->startOfDay())) {
            $this->notify('danger', 'Tidak bisa mencatat absensi untuk tanggal yang akan datang.');
            $this->halt();
        }

        $sudahAbsen = \App\Models\Attendance::where('member_id', $member->id)
            ->whereBetween('created_at', [
                Carbon::parse($data['created_at'])->startOfDay(),
                Carbon::parse($data['created_at'])->endOfDay(),
            ])
            ->exists();

        if ($sudahAbsen) {
            $tanggal = Carbon::parse($data['created_at'])->format('d/m/Y');
            $this->notify('danger', "{$member->name} sudah melakukan absensi pada tanggal {$tanggal}.");
            $this->halt();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Absensi berhasil dicatat';
    }
}