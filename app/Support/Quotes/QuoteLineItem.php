<?php

namespace App\Support\Quotes;

class QuoteLineItem
{
    public const KIND_LABOUR = 'labour';

    public const KIND_MATERIALS = 'materials';

    public const KIND_CALLOUT = 'callout';

    public const KIND_TRANSPORT = 'transport';

    public const KIND_INSPECTION = 'inspection';

    public const KIND_PERMIT = 'permit';

    public const KIND_OTHER = 'other';

    /**
     * @return list<array{kind: string, label: string, quantity: float|int, unit: string, unit_price: float|int}>
     */
    public static function defaultRows(): array
    {
        return [
            self::normalizeRow(['kind' => self::KIND_LABOUR, 'unit_price' => 0]),
            self::normalizeRow(['kind' => self::KIND_MATERIALS, 'label' => '', 'quantity' => 0, 'unit_price' => 0]),
        ];
    }

    /**
     * @return list<array{kind: string, label: string}>
     */
    public static function extraChargeOptions(): array
    {
        return [
            ['kind' => self::KIND_CALLOUT, 'label' => self::labelForKind(self::KIND_CALLOUT)],
            ['kind' => self::KIND_TRANSPORT, 'label' => self::labelForKind(self::KIND_TRANSPORT)],
            ['kind' => self::KIND_INSPECTION, 'label' => self::labelForKind(self::KIND_INSPECTION)],
            ['kind' => self::KIND_PERMIT, 'label' => self::labelForKind(self::KIND_PERMIT)],
            ['kind' => self::KIND_OTHER, 'label' => self::labelForKind(self::KIND_OTHER)],
        ];
    }

    public static function labelForKind(string $kind): string
    {
        return match ($kind) {
            self::KIND_LABOUR => 'Labour',
            self::KIND_MATERIALS => 'Materials / tools',
            self::KIND_CALLOUT => 'Callout fee',
            self::KIND_TRANSPORT => 'Transport / logistics fee',
            self::KIND_INSPECTION => 'Site inspection / consultation fee',
            self::KIND_PERMIT => 'Permit / approval fees',
            self::KIND_OTHER => 'Other',
            default => 'Charge',
        };
    }

    public static function isMaterials(string $kind): bool
    {
        return $kind === self::KIND_MATERIALS;
    }

    public static function isCoreKind(string $kind): bool
    {
        return in_array($kind, [self::KIND_LABOUR, self::KIND_MATERIALS], true);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{kind: string, label: string, quantity: float, unit: string, unit_price: float}
     */
    public static function normalizeRow(array $row): array
    {
        $kind = (string) ($row['kind'] ?? '');
        if ($kind === '') {
            $kind = self::KIND_OTHER;
            $label = trim((string) ($row['description'] ?? $row['label'] ?? 'Item'));
        } else {
            $label = trim((string) ($row['label'] ?? self::labelForKind($kind)));
            if ($kind === self::KIND_OTHER && blank($row['label'] ?? null) && filled($row['description'] ?? null)) {
                $label = trim((string) $row['description']);
            }
        }

        if ($label === '' && ! self::isMaterials($kind)) {
            $label = self::labelForKind($kind);
        }

        $quantity = self::isMaterials($kind)
            ? self::materialQuantity($row)
            : 1.0;

        $unit = self::isMaterials($kind) ? 'unit' : 'fee';

        return [
            'kind' => $kind,
            'label' => $label,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => max(0, (float) ($row['unit_price'] ?? 0)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{kind: string, label: string, quantity: float, unit: string, unit_price: float}>
     */
    public static function normalizeCollection(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => self::normalizeRow(is_array($row) ? $row : []))
            ->filter(function (array $row): bool {
                if ($row['kind'] === self::KIND_LABOUR) {
                    return true;
                }

                if ($row['kind'] === self::KIND_MATERIALS) {
                    return $row['unit_price'] > 0
                        && $row['quantity'] > 0
                        && filled(trim($row['label']));
                }

                return $row['unit_price'] > 0;
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{kind: string, label: string, quantity: float, unit: string, unit_price: float}>
     */
    public static function rowsForEditor(array $rows): array
    {
        if ($rows === []) {
            return self::defaultRows();
        }

        $normalized = collect($rows)
            ->map(fn ($row) => self::normalizeRow(is_array($row) ? $row : []));

        $hasKinds = $normalized->contains(fn ($row) => self::isCoreKind($row['kind'])
            || in_array($row['kind'], [
                self::KIND_CALLOUT,
                self::KIND_TRANSPORT,
                self::KIND_INSPECTION,
                self::KIND_PERMIT,
            ], true));

        if (! $hasKinds) {
            $legacy = $normalized->values();
            $mapped = collect();

            if ($legacy->isNotEmpty()) {
                $first = $legacy->shift();
                $first['kind'] = self::KIND_LABOUR;
                $first['label'] = self::labelForKind(self::KIND_LABOUR);
                $mapped->push(self::normalizeRow($first));
            }

            if ($legacy->isNotEmpty()) {
                $second = $legacy->shift();
                $second['kind'] = self::KIND_MATERIALS;
                $second['label'] = self::labelForKind(self::KIND_MATERIALS);
                $mapped->push(self::normalizeRow($second));
            }

            foreach ($legacy as $row) {
                $row['kind'] = self::KIND_OTHER;
                $mapped->push(self::normalizeRow($row));
            }

            $normalized = $mapped;
        }

        $labour = $normalized->first(fn ($row) => $row['kind'] === self::KIND_LABOUR)
            ?? self::normalizeRow(['kind' => self::KIND_LABOUR]);

        $materials = $normalized
            ->filter(fn ($row) => $row['kind'] === self::KIND_MATERIALS)
            ->values();

        if ($materials->isEmpty()) {
            $materials->push(self::normalizeRow([
                'kind' => self::KIND_MATERIALS,
                'label' => '',
                'quantity' => 0,
                'unit_price' => 0,
            ]));
        }

        $extras = $normalized
            ->filter(fn ($row) => ! self::isCoreKind($row['kind']))
            ->values();

        return [
            $labour,
            ...$materials->all(),
            ...$extras->all(),
        ];
    }

    /**
     * @param  array{kind: string, quantity?: float, unit_price?: float}  $row
     */
    public static function lineTotalNaira(array $row): float
    {
        $normalized = self::normalizeRow($row);

        return $normalized['quantity'] * $normalized['unit_price'];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function subtotalNaira(array $rows): float
    {
        return collect($rows)->sum(fn ($row) => self::lineTotalNaira($row));
    }

    /**
     * @param  array{kind: string, label: string, quantity: float, unit: string, unit_price: float}  $row
     */
    public static function displayLabel(array $row): string
    {
        if ($row['kind'] === self::KIND_MATERIALS && filled(trim($row['label']))) {
            return $row['quantity'] > 0
                ? $row['label'].' · '.$row['quantity'].' unit'.($row['quantity'] == 1 ? '' : 's')
                : $row['label'];
        }

        return $row['label'];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function materialQuantity(array $row): float
    {
        $quantity = max(0, (float) ($row['quantity'] ?? 0));

        if ($quantity <= 0 && is_numeric($row['unit'] ?? null)) {
            $quantity = max(0, (float) $row['unit']);
        }

        return $quantity;
    }
}
