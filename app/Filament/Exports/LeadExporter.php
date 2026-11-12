<?php

namespace App\Filament\Exports;

use App\Models\Lead;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class LeadExporter extends Exporter
{
    protected static ?string $model = Lead::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('uuid')->label('ID'),
            ExportColumn::make('created_at')->label('Tanggal masuk'),
            ExportColumn::make('company_name')->label('Perusahaan'),
            ExportColumn::make('contact_name')->label('Kontak'),
            ExportColumn::make('phone')->label('Telepon'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('company_address')->label('Alamat'),
            ExportColumn::make('industrySolution.name')->label('Industri'),
            ExportColumn::make('product.name')->label('Produk'),
            ExportColumn::make('bandwidth_requirement')->label('Kebutuhan bandwidth'),
            ExportColumn::make('locations_count')->label('Jumlah lokasi'),
            ExportColumn::make('type')->label('Jenis')->formatStateUsing(fn ($state) => $state?->label()),
            ExportColumn::make('source')->label('Sumber')->formatStateUsing(fn ($state) => $state?->label()),
            ExportColumn::make('campaign.name')->label('Kampanye'),
            ExportColumn::make('status')->label('Status')->formatStateUsing(fn ($state) => $state?->label()),
            ExportColumn::make('assignee.name')->label('Sales'),
            ExportColumn::make('follow_up_at')->label('Follow-up'),
            ExportColumn::make('message')->label('Pesan'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export lead selesai: '.Number::format($export->successful_rows).' baris.';
        if ($failed = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' baris gagal.';
        }

        return $body;
    }
}
