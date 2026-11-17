<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RekapExport;
use App\Http\Controllers\Controller;
use App\Pemetaan\Backup;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnduhController extends Controller
{
    public function rekap(RekapExport $rekap): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'rekap');
        $rekap->simpan($path);

        return response()->download($path, 'rekap-pemetaan-'.now()->format('Ymd-Hi').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    public function backup(Backup $backup): BinaryFileResponse
    {
        $path = $backup->buat();

        return response()->download($path, 'pemetaan-backup-'.now()->format('Ymd-Hi').'.sqlite', [
            'Content-Type' => 'application/vnd.sqlite3',
        ]);
    }
}
