<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the `bangkit` connection's lookup tables.
 *
 * Data sources:
 *  - sekre_pegawai_duk: verbatim from old sql/sekre_pegawai_duk.sql (the only
 *    real schema/data dump that survived; 36 employees, DUK period 2023/03).
 *  - sekre_pegawai_kekuatan: DERIVED from sekre_pegawai_duk (Id + nama) — the
 *    legacy app resolves employee names via nama_pergawai() against THIS table,
 *    but no dump of it exists; the DUK roster is the only name source we have,
 *    so we mirror its ids/names so lookups resolve.
 *  - sekre_bangkit_role: the 6 roles are unambiguous from legacy Auth.php's
 *    redirect map (role 1..6 → superadmin/kadin/sekdin/kasubag/bendahara/user).
 *
 * NOT seeded (no data source): katkit_bidang and the 5 sekre_bangkit_master_*
 * tables are DB-driven dropdowns with no surviving dump — they must be
 * populated from live data later.
 */
class BangkitSeeder extends Seeder
{
    public function run(): void
    {
        $conn = 'bangkit';

        DB::connection($conn)->table('sekre_bangkit_role')->insert([
            ['Id' => 1, 'role_name' => 'Superadmin'],
            ['Id' => 2, 'role_name' => 'Kadin'],
            ['Id' => 3, 'role_name' => 'Sekdin'],
            ['Id' => 4, 'role_name' => 'Kasubag'],
            ['Id' => 5, 'role_name' => 'Bendahara'],
            ['Id' => 6, 'role_name' => 'User'],
        ]);

        $pegawai = [
            [1, 'DIAH SUPARTININGTIAS, SH,M.Kn'],
            [2, 'LAILA FIRDHOUS ARIBAWA, S.STP, M.Si.'],
            [3, 'BUDI SETYO RACHMAT, SE'],
            [4, 'YOHANES FRANSISCUS YUNIAR RONNY W, ST, MT'],
            [5, 'RISTA AMELIA ADHI, S.STP., M.M.'],
            [6, 'GITA ALFA ARSYADHA, ST'],
            [7, 'YULIA ADITYORINI, S.IP'],
            [8, 'NANY MARLINA, SE.M.Si,Akt'],
            [9, 'MILATINA, S.Kom, M.Kom'],
            [10, 'SITI ARIAWATI, SE,MM'],
            [11, 'DYAH ERNAWATI, S.Sos'],
            [12, 'PRAMASTUTI MOEGIYONO, S.IP'],
            [13, 'AHMAD ROSIDIN, S.IP'],
            [14, 'ALEXANDER GANESWARA WISNU PUTRO, S.Kom'],
            [15, 'HANGGORO LARAS SAKTI, ST'],
            [16, 'DHAMAYANTIE SAVITRI, SS'],
            [17, 'TUTI INAYATI, SE'],
            [18, 'NARITA BUDHIARTY, S.STP, M.Si'],
            [19, 'IKA DENNY BUDIYANTI, S.E'],
            [20, 'SUZAN MAHARANI, S.T'],
            [21, 'RINI SETIJOWATI SOEHARDJO'],
            [22, 'MADE AGUNG LISTIAWATI, A.Md'],
            [23, 'NOVI TRI SURYANI, S.Kom'],
            [24, 'HERLAMBANG LUTFI PRAKOSO, S.STP'],
            [25, 'FARIZ ARDYANTO, S.STP., M.M.'],
            [26, 'HARYANTI'],
            [27, 'ERLINA PINKY MANDASARI, S.STP'],
            [28, 'RICO PAULUS SIBUEA, S.Tr.IP'],
            [29, 'DODY FERY WIBOWO'],
            [30, 'RIONO UTOMO'],
            [31, 'SANTOSO'],
            [32, 'YOHANES ADHI CHRISTIAN, A.Md'],
            [33, 'JUPRI'],
            [34, 'ARIS DARYANTO'],
            [35, 'NAELU SHULHAL MAJID, A.Md.Ak'],
            [36, 'ISMOYO HADI WIBOWO, S.Kom'],
        ];

        $kekuatanRows = array_map(fn ($p) => ['Id' => $p[0], 'nama' => $p[1]], $pegawai);
        DB::connection($conn)->table('sekre_pegawai_kekuatan')->insert($kekuatanRows);

        // sekre_pegawai_duk — verbatim INSERT from the surviving dump (kept as
        // raw SQL to preserve exact values incl. urutan_duk and NIP strings).
        $dukColumns = '`Id`,`urutan_duk`,`nama`,`nip`,`gol`,`tmt`,`gol_cpns`,`tmt_cpns`,`jabatan`,`eselon`,`masa_kerja_tahun`,`masa_kerja_bulan`,`pendidikan`,`tahun_periode_data`,`bulan_periode_data`';

        DB::connection($conn)->unprepared(
            "INSERT INTO `sekre_pegawai_duk` ($dukColumns) VALUES "
            .'(1,1,\'DIAH SUPARTININGTIAS, SH,M.Kn\',\'1.96710231994012E+17\',\'IV/b\',\'2022-01-04\',\'III/a\',\'1994-01-01\',\'Kepala\',\'II.b\',30,5,\'S-2 MAGISTER KENOTARIATAN\',\'2023\',\'3\'),'
            .'(2,102,\'LAILA FIRDHOUS ARIBAWA, S.STP, M.Si.\',\'1.97901271998101E+17\',\'IV/b\',\'2023-01-04\',\'II/a\',\'2002-01-10\',\'Sekretaris\',\'III.a\',21,8,\'S-2 MANAJEMEN\',\'2023\',\'3\'),'
            .'(3,103,\'BUDI SETYO RACHMAT, SE\',\'1.96712051998031E+17\',\'IV/a\',\'2013-01-04\',\'III/a\',\'1998-01-03\',\'Kepala Bidang Penyelenggaraan Layanan Perizinan III\',\'III.b\',29,6,\'S-1 EKONOMI MANAJEMEN\',\'2023\',\'3\'),'
            .'(4,104,\'YOHANES FRANSISCUS YUNIAR RONNY W, ST, MT\',\'1.97806162009011E+17\',\'IV/a\',\'2020-01-04\',\'III/a\',\'2009-01-01\',\'Kepala Bidang Sistem Informasi dan Monitoring dan Evaluasi Perijinan\',\'III.b\',20,14,\'S-2 MAGISTER TEKNIK\',\'2023\',\'3\'),'
            .'(5,105,\'RISTA AMELIA ADHI, S.STP., M.M.\',\'1.98111192000122E+17\',\'IV/a\',\'2016-01-10\',\'II/a\',\'2004-01-10\',\'Kepala Bidang Penyelenggaraan Layanan Perizinan I\',\'III.b\',19,8,\'S-2 MAGISTER MANAJEMEN\',\'2023\',\'3\'),'
            .'(6,106,\'GITA ALFA ARSYADHA, ST\',\'1.97906212010011E+17\',\'III/d\',\'2022-01-04\',\'III/a\',\'2010-01-01\',\'Kepala Bidang Potensi dan Promosi Penanaman Modal\',\'III.b\',14,5,\'S-1 TEKNIK SIPIL PLANOLOGI\',\'2023\',\'3\'),'
            .'(7,107,\'YULIA ADITYORINI, S.IP\',\'1.98307232010012E+17\',\'III/d\',\'2022-01-04\',\'III/a\',\'2010-01-01\',\'Kepala Bidang Penyelenggaraan Layanan Perizinan II\',\'III.b\',14,5,\'S-1 ILMU PEMERINTAHAN\',\'2023\',\'3\'),'
            .'(8,208,\'NANY MARLINA, SE.M.Si,Akt\',\'1.97405132002122E+17\',\'IV/a\',\'2020-01-04\',\'III/a\',\'2002-01-12\',\'Kepala Subbagian Keuangan dan Barang Milik Daerah\',\'IV.a\',21,6,\'S-2 MAGISTER AKUNTANSI\',\'2023\',\'3\'),'
            .'(9,209,\'MILATINA, S.Kom, M.Kom\',\'1.98510152006042E+17\',\'III/c\',\'2020-01-10\',\'II/a\',\'2006-01-04\',\'Kepala Sub Bagian Umum dan Kepegawaian\',\'IV.a\',13,16,\'S-2 TEKNIK INFORMATIKA\',\'2023\',\'3\'),'
            .'(10,210,\'SITI ARIAWATI, SE,MM\',\'1.97301241998032E+17\',\'IV/a\',\'2015-01-10\',\'III/a\',\'1998-01-03\',\'Analis Kebijakan Muda\',\'JF\',26,18,\'S-2 MAGISTER MANAJEMEN\',\'2023\',\'3\'),'
            .'(11,211,\'DYAH ERNAWATI, S.Sos\',\'1.96609141987022E+17\',\'III/d\',\'2005-01-04\',\'II/a\',\'1987-01-02\',\'Perencana Ahli Muda\',\'JF\',32,4,\'S-1/STRATA SATU\',\'2023\',\'3\'),'
            .'(12,212,\'PRAMASTUTI MOEGIYONO, S.IP\',\'1.96708181993012E+17\',\'III/d\',\'2013-01-04\',\'II/b\',\'1993-01-01\',\'Analis Kebijakan Muda\',\'JF\',29,5,\'S-1 SOSIAL POLITIK FILSAFAT\',\'2023\',\'3\'),'
            .'(13,213,\'AHMAD ROSIDIN, S.IP\',\'1.97201041992031E+17\',\'III/d\',\'2018-01-04\',\'II/a\',\'1992-01-03\',\'Analis Kebijakan Muda\',\'JF\',27,3,\'S-1 ILMU PEMERINTAHAN\',\'2023\',\'3\'),'
            .'(14,214,\'ALEXANDER GANESWARA WISNU PUTRO, S.Kom\',\'1.98110132009031E+17\',\'III/d\',\'2023-01-04\',\'III/a\',\'2009-01-03\',\'Pranata Komputer Ahli MudaMuda\',\'JF\',15,3,\'S-1 TEKNIK INFORMATIKA\',\'2023\',\'3\'),'
            .'(15,215,\'HANGGORO LARAS SAKTI, ST\',\'1.98603072011011E+17\',\'III/d\',\'2019-01-04\',\'III/a\',\'2011-01-01\',\'Analis Kebijakan Muda\',\'JF\',13,5,\'S-1 TEKNIK ARSITEKTUR\',\'2023\',\'3\'),'
            .'(16,216,\'DHAMAYANTIE SAVITRI, SS\',\'1.97707242009032E+17\',\'III/c\',\'2020-01-04\',\'III/a\',\'2009-01-03\',\'Analis Kebijakan Muda\',\'JF\',15,3,\'S-1 BAHASA INGGRIS\',\'2023\',\'3\'),'
            .'(17,217,\'TUTI INAYATI, SE\',\'1.98001102010012E+17\',\'III/c\',\'2022-01-04\',\'III/a\',\'2010-01-01\',\'Analis Kebijakan Muda\',\'JF\',14,5,\'S-1 MANAJEMEN\',\'2023\',\'3\'),'
            .'(18,218,\'NARITA BUDHIARTY, S.STP, M.Si\',\'1.99401172015072E+17\',\'III/c\',\'2023-01-04\',\'III/a\',\'2015-01-07\',\'Analis Kebijakan Muda\',\'JF\',8,11,\'D-IV POLITIK PEMERINTAHAN\',\'2023\',\'3\'),'
            .'(19,3019,\'IKA DENNY BUDIYANTI, S.E\',\'1.98311272010012E+17\',\'III/c\',\'2023-01-04\',\'II/c\',\'2010-01-01\',\'ANALIS PERENCANAAN EVALUASI PELAPORAN\',\'Pelaksana\',12,5,\'S-1 ILMU EKONOMI\',\'2023\',\'3\'),'
            .'(20,3020,\'SUZAN MAHARANI, S.T\',\'1.98304242014032E+17\',\'III/c\',\'2022-01-10\',\'III/a\',\'2014-01-03\',\'ANALIS DOKUMEN PERIZINAN\',\'Pelaksana\',10,18,\'S-1 TEKNIK INDUSTRI\',\'2023\',\'3\'),'
            .'(21,4021,\'RINI SETIJOWATI SOEHARDJO\',\'1.96811231990032E+17\',\'III/b\',\'2010-01-04\',\'II/a\',\'1990-01-03\',\'ANALIS DOKUMEN PERIZINAN\',\'Pelaksana\',29,3,\'SLTA UMUM\',\'2023\',\'3\'),'
            .'(22,4022,\'MADE AGUNG LISTIAWATI, A.Md\',\'1.97903292010012E+17\',\'III/b\',\'2022-01-04\',\'II/c\',\'2010-01-01\',\'BENDAHARA\',\'Pelaksana\',12,5,\'D-III PERPAJAKAN\',\'2023\',\'3\'),'
            .'(23,4023,\'NOVI TRI SURYANI, S.Kom\',\'1.97811102011012E+17\',\'III/b\',\'2023-01-10\',\'II/c\',\'2011-01-01\',\'Pranata Komputer Ahli PertamaPertama\',\'JF\',11,22,\'S-1 SISTEM INFORMASI\',\'2023\',\'3\'),'
            .'(24,4024,\'HERLAMBANG LUTFI PRAKOSO, S.STP\',\'1.99307222017081E+17\',\'III/b\',\'2021-01-10\',\'III/a\',\'2017-01-08\',\'ANALIS PERIZINAN\',\'Pelaksana\',6,10,\'D-IV PEMBANGUNAN DAN PEMBERDAYAAN\',\'2023\',\'3\'),'
            .'(25,4025,\'FARIZ ARDYANTO, S.STP., M.M.\',\'1.99507012018081E+17\',\'III/b\',\'2023-01-04\',\'III/a\',\'2018-01-08\',\'ANALIS PERIZINAN\',\'Pelaksana\',5,10,\'S-2 MAGISTER MANAGEMEN\',\'2023\',\'3\'),'
            .'(26,5026,\'HARYANTI\',\'1.96801182007012E+17\',\'III/a\',\'2023-01-04\',\'II/a\',\'2007-01-01\',\'PENGADMINISTRASI PERIZINAN\',\'Pelaksana\',29,2,\'SMA A.3/IPS\',\'2023\',\'3\'),'
            .'(27,5027,\'ERLINA PINKY MANDASARI, S.STP\',\'1.99802012020082E+17\',\'III/a\',NULL,\'III/a\',\'2020-01-08\',\'PENYUSUN RENCANA KERJA DAN ANGGARAN SISTEM DAN METODA\',\'Pelaksana\',3,10,\'D-IV MANAJEMEN SUMBER DAYA APARATUR\',\'2023\',\'3\'),'
            .'(28,5028,\'RICO PAULUS SIBUEA, S.Tr.IP\',\'1.99712302022081E+17\',\'III/a\',NULL,\'III/a\',\'2022-01-08\',\'ANALIS PERIZINAN\',\'Pelaksana\',1,10,\'D-IV STUDI KEPENDUDUKAN DAN PENCATATAN SIPIL\',\'2023\',\'3\'),'
            .'(29,6029,\'DODY FERY WIBOWO\',\'1.97302072009011E+17\',\'II/d\',\'2021-01-04\',\'II/a\',\'2009-01-01\',\'PENGADMINISTRASI UMUM\',\'Pelaksana\',26,12,\'SMA\',\'2023\',\'3\'),'
            .'(30,6030,\'RIONO UTOMO\',\'1.97909112009011E+17\',\'II/d\',\'2021-01-04\',\'II/a\',\'2009-01-01\',\'PENGADMINISTRASI PERIZINAN\',\'Pelaksana\',23,10,\'SMK\',\'2023\',\'3\'),'
            .'(31,6031,\'SANTOSO\',\'1.97111152010011E+17\',\'II/d\',\'2022-01-04\',\'II/a\',\'2010-01-01\',\'PENGADMINISTRASI PERIZINAN\',\'Pelaksana\',20,14,\'SMA A.3/IPS\',\'2023\',\'3\'),'
            .'(32,6032,\'YOHANES ADHI CHRISTIAN, A.Md\',\'1.98102232010011E+17\',\'II/d\',\'2014-01-04\',\'II/c\',\'2010-01-01\',\'Pranata Komputer Terampil\',\'JF\',17,5,\'D-III TEKNIK KOMPUTER\',\'2023\',\'3\'),'
            .'(33,7033,\'JUPRI\',\'1.96909102008011E+17\',\'II/c\',\'2023-01-04\',\'I/a\',\'2008-01-01\',\'PENGADMINISTRASI PERSURATAN\',\'Pelaksana\',21,12,\'SMA PAKET C\',\'2023\',\'3\'),'
            .'(34,7034,\'ARIS DARYANTO\',\'1.97302102007011E+17\',\'II/c\',\'2023-01-04\',\'I/c\',\'2007-01-01\',\'PENGADMINISTRASI UMUM\',\'Pelaksana\',21,10,\'SMP\',\'2023\',\'3\'),'
            .'(35,7035,\'NAELU SHULHAL MAJID, A.Md.Ak\',\'1.99904062022011E+17\',\'II/c\',NULL,\'II/c\',\'2022-01-01\',\'PENGELOLA AKUNTANSI\',\'Pelaksana\',5,5,\'D-III AKUNTANSI\',\'2023\',\'3\'),'
            .'(36,9001,\'ISMOYO HADI WIBOWO, S.Kom\',\'1.99904062022011E+17\',\'IX\',NULL,NULL,NULL,\'Pranata Komputer \',\'Pelaksana\',1,7,\'S-1 TEKNIK INFORMATIKA\',\'2023\',\'3\')'
        );
    }
}
