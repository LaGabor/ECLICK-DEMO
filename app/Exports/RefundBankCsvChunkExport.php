<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

final class RefundBankCsvChunkExport extends DefaultValueBinder implements FromCollection, WithCustomCsvSettings, WithCustomValueBinder, WithHeadings
{
    public function __construct(
        private readonly Collection $rows,
    ) {}

    public function getCsvSettings(): array
    {
        return [
            'use_bom' => false,
        ];
    }

    public function headings(): array
    {
        return ['recipient_name', 'bank_account_number', 'refund_amount'];
    }

    public function collection(): Collection
    {
        return $this->rows->map(static fn (array $row): array => [
            $row['recipient_name'],
            $row['bank_account_number'],
            $row['refund_amount'],
        ]);
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }
}
