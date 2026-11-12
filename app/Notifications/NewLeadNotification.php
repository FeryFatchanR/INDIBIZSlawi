<?php

namespace App\Notifications;

use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/** Notifikasi lead baru di bell admin panel (database). Di-queue agar form publik tetap cepat. */
class NewLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Lead baru: '.$this->lead->company_name)
            ->body($this->lead->type->label().' — '.$this->lead->contact_name)
            ->icon('heroicon-o-inbox-arrow-down')
            ->actions([
                Action::make('view')->label('Lihat')->url(route('filament.admin.resources.leads.view', $this->lead)),
            ])
            ->getDatabaseMessage();
    }
}
