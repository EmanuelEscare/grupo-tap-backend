<?php

namespace App\Services;

use App\Exports\ProfilesExport;
use App\Models\Profile;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileExportService
{
    /**
     * @param  Collection<int, Profile>  $profiles
     */
    public function pdf(Collection $profiles): Responsable
    {
        return Pdf::view('pdf.profiles', [
            'profiles' => $profiles,
        ])
            ->driver('dompdf')
            ->format('a4')
            ->download('profiles.pdf');
    }

    /**
     * @param  Collection<int, Profile>  $profiles
     */
    public function excel(Collection $profiles): BinaryFileResponse
    {
        return Excel::download(new ProfilesExport($profiles), 'profiles.xlsx');
    }
}
