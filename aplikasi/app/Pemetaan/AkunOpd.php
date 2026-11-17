<?php

namespace App\Pemetaan;

use App\Enums\Role;
use App\Models\Opd;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class AkunOpd
{
    /** Tanpa karakter yang mudah tertukar: 0 O o 1 l I. */
    private const ALFABET = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private ?array $singkatan = null;

    /** @return Collection<int, array{opd: string, username: string, password: string}> */
    public function buatAkun(): Collection
    {
        return DB::transaction(fn () => Opd::query()->doesntHave('user')->orderBy('urutan')->orderBy('id')->get()
            ->map(fn (Opd $opd) => $this->buatUntuk($opd))
            ->values());
    }

    /** @return array{opd: string, username: string, password: string} */
    public function buatUntuk(Opd $opd): array
    {
        $username = $this->usernameUnik($opd);
        $password = $this->kataSandi();

        if (! $opd->singkatan) {
            $opd->update(['singkatan' => $username]);
        }
        User::query()->create([
            'name' => $opd->nama,
            'username' => $username,
            'password' => $password,
            'role' => Role::Opd,
            'opd_id' => $opd->id,
        ]);

        return ['opd' => $opd->nama, 'username' => $username, 'password' => $password];
    }

    public function username(Opd $opd): string
    {
        if ($opd->singkatan) {
            return $opd->singkatan;
        }
        $peta = $this->singkatan ??= require database_path('data/singkatan_opd.php');
        if (isset($peta[$opd->nama])) {
            return $peta[$opd->nama];
        }
        if (Str::startsWith($opd->nama, 'KECAMATAN ')) {
            return Str::limit('kec-'.Str::slug(Str::after($opd->nama, 'KECAMATAN ')), 30, '');
        }

        return rtrim(Str::limit(Str::slug($opd->nama), 30, ''), '-');
    }

    public function resetPassword(User $user): string
    {
        $password = $this->kataSandi();
        $user->update(['password' => $password]);

        return $password;
    }

    /** @param  Collection<int, array{opd: string, username: string, password: string}>  $akun */
    public function ekspor(Collection $akun, string $path): void
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('Akun OPD');
        $sheet->fromArray(['OPD', 'Username', 'Kata Sandi Awal'], null, 'A1');
        $sheet->fromArray($akun->map(fn ($a) => [$a['opd'], $a['username'], $a['password']])->all(), null, 'A2');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        foreach (['A' => 70, 'B' => 22, 'C' => 18] as $kol => $lebar) {
            $sheet->getColumnDimension($kol)->setWidth($lebar);
        }
        $sheet->getStyle('C:C')->getNumberFormat()->setFormatCode('@');
        (new Xlsx($book))->save($path);
    }

    private function usernameUnik(Opd $opd): string
    {
        $dasar = $this->username($opd);
        $kandidat = $dasar;
        for ($n = 2; User::query()->where('username', $kandidat)->exists(); $n++) {
            $kandidat = "{$dasar}-{$n}";
        }

        return $kandidat;
    }

    private function kataSandi(): string
    {
        $hasil = '';
        for ($i = 0; $i < 10; $i++) {
            $hasil .= self::ALFABET[random_int(0, strlen(self::ALFABET) - 1)];
        }

        return $hasil;
    }
}
