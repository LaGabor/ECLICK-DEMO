<?php

declare(strict_types=1);

namespace App\Filament\Resources\Promotions\RelationManagers;

use App\Domain\Promotions\PromotionProductRefundKind;
use App\Models\Product;
use Closure;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament.promotions.products_relation');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (Product $record): string => $record->name.' (SKU: '.$record->sku.')')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Product'))
                    ->searchable(['name', 'sku'])
                    ->formatStateUsing(function (mixed $state, Model $record): string {
                        return $record instanceof Product
                            ? $record->name.' (SKU: '.$record->sku.')'
                            : (string) $state;
                    }),
                TextColumn::make('price')
                    ->label(__('List price'))
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('active')
                    ->label(__('Active in catalog'))
                    ->boolean(),
                TextColumn::make('pivot.refund_value')
                    ->label(__('Refund value'))
                    ->formatStateUsing(function ($state, Model $record): string {
                        if ($state === null || $state === '') {
                            return '';
                        }
                        $type = (string) ($record->getRelationValue('pivot')?->refund_type ?? '');

                        $suffix = $type === PromotionProductRefundKind::PercentOfLineSubtotal->value
                            ? '%'
                            : ' '.__('filament.promotions.currency_suffix');

                        return rtrim(rtrim((string) $state, '0'), '.').$suffix;
                    }),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label(__('filament.promotions.attach_product'))
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'sku'])
                    ->schema(function (AttachAction $action): array {
                        $recordSelect = $action->getRecordSelect();
                        $recordSelect->live();

                        return [
                            $recordSelect,
                            self::refundTypeSelect(),
                            self::makeRefundValueInput(forAttach: true),
                        ];
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->color('warning')
                    ->outlined()
                    ->schema(function (EditAction $action): array {
                        /** @var Product $product */
                        $product = $action->getRecord();

                        return [
                            self::refundTypeSelect(),
                            self::makeRefundValueInput(
                                forAttach: false,
                                editProductMaxPrice: (float) $product->getAttribute('price'),
                            ),
                        ];
                    }),
                DetachAction::make()
                    ->color('danger')
                    ->outlined(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    private static function refundTypeSelect(): Select
    {
        return Select::make('refund_type')
            ->label(__('Refund calculation'))
            ->options(collect(PromotionProductRefundKind::cases())->mapWithKeys(
                fn (PromotionProductRefundKind $kind): array => [$kind->value => $kind->getLabel()],
            ))
            ->default(PromotionProductRefundKind::FixedAmountPerUnit->value)
            ->selectablePlaceholder(false)
            ->required()
            ->native(false)
            ->live()
            ->afterStateUpdated(function (Select $component): void {
                $refundValueField = $component->getContainer()->getComponent(
                    fn (mixed $c): bool => $c instanceof TextInput && $c->getName() === 'refund_value',
                );

                if (! $refundValueField instanceof TextInput) {
                    return;
                }

                $path = $refundValueField->getStatePath();
                if (filled($path)) {
                    $component->getLivewire()->resetErrorBag($path);
                }
            });
    }

    private static function makeRefundValueInput(bool $forAttach, ?float $editProductMaxPrice = null): TextInput
    {
        return TextInput::make('refund_value')
            ->label(__('filament.promotions.configured_refund_value'))
            ->numeric()
            ->required()
            ->live(onBlur: true)
            ->suffix(function (Get $get): string {
                $kind = PromotionProductRefundKind::tryFrom((string) ($get('refund_type') ?? ''));

                return $kind === PromotionProductRefundKind::PercentOfLineSubtotal
                    ? '%'
                    : ' '.__('filament.promotions.currency_suffix');
            })
            ->helperText(function (Get $get): ?string {
                $kind = PromotionProductRefundKind::tryFrom((string) ($get('refund_type') ?? ''));
                if ($kind === PromotionProductRefundKind::PercentOfLineSubtotal) {
                    return __('filament.promotions.refund_value.helper_percent');
                }
                if ($kind === PromotionProductRefundKind::FixedAmountPerUnit) {
                    return __('filament.promotions.refund_value.helper_fixed');
                }

                return null;
            })
            ->rules([
                fn (Get $get): Closure => self::refundValueRule($get, $forAttach, $editProductMaxPrice),
            ]);
    }

    private static function refundValueRule(Get $get, bool $forAttach, ?float $editProductMaxPrice): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get, $forAttach, $editProductMaxPrice): void {
            if (! is_numeric($value)) {
                return;
            }

            $num = (float) $value;
            if ($num <= 0) {
                $fail(__('filament.promotions.refund_value.must_be_positive'));

                return;
            }

            $kind = PromotionProductRefundKind::tryFrom((string) ($get('refund_type') ?? ''));
            if ($kind === null) {
                return;
            }

            if ($kind === PromotionProductRefundKind::PercentOfLineSubtotal) {
                if ($num > 100) {
                    $fail(__('filament.promotions.refund_value.percent_not_above_100'));
                }

                return;
            }

            $maxPrice = $editProductMaxPrice;
            if ($forAttach) {
                $productId = $get('recordId');
                if (blank($productId)) {
                    return;
                }
                $product = Product::query()->find($productId);
                $maxPrice = $product !== null ? (float) $product->getAttribute('price') : null;
            }

            if ($maxPrice === null || $maxPrice <= 0) {
                $fail(__('filament.promotions.refund_value.product_price_missing'));

                return;
            }

            if ($num > $maxPrice) {
                $fail(__('filament.promotions.refund_value.fixed_above_price', ['max' => $maxPrice]));
            }
        };
    }
}
