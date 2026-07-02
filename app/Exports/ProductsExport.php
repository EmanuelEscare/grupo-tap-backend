<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Product>  $products
     */
    public function __construct(private readonly Collection $products)
    {
    }

    /**
     * @return Collection<int, Product>
     */
    public function collection(): Collection
    {
        return $this->products;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Código de producto',
            'Nombre del producto',
            'Precio',
            'Fecha de creación',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function map($row): array
    {
        return [
            (string) $row->code,
            (string) $row->name,
            number_format((float) $row->price, 2, '.', ''),
            (string) $row->created_at?->format('d/m/Y H:i'),
        ];
    }
}
