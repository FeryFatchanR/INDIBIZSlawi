<?php

namespace App\Filament\Resources\Leads;

use App\Actions\Crm\AddLeadNote;
use App\Actions\Crm\AssignLead;
use App\Actions\Crm\ChangeLeadStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Exports\LeadExporter;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Leads\RelationManagers\NotesRelationManager;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Lead';

    protected static ?string $pluralModelLabel = 'Leads';

    protected static ?string $recordTitleAttribute = 'company_name';

    /** Scoping data: user tanpa leads.view_all hanya melihat lead yang di-assign kepadanya (anti-IDOR). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->visibleTo(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->whereNull('deleted_at')->where('status', LeadStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Perusahaan & kontak')->columns(2)->columnSpanFull()->schema([
                TextInput::make('company_name')->label('Nama perusahaan')->required()->maxLength(180),
                TextInput::make('contact_name')->label('Nama kontak')->required()->maxLength(150),
                TextInput::make('phone')->label('Telepon')->required()->tel()->maxLength(30),
                TextInput::make('email')->label('Email')->email()->maxLength(180),
                Textarea::make('company_address')->label('Alamat')->maxLength(500)->rows(2)->columnSpanFull(),
                Select::make('industry_solution_id')->label('Industri')->relationship('industrySolution', 'name')->searchable()->preload(),
                TextInput::make('industry')->label('Industri (lainnya)')->maxLength(120),
            ]),
            Section::make('Kebutuhan')->columns(2)->columnSpanFull()->schema([
                Select::make('product_id')->label('Produk diminati')->relationship('product', 'name')->searchable()->preload(),
                TextInput::make('bandwidth_requirement')->label('Kebutuhan bandwidth')->maxLength(120),
                TextInput::make('locations_count')->label('Jumlah lokasi')->numeric()->minValue(1)->maxValue(10000),
                Select::make('type')->label('Jenis')->options(LeadType::options())->required()->default(LeadType::Contact->value),
                Select::make('source')->label('Sumber')->options(LeadSource::options())->required()->default(LeadSource::Manual->value),
                Select::make('campaign_id')->label('Kampanye')->relationship('campaign', 'name')->searchable()->preload(),
                DateTimePicker::make('follow_up_at')->label('Jadwal follow-up'),
                Textarea::make('message')->label('Pesan')->maxLength(3000)->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Perusahaan')->columnSpan(2)->columns(2)->schema([
                TextEntry::make('company_name')->label('Perusahaan'),
                TextEntry::make('contact_name')->label('Kontak'),
                TextEntry::make('phone')->label('Telepon')->copyable()
                    ->url(fn (Lead $r) => 'https://wa.me/'.ltrim(preg_replace('/^0/', '62', preg_replace('/\D/', '', $r->phone)), '+'), shouldOpenInNewTab: true),
                TextEntry::make('email')->label('Email')->placeholder('-')->copyable(),
                TextEntry::make('company_address')->label('Alamat')->placeholder('-')->columnSpanFull(),
                TextEntry::make('industrySolution.name')->label('Industri')->placeholder(fn (Lead $r) => $r->industry ?: '-'),
                TextEntry::make('product.name')->label('Produk')->placeholder('-'),
                TextEntry::make('bandwidth_requirement')->label('Bandwidth')->placeholder('-'),
                TextEntry::make('locations_count')->label('Jumlah lokasi')->placeholder('-'),
                TextEntry::make('message')->label('Pesan')->placeholder('-')->columnSpanFull(),
            ]),
            Section::make('Status')->columnSpan(1)->schema([
                TextEntry::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (LeadStatus $state) => $state->label())->color(fn (LeadStatus $state) => $state->color()),
                TextEntry::make('assignee.name')->label('Sales')->placeholder('Belum di-assign'),
                TextEntry::make('follow_up_at')->label('Follow-up')->dateTime('d M Y H:i')->placeholder('-'),
                TextEntry::make('type')->label('Jenis')->formatStateUsing(fn (LeadType $state) => $state->label()),
                TextEntry::make('source')->label('Sumber')->formatStateUsing(fn (LeadSource $state) => $state->label()),
                TextEntry::make('campaign.name')->label('Kampanye')->placeholder('-'),
                TextEntry::make('landing_page')->label('Landing page')->placeholder('-'),
                TextEntry::make('lost_reason')->label('Alasan lost')->visible(fn (Lead $r) => $r->status === LeadStatus::Lost),
                TextEntry::make('created_at')->label('Masuk')->dateTime('d M Y H:i'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $canViewAll = fn () => auth()->user()->can('leads.view_all');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'assignee']))
            ->columns([
                TextColumn::make('company_name')->label('Perusahaan')->searchable()->sortable()->description(fn (Lead $r) => $r->contact_name),
                TextColumn::make('phone')->label('Telepon')->searchable()->toggleable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.name')->label('Produk')->placeholder('-'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (LeadStatus $state) => $state->label())->color(fn (LeadStatus $state) => $state->color())->sortable(),
                TextColumn::make('assignee.name')->label('Sales')->placeholder('-')->visible($canViewAll),
                TextColumn::make('source')->label('Sumber')->formatStateUsing(fn (LeadSource $state) => $state->label())->toggleable(),
                TextColumn::make('follow_up_at')->label('Follow-up')->dateTime('d M Y')->sortable()
                    ->color(fn (Lead $r) => $r->follow_up_at?->isPast() && ! in_array($r->status, [LeadStatus::Won, LeadStatus::Lost], true) ? 'danger' : null),
                TextColumn::make('created_at')->label('Masuk')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(LeadStatus::options())->multiple(),
                SelectFilter::make('assigned_to')->label('Sales')->visible($canViewAll)
                    ->options(fn () => User::permission(['leads.view_assigned', 'leads.view_all'])->pluck('name', 'id')),
                SelectFilter::make('product_id')->label('Produk')->relationship('product', 'name'),
                SelectFilter::make('source')->label('Sumber')->options(LeadSource::options()),
                SelectFilter::make('campaign_id')->label('Kampanye')->relationship('campaign', 'name'),
                Filter::make('overdue')->label('Follow-up terlewat')
                    ->query(fn (Builder $query) => $query->where('follow_up_at', '<', now())->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost])),
                Filter::make('created')->schema([DatePicker::make('from')->label('Masuk dari'), DatePicker::make('until')->label('Sampai')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '<=', $d))),
                TrashedFilter::make()->visible(fn () => auth()->user()->can('leads.delete')),
            ])
            ->headerActions([
                ExportAction::make()->label('Export Excel/CSV')->exporter(LeadExporter::class)
                    ->authorize(fn () => auth()->user()->can('export', Lead::class))
                    ->before(fn () => ActivityLog::record('leads.exported', null, 'Export data lead')),
            ])
            ->recordActions([
                ViewAction::make(),
                self::changeStatusAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_assign')->label('Assign ke sales')->icon('heroicon-o-user-plus')
                        ->authorize(fn () => auth()->user()->can('assign', Lead::class))
                        ->schema([self::assigneeSelect()])
                        ->action(function (Collection $records, array $data) {
                            $assignee = User::find($data['assigned_to']);
                            foreach ($records as $lead) {
                                app(AssignLead::class)->execute($lead, $assignee, auth()->user());
                            }
                            Notification::make()->title($records->count().' lead di-assign.')->success()->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function assigneeSelect(): Select
    {
        return Select::make('assigned_to')->label('Sales')->required()->searchable()
            ->options(fn () => User::query()->where('is_active', true)
                ->permission(['leads.view_assigned', 'leads.view_all'])->orderBy('name')->pluck('name', 'id'));
    }

    public static function changeStatusAction(): Action
    {
        return Action::make('change_status')->label('Ubah status')->icon('heroicon-o-arrow-path-rounded-square')
            ->authorize(fn (Lead $record) => auth()->user()->can('changeStatus', $record))
            ->fillForm(fn (Lead $record) => ['status' => $record->status->value, 'follow_up_at' => $record->follow_up_at])
            ->schema([
                Select::make('status')->label('Status baru')->options(LeadStatus::options())->required()->live(),
                TextInput::make('lost_reason')->label('Alasan lost')->maxLength(255)
                    ->visible(fn ($get) => $get('status') === LeadStatus::Lost->value)->required(fn ($get) => $get('status') === LeadStatus::Lost->value),
                DateTimePicker::make('follow_up_at')->label('Jadwal follow-up berikutnya'),
                Textarea::make('note')->label('Catatan (opsional)')->maxLength(5000)->rows(3),
            ])
            ->action(function (Lead $record, array $data) {
                app(ChangeLeadStatus::class)->execute(
                    $record, LeadStatus::from($data['status']), auth()->user(), $data['note'] ?? null, $data['lost_reason'] ?? null,
                    filled($data['follow_up_at'] ?? null) ? \Illuminate\Support\Carbon::parse($data['follow_up_at']) : null,
                );
                Notification::make()->title('Status lead diperbarui.')->success()->send();
            });
    }

    public static function assignAction(): Action
    {
        return Action::make('assign')->label('Assign sales')->icon('heroicon-o-user-plus')
            ->authorize(fn (Lead $record) => auth()->user()->can('assign', $record))
            ->fillForm(fn (Lead $record) => ['assigned_to' => $record->assigned_to])
            ->schema([self::assigneeSelect()])
            ->action(function (Lead $record, array $data) {
                app(AssignLead::class)->execute($record, User::find($data['assigned_to']), auth()->user());
                Notification::make()->title('Lead di-assign.')->success()->send();
            });
    }

    public static function addNoteAction(): Action
    {
        return Action::make('add_note')->label('Tambah catatan')->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->authorize(fn (Lead $record) => auth()->user()->can('addNote', $record))
            ->schema([
                Textarea::make('body')->label('Catatan follow-up')->required()->maxLength(5000)->rows(4),
                DateTimePicker::make('follow_up_at')->label('Jadwalkan follow-up'),
            ])
            ->action(function (Lead $record, array $data) {
                app(AddLeadNote::class)->execute($record, auth()->user(), $data['body'],
                    filled($data['follow_up_at'] ?? null) ? \Illuminate\Support\Carbon::parse($data['follow_up_at']) : null);
                Notification::make()->title('Catatan ditambahkan.')->success()->send();
            });
    }

    public static function getRelations(): array
    {
        return [NotesRelationManager::class, ActivitiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'view' => ViewLead::route('/{record}'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }
}
