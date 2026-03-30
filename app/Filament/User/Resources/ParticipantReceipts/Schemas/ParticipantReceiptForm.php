<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ParticipantReceipts\Schemas;

use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Receipt;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;

final class ParticipantReceiptForm
{
    private const string FORM_ROOT = 'data';

    public static function configure(Schema $schema, ?Receipt $record = null): Schema
    {
        $forEdit = $record !== null;

        $adminMessageFromTeam = Html::make(function () use ($forEdit, $record): HtmlString {
            if (! $forEdit || $record === null || blank($record->admin_note)) {
                return new HtmlString('');
            }

            return new HtmlString(
                '<div role="note" class="rounded-xl border border-sky-200 bg-sky-50/90 p-4 shadow-sm ring-1 ring-sky-950/5 dark:border-sky-800/50 dark:bg-sky-950/35 dark:ring-white/10">'
                .'<p class="text-sm font-semibold text-sky-950 dark:text-sky-100 mb-2">'
                .e(__('user.receipts.admin_message_heading'))
                .'</p>'
                .'<div class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">'
                .e((string) $record->admin_note)
                .'</div>'
                .'</div>',
            );
        })->columnSpanFull();

        $promotionField = Select::make('promotion_id')
            ->label(__('user.receipts.promotion'))
            ->options(function () use ($forEdit, $record): array {
                if ($forEdit && $record !== null) {
                    return [
                        (int) $record->promotion_id => (string) ($record->promotion?->name ?? '#'.$record->promotion_id),
                    ];
                }

                return Promotion::query()
                    ->acceptingParticipantUploadsOn(CarbonImmutable::now())
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all();
            })
            ->required()
            ->live()
            ->searchable()
            ->disabled($forEdit)
            ->dehydrated(true);

        if (! $forEdit) {
            $promotionField->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                $promotionId = $state;

                if (blank($promotionId)) {
                    $set('/'.self::FORM_ROOT.'.lines', [['product_id' => null, 'quantity' => 1]]);

                    return;
                }

                $promotion = Promotion::query()
                    ->with(['products' => fn ($q) => $q->where('products.active', true)->orderBy('products.name')])
                    ->find((int) $promotionId);

                if ($promotion === null) {
                    $set('/'.self::FORM_ROOT.'.lines', [['product_id' => null, 'quantity' => 1]]);

                    return;
                }

                $validIds = $promotion->products->pluck('id')->map(static fn ($id): int => (int) $id)->all();

                $lines = $get('/'.self::FORM_ROOT.'.lines');
                if (! is_array($lines)) {
                    $lines = [];
                }

                $newLines = [];
                foreach ($lines as $line) {
                    if (! is_array($line)) {
                        continue;
                    }

                    $pid = $line['product_id'] ?? null;
                    $qty = max(1, (int) ($line['quantity'] ?? 1));

                    if ($pid !== null && $pid !== '' && in_array((int) $pid, $validIds, true)) {
                        $newLines[] = ['product_id' => (int) $pid, 'quantity' => $qty];
                    } else {
                        $newLines[] = ['product_id' => null, 'quantity' => $qty];
                    }
                }

                if ($newLines === []) {
                    $newLines = [['product_id' => null, 'quantity' => 1]];
                }

                $set('/'.self::FORM_ROOT.'.lines', $newLines);

                $purchaseRaw = $get('/'.self::FORM_ROOT.'.purchase_date');
                if (filled($purchaseRaw)) {
                    $purchaseDay = CarbonImmutable::parse($purchaseRaw)->startOfDay();
                    $earliest = $promotion->earliestAllowedPurchaseDate();
                    $latest = $promotion->latestAllowedPurchaseDateAsOf(CarbonImmutable::now());
                    if ($purchaseDay->lt($earliest) || $purchaseDay->gt($latest)) {
                        $set('/'.self::FORM_ROOT.'.purchase_date', null);
                    }
                }
            });
        }

        $linesRepeater = Repeater::make('lines')
            ->label(__('user.receipts.lines_heading'))
            ->helperText(__('user.receipts.lines_description'))
            ->minItems(1)
            ->defaultItems(1)
            ->reorderable(false)
            ->live()
            ->schema([
                Grid::make(12)
                    ->schema([
                        Html::make(function (Get $get): HtmlString {
                            $pid = $get('product_id');
                            if (blank($pid)) {
                                return new HtmlString('');
                            }

                            $product = Product::query()->find((int) $pid);
                            $imageUrl = null;
                            if ($product !== null && filled($product->product_image)) {
                                $imageUrl = route('media.products.image', $product, absolute: true);
                            }

                            return new HtmlString(View::make(
                                'filament.user.partials.participant-receipt-line-product-thumb',
                                ['imageUrl' => $imageUrl],
                            )->render());
                        })
                            ->hidden(fn (Get $get): bool => blank($get('product_id')))
                            ->columnSpan([
                                'default' => 12,
                                'sm' => 12,
                                'md' => 2,
                            ])
                            ->live(),
                        Select::make('product_id')
                            ->label(__('user.receipts.product'))
                            ->options(function (Get $get): array {
                                return self::productOptionsForPromotion($get('/'.self::FORM_ROOT.'.promotion_id'));
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn (Get $get): bool => blank($get('/'.self::FORM_ROOT.'.promotion_id')))
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live()
                            ->columnSpan(fn (Get $get): array => filled($get('product_id'))
                                ? ['default' => 12, 'sm' => 12, 'md' => 5]
                                : ['default' => 12, 'sm' => 12, 'md' => 7]),
                        TextInput::make('quantity')
                            ->label(__('user.receipts.quantity_short'))
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live(debounce: 250)
                            ->columnSpan(fn (Get $get): array => filled($get('product_id'))
                                ? ['default' => 12, 'sm' => 12, 'md' => 5]
                                : ['default' => 12, 'sm' => 12, 'md' => 5]),
                    ]),
            ])
            ->columnSpanFull();

        $imageField = FileUpload::make('receipt_image')
            ->label(__('user.receipts.receipt_photo'))
            ->image()
            ->disk((string) config('image_upload.disk'))
            ->directory((string) config('image_upload.path.receipt_staging'))
            ->visibility('private')
            ->maxSize((int) config('image_upload.max_upload_kb') * 1024)
            ->acceptedFileTypes(['image/jpeg', 'image/png'])
            ->imageEditor(false)
            ->helperText(__('user.receipts.receipt_photo_help'));

        if (! $forEdit) {
            $imageField->required();
        }

        $purchaseDateField = DatePicker::make('purchase_date')
            ->label(__('user.receipts.purchase_date'))
            ->required()
            ->native(false)
            ->minDate(function (Get $get): ?CarbonImmutable {
                $id = $get('/'.self::FORM_ROOT.'.promotion_id');
                if (blank($id)) {
                    return null;
                }

                $promotion = Promotion::query()->find((int) $id);

                return $promotion?->earliestAllowedPurchaseDate();
            })
            ->maxDate(function (Get $get): CarbonImmutable {
                $id = $get('/'.self::FORM_ROOT.'.promotion_id');
                if (blank($id)) {
                    return CarbonImmutable::now()->startOfDay();
                }

                $promotion = Promotion::query()->find((int) $id);
                if ($promotion === null) {
                    return CarbonImmutable::now()->startOfDay();
                }

                return $promotion->latestAllowedPurchaseDateAsOf(CarbonImmutable::now());
            });

        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->schema([
                        $adminMessageFromTeam,
                        $promotionField,
                        $purchaseDateField,
                        TextInput::make('ap_code')
                            ->label(__('user.receipts.ap_code'))
                            ->required()
                            ->maxLength(128)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make()->schema([$linesRepeater]),
                Section::make()->schema([$imageField]),
                Section::make(__('user.receipts.expected_refund'))
                    ->description(__('user.receipts.refund_preview_description'))
                    ->schema([
                        Html::make(function (Get $get): HtmlString {
                            $promotionId = $get('/'.self::FORM_ROOT.'.promotion_id');
                            $lines = $get('/'.self::FORM_ROOT.'.lines');

                            if (blank($promotionId) || ! is_array($lines)) {
                                return new HtmlString(
                                    '<p class="text-sm text-gray-500 dark:text-gray-400">'
                                    .e(__('user.receipts.refund_preview_pick_promotion'))
                                    .'</p>',
                                );
                            }

                            $promotion = Promotion::query()->find((int) $promotionId);

                            if ($promotion === null) {
                                return new HtmlString(
                                    '<p class="text-sm text-gray-500 dark:text-gray-400">—</p>',
                                );
                            }

                            $display = app(ReceiptRefundTotalCalculatorInterface::class)
                                ->estimateRefundTotalDisplayForDraft($promotion, $lines);

                            return new HtmlString(
                                '<p class="text-2xl font-semibold tabular-nums tracking-tight text-gray-950 dark:text-white">'
                                .e($display)
                                .'</p>',
                            );
                        }),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function productOptionsForPromotion(mixed $promotionId): array
    {
        if (blank($promotionId)) {
            return [];
        }

        $promotion = Promotion::query()
            ->with(['products' => fn ($q) => $q->where('products.active', true)->orderBy('products.name')])
            ->find((int) $promotionId);

        if ($promotion === null) {
            return [];
        }

        return $promotion->products->mapWithKeys(
            fn ($p): array => [(int) $p->getKey() => (string) $p->name],
        )->all();
    }
}
