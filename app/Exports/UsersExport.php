<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, User>  $users
     */
    public function __construct(private readonly Collection $users)
    {
    }

    /**
     * @return Collection<int, User>
     */
    public function collection(): Collection
    {
        return $this->users;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Código de usuario',
            'Usuario',
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
            (string) $row->email,
            (string) $row->name,
            (string) $row->created_at?->format('d/m/Y H:i'),
        ];
    }
}
