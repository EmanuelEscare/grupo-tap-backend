<?php

namespace App\Exports;

use App\Models\Profile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProfilesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Profile>  $profiles
     */
    public function __construct(private readonly Collection $profiles) {}

    /**
     * @return Collection<int, Profile>
     */
    public function collection(): Collection
    {
        return $this->profiles;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Código de perfil',
            'Nombre',
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
            (string) $row->created_at?->format('d/m/Y H:i'),
        ];
    }
}
