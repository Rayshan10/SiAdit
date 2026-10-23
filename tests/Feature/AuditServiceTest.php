<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditResult;
use App\Models\DataMakam;
use App\Models\Tpu;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tpu $tpu;

    private AuditResult $hasil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tpu = Tpu::create([
            'nama' => 'TPU Pandu',
            'kode' => 'TPU-PANDU',
        ]);

        /*
         | Skenario lengkap, masing-masing kategori audit terwakili:
         |
         | pusat P1 AHMAD S 2020     -> match_full    (A)
         | pusat P2 BUDI S 2019      -> tahun_beda    (B tahunnya 2018)
         | pusat P3 CICI   2021      -> tahun_beda    (D tahunnya 2022)
         | pusat P4 CICI   2021(dup) -> pusat_tidak_ada
         | pusat P5 AHMAD SA 2020    -> fuzzy_match   (C "AHMAD SAA", jarak 1)
         | pusat P6 DUDI   2020      -> pusat_tidak_ada
         | cabang E ZAKI 2020        -> cabang_tidak_ada
         */
        $pusat = [
            ['AHMAD S', '2020-05-01', 'AHMAD S-2020'],
            ['BUDI S', '2019-06-14', 'BUDI S-2019'],
            ['CICI', '2021-07-20', 'CICI-2021'],
            ['CICI', '2021-07-20', 'CICI-2021'],
            ['AHMAD SA', '2020-08-02', 'AHMAD SA-2020'],
            ['DUDI', '2020-03-30', 'DUDI-2020'],
        ];

        $cabang = [
            ['AHMAD S', '2020-05-01', 'AHMAD S-2020'],
            ['BUDI S', '2018-06-14', 'BUDI S-2018'],
            ['AHMAD SAA', '2020-08-02', 'AHMAD SAA-2020'],
            ['CICI', '2022-07-20', 'CICI-2022'],
            ['ZAKI', '2020-11-11', 'ZAKI-2020'],
        ];

        foreach ($pusat as [$nama, $tanggal, $idMatch]) {
            DataMakam::create([
                'tpu_id' => $this->tpu->id,
                'import_id' => 1,
                'sumber' => 'pusat',
                'nama' => $nama,
                'tanggal_raw' => $tanggal,
                'nama_clean' => $nama,
                'tanggal_clean' => $tanggal,
                'id_match' => $idMatch,
            ]);
        }

        foreach ($cabang as [$nama, $tanggal, $idMatch]) {
            DataMakam::create([
                'tpu_id' => $this->tpu->id,
                'import_id' => 2,
                'sumber' => 'cabang',
                'nama' => $nama,
                'tanggal_raw' => $tanggal,
                'nama_clean' => $nama,
                'tanggal_clean' => $tanggal,
                'id_match' => $idMatch,
            ]);
        }

        $this->hasil = app(AuditService::class)->generate($this->tpu->id);
    }

    /** @test */
    public function hasil_ringkasan_sesuai_skenario(): void
    {
        $this->assertSame(1, $this->hasil->total_match);
        $this->assertSame(2, $this->hasil->total_tahun_beda);
        $this->assertSame(1, $this->hasil->total_fuzzy_match);
        $this->assertSame(2, $this->hasil->total_pusat_tidak_ada);
        $this->assertSame(1, $this->hasil->total_cabang_tidak_ada);
        $this->assertSame(2, $this->hasil->total_duplikat_pusat);
        $this->assertSame(0, $this->hasil->total_duplikat_cabang);
    }

    /** @test */
    public function jumlah_baris_per_status_cocok_dengan_ringkasan(): void
    {
        foreach (
            [
                'match_full'       => 1,
                'tahun_beda'       => 2,
                'fuzzy_match'      => 1,
                'pusat_tidak_ada'  => 2,
                'cabang_tidak_ada' => 1,
                'duplikat_pusat'   => 2,
                'duplikat_cabang'  => 0,
            ] as $status => $jumlah
        ) {
            $this->assertSame(
                $jumlah,
                Audit::where('audit_result_id', $this->hasil->id)->where('status', $status)->count(),
                "Jumlah baris status '{$status}' tidak sesuai."
            );
        }
    }

    /** @test */
    public function baris_fuzzy_menyimpan_skor_kemiripan_dan_jarak(): void
    {
        $fuzzy = Audit::where('audit_result_id', $this->hasil->id)
            ->where('status', 'fuzzy_match')
            ->first();

        $this->assertNotNull($fuzzy);
        $this->assertNotNull($fuzzy->dataMakam);
        $this->assertSame('AHMAD SA', $fuzzy->dataMakam->nama_clean);

        // "AHMAD SA" vs "AHMAD SAA": jarak Levenshtein 1
        $this->assertSame(1, $fuzzy->levenshtein_distance);
        $this->assertGreaterThanOrEqual(90, $fuzzy->similarity_score);

        // Dipasangkan dengan cabang "AHMAD SAA"
        $this->assertNotNull($fuzzy->matchedData);
        $this->assertSame('AHMAD SAA', $fuzzy->matchedData->nama_clean);
    }

    /** @test */
    public function menjalankan_ulang_audit_mereset_hasil_lama(): void
    {
        $idLama = $this->hasil->id;

        app(AuditService::class)->generate($this->tpu->id);

        $this->assertDatabaseMissing('audit_results', ['id' => $idLama]);
        $this->assertDatabaseCount('audit_results', 1);
        $this->assertDatabaseCount('audits', 9);
    }
}
