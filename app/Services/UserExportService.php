<?php

namespace App\Services;

use App\Exports\UsersExport;
use App\Models\User;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserExportService
{
    /**
     * @param  Collection<int, User>  $users
     */
    public function pdf(Collection $users): Responsable
    {
        return Pdf::view('pdf.users', [
            'users' => $users,
        ])
            ->driver('dompdf')
            ->format('a4')
            ->download('users.pdf');
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function excel(Collection $users): BinaryFileResponse
    {
        return Excel::download(new UsersExport($users), 'users.xlsx');
    }
}
