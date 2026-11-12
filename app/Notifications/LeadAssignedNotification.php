<?php

namespace App\Notifications;

use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification implements ShouldQueue
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
            ->title('Lead di-assign ke Anda: '.$this->lead->company_name)
            ->icon('heroicon-o-user-plus')
            ->actions([Action::make('view')->label('Lihat')->url(route('filament.admin.resources.leads.view', $this->lead))])
            ->getDatabaseMessage();
    }
}
