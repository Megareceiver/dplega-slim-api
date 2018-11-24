<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

// Routes

// LOGIN ------------
$app->post("/auth/", function (Request $request, Response $response){

    $credential = $request->getParsedBody();

    $sql = "
        SELECT 
            l.noRegistrasi,
            l.nama,
            l.jabatan,
            l.noTelp,
            l.email,
            l.username,
            l.alamat,
            l.noRt,
            l.noRw,
            l.kodeKelurahan,
            l.kodeKecamatan,
            l.kodeWilayah,
            COALESCE(l.`urlGambar`, CONCAT_WS('', 'avatar-', RIGHT(l.idData,1) ,'.jpg')) as urlGambar, 
            l.userLevel,
            l.lingkupArea,
            l.idBatasArea,
            CONCAT_WS(' ', l.`alamat`, 'RT/RW', COALESCE(l.`noRt`, '-'), '/', COALESCE(l.`noRw`, '-'), `namaKelurahan`, `namaKecamatan`, `namaWilayah`, `namaProvinsi`) as alamatLengkap
        FROM 
            dplega_910_user l
        LEFT JOIN
            dplega_100_provinsi p ON l.kodeProvinsi = p.idData
        LEFT JOIN
            dplega_101_wilayah w ON l.kodeWilayah = w.idData
        LEFT JOIN
            dplega_102_kecamatan kc ON l.kodeKecamatan = kc.idData
        LEFT JOIN
            dplega_103_kelurahan kl ON l.kodeKelurahan = kl.idData
        
        WHERE username=:username AND password=:password AND statusActive = 1";
    $stmt = $this->db->prepare($sql);

    $data = [
        ":username" => $credential["username"],
        ":password" => md5($credential["password"])
    ];

    if($stmt->execute($data)){
        $result = $stmt->fetch();
        
        if($result['nama'] != ''){
            $result['namaLembaga'] = "";
            
            if($result['userLevel'] == '1'){
                $sql  = "SELECT nama FROM dplega_000_lembaga WHERE noRegistrasi = '".$result['noRegistrasi']."'";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $res  = $stmt->fetch();

                if($res['nama'] == ''){
                    $sql  = "SELECT nama FROM dplega_000_lembaga_temp WHERE noRegistrasi = '".$result['noRegistrasi']."'";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                    $res  = $stmt->fetch();
                }

                $result['namaLembaga'] = $res['nama'];
            }
        }


       return $response->withJson($result, 200);
    }
    
    return $response->withJson(["status" => "failed", "data" => '0'], 200);
});

$app->post("/fetch/auth/", function (Request $request, Response $response){

    $credential = $request->getParsedBody();

    $sql = "
        SELECT 
            l.idData,
            l.noRegistrasi,
            l.nama,
            l.jabatan,
            l.noTelp,
            l.email,
            l.username,
            l.alamat,
            l.noRt,
            l.noRw,
            l.kodeKelurahan,
            l.kodeKecamatan,
            l.kodeWilayah,
            COALESCE(l.`urlGambar`, CONCAT_WS('', 'avatar-', RIGHT(l.idData,1) ,'.jpg')) as urlGambar, 
            l.userLevel,
            CONCAT_WS(' ', l.`alamat`, 'RT/RW', COALESCE(l.`noRt`, '-'), '/', COALESCE(l.`noRw`, '-'), `namaKelurahan`, `namaKecamatan`, `namaWilayah`, `namaProvinsi`) as alamatLengkap
        FROM 
            dplega_910_user l
        LEFT JOIN
            dplega_100_provinsi p ON l.kodeProvinsi = p.idData
        LEFT JOIN
            dplega_101_wilayah w ON l.kodeWilayah = w.idData
        LEFT JOIN
            dplega_102_kecamatan kc ON l.kodeKecamatan = kc.idData
        LEFT JOIN
            dplega_103_kelurahan kl ON l.kodeKelurahan = kl.idData
        
        WHERE username=:username";
    $stmt = $this->db->prepare($sql);

    $data = [
        ":username" => $credential["username"]
    ];

    if($stmt->execute($data)){
       $result = $stmt->fetch();
       return $response->withJson($result, 200);
    }
    
    return $response->withJson(["status" => "failed", "data" => '0'], 200);
});

$app->post("/update/password/", function (Request $request, Response $response){

    $credential = $request->getParsedBody();
    // $res = array();
    if($credential['newPassword'] == $credential['retypePassword']){
        
        // checking
        $te = "salah";
        $sql  = "SELECT username FROM dplega_910_user WHERE username = '".$credential['username']."' AND password = '".md5($credential['oldPassword'])."'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $res  = $stmt->fetch();
        
        if($res['username'] == $credential["username"]){
            
            $sql = "UPDATE dplega_910_user SET `password`=:newPassword WHERE username=:username AND `password`=:oldPassword";
            $stmt = $this->db->prepare($sql);

            $data = [
                ":username" => $credential["username"],
                ":newPassword" => md5($credential["newPassword"]),
                ":oldPassword" => md5($credential["oldPassword"])
            ];

            if($stmt->execute($data)){
                return $response->withJson(["status" => "success", "data" => "1"], 200);
            }
        }

    }
    
    return $response->withJson(["status" => "failed", "data" => $res], 200);
});

$app->post("/update/account/", function (Request $request, Response $response){

    $account = $request->getParsedBody();
   
    // checking
    $te = "salah";
    $sql  = "SELECT username FROM dplega_910_user WHERE username = '".$account['username']."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();
    
    if($res['username'] == $account["username"]){
        
        $sql = "
            UPDATE dplega_910_user 
            SET 
                `nama`=:nama, 
                `jabatan`=:jabatan, 
                `alamat`=:alamat, 
                `noRt`=:noRt, 
                `noRw`=:noRw, 
                `kodeWilayah`=:kodeWilayah, 
                `kodeKelurahan`=:kodeKelurahan, 
                `kodeKecamatan`=:kodeKecamatan, 
                `noTelp`=:noTelp, 
                `email`=:email
            WHERE username=:username";
        $stmt = $this->db->prepare($sql);

        $data = [
            ":username" => $account["username"],
            ":nama" => $account["nama"],
            ":jabatan" => $account["jabatan"],
            ":alamat" => $account["alamat"],
            ":noRt" => $account["noRt"],
            ":noRw" => $account["noRw"],
            ":kodeWilayah" => $account["kodeWilayah"],
            ":kodeKelurahan" => $account["kodeKelurahan"],
            ":kodeKecamatan" => $account["kodeKecamatan"],
            ":noTelp" => $account["noTelp"],
            ":email" => $account["email"]
        ];

        if($stmt->execute($data)){
            return $response->withJson(["status" => "success", "data" => "1"], 200);
        }
    }

    
    return $response->withJson(["status" => "failed", "data" => $res], 200);
});

// Upload
$app->post('/upload/account/avatar/', function(Request $request, Response $response, $args) {
    
    $uploadedFiles = $request->getUploadedFiles();
    $credential    = $request->getParsedBody();

    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['file'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        
        // ubah nama file dengan id buku
        $filename = sprintf('%s.%0.8s', $credential["username"].'_avatar', $extension);
        
        $directory = $this->get('settings')['avatar_directory'];
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        // simpan nama file ke database
        $sql = "UPDATE dplega_910_user SET urlGambar=:urlGambar WHERE username=:username";
        $stmt = $this->db->prepare($sql);
        $params = [
            ":username" => $credential["username"],
            ":urlGambar" => $filename
        ];
        
        if($stmt->execute($params)){
            // ambil base url dan gabungkan dengan file name untuk membentuk URL file
            // $url = $request->getUri()->getBaseUrl().$filename;
            return $response->withJson(["status" => "success", "filename" => $filename], 200);
        }
        
        return $response->withJson(["status" => "failed", "filename" => "0"], 200);
    }
});

// LIST LEMBAGA ------------
$app->post("/list/lembaga/{page}", function (Request $request, Response $response, $args){
    $page   = $args["page"];
    $offset = intval($page);
    $offset = ($offset - 1) * 25;

    $filter = $request->getParsedBody();
    $data['keyword']        = (isset($filter["keyword"]) ? " (LOWER(l.nama) LIKE '%".$filter["keyword"]."%' OR l.noRegistrasi LIKE '%".$filter["keyword"]."%')" : '');
    $data['bentukLembaga']  = (isset($filter["bentukLembaga"]) ? " l.kodeBentukLembaga = '".$filter["bentukLembaga"]."'" : '');
    $data['wilayah']        = (isset($filter["wilayah"]) ? " l.kodeWilayah = '".$filter["wilayah"]."'" : '');
    $data['kecamatan']      = (isset($filter["kecamatan"]) ? " l.kodeKecamatan = '".$filter["kecamatan"]."'" : '');
    $data['kelurahan']      = (isset($filter["kelurahan"]) ? " l.kodeKelurahan = '".$filter["kelurahan"]."'" : '');
    $data['ajuan']          = (isset($filter["ajuan"]) ? $filter["ajuan"] : '');
    $data['valid']          = (isset($filter["valid"]) ? $filter["valid"] : '');

    if($data['valid'] != 'false' || $data['ajuan'] != 'false'){
        $sql = "SELECT * FROM (";
        
        if($data['valid'] != 'false'){
            $sql = $sql."
                SELECT * FROM (
                    SELECT 
                        noRegistrasi,
                        TRIM(LEADING ' ' FROM l.nama) as nama,
                        bl.namaBentukLembaga,
                        COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as urlGambarLogo,
                        '1' as statusVerifikasi
                    FROM dplega_000_lembaga l
                    JOIN
                        dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                    WHERE l.statusAktif = '1' ";
            
                if($filter["keyword"] != ""){
                    $sql = $sql." AND ".$data['keyword'];
                }

                if($filter["bentukLembaga"] != ""){
                    $sql = $sql." AND ".$data['bentukLembaga'];
                }

                if($filter["wilayah"] != ""){
                    $sql = $sql." AND ".$data['wilayah'];
                }

                if($filter["kecamatan"] != ""){
                    $sql = $sql." AND ".$data['kecamatan'];
                }

                if($filter["kelurahan"] != ""){
                    $sql = $sql." AND ".$data['kelurahan'];
                }
            
            $sql = $sql."
                ) as table_1 
            ";
        }
        
        if($data['ajuan'] != 'false'){

            if($data['valid'] != 'false'){ 
                 $sql = $sql." UNION ";
            }
            $sql = $sql."
                SELECT * FROM (
                    SELECT 
                        noRegistrasi,
                        TRIM(LEADING ' ' FROM l.nama) as nama,
                        bl.namaBentukLembaga,
                        COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as urlGambarLogo,
                        '0' as statusVerifikasi
                    FROM dplega_000_lembaga_temp l
                    JOIN
                        dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                    WHERE l.statusAktif = '1' ";
                
                if($filter["keyword"] != ""){
                    $sql = $sql." AND ".$data['keyword'];
                }

                if($filter["bentukLembaga"] != ""){
                    $sql = $sql." AND ".$data['bentukLembaga'];
                }

                if($filter["wilayah"] != ""){
                    $sql = $sql." AND ".$data['wilayah'];
                }

                if($filter["kecamatan"] != ""){
                    $sql = $sql." AND ".$data['kecamatan'];
                }

                if($filter["kelurahan"] != ""){
                    $sql = $sql." AND ".$data['kelurahan'];
                }

            $sql = $sql."  
                ) as table_2 
            ";
        }

        $sql = $sql."
            ) as main_table ORDER BY nama LIMIT ".$offset.", 25
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
    }else{
        $result = array();
    }

    return $response->withJson($result, 200);
});

$app->post("/summary/lembaga/", function (Request $request, Response $response){
    $filter = $request->getParsedBody();
    $data['keyword']        = (isset($filter["keyword"]) ? " l.nama LIKE '%".$filter["keyword"]."%'" : '');
    $data['bentukLembaga']  = (isset($filter["bentukLembaga"]) ? " l.kodeBentukLembaga = '".$filter["bentukLembaga"]."'" : '');
    $data['wilayah']        = (isset($filter["wilayah"]) ? " l.kodeWilayah = '".$filter["wilayah"]."'" : '');
    $data['kecamatan']      = (isset($filter["kecamatan"]) ? " l.kodeKecamatan = '".$filter["kecamatan"]."'" : '');
    $data['kelurahan']      = (isset($filter["kelurahan"]) ? " l.kodeKelurahan = '".$filter["kelurahan"]."'" : '');
    $data['ajuan']          = (isset($filter["ajuan"]) ? $filter["ajuan"] : '');
    $data['valid']          = (isset($filter["valid"]) ? $filter["valid"] : '');

    if($data['valid'] != 'false' || $data['ajuan'] != 'false'){
        $sql = "SELECT SUM(summary) as summary FROM (";
        
        if($data['valid'] != 'false'){
            $sql = $sql."
                SELECT * FROM (
                    SELECT 
                        COUNT(noRegistrasi) as summary
                    FROM dplega_000_lembaga l
                    JOIN
                        dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                    WHERE l.statusAktif = '1' ";
            
                if($filter["keyword"] != ""){
                    $sql = $sql." AND ".$data['keyword'];
                }

                if($filter["bentukLembaga"] != ""){
                    $sql = $sql." AND ".$data['bentukLembaga'];
                }

                if($filter["wilayah"] != ""){
                    $sql = $sql." AND ".$data['wilayah'];
                }

                if($filter["kecamatan"] != ""){
                    $sql = $sql." AND ".$data['kecamatan'];
                }

                if($filter["kelurahan"] != ""){
                    $sql = $sql." AND ".$data['kelurahan'];
                }
            
            $sql = $sql."
                ) as table_1 
            ";
        }
        
        if($data['ajuan'] != 'false'){

            if($data['valid'] != 'false'){ 
                 $sql = $sql." UNION ";
            }
            $sql = $sql."
                SELECT * FROM (
                    SELECT 
                        COUNT(noRegistrasi) as summary
                    FROM dplega_000_lembaga_temp l
                    JOIN
                        dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                    WHERE l.statusAktif = '1' ";
                
                if($filter["keyword"] != ""){
                    $sql = $sql." AND ".$data['keyword'];
                }

                if($filter["bentukLembaga"] != ""){
                    $sql = $sql." AND ".$data['bentukLembaga'];
                }

                if($filter["wilayah"] != ""){
                    $sql = $sql." AND ".$data['wilayah'];
                }

                if($filter["kecamatan"] != ""){
                    $sql = $sql." AND ".$data['kecamatan'];
                }

                if($filter["kelurahan"] != ""){
                    $sql = $sql." AND ".$data['kelurahan'];
                }

            $sql = $sql."  
                ) as table_2 
            ";
        }

        $sql = $sql."
            ) as main_table
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
    }else{
        $result = array('summary' => 0);
    }

    return $response->withJson($result, 200);
});

$app->get("/list/lembaga/{page}", function (Request $request, Response $response, $args){
    $page   = $args["page"];
    $offset = intval($page);
    $offset = ($offset - 1) * 25;

    $sql = "
        SELECT * FROM (
            SELECT * FROM (
                SELECT 
                    noRegistrasi,
                    TRIM(LEADING ' ' FROM l.nama) as nama,
                    bl.namaBentukLembaga,
                    COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as urlGambarLogo,
                    '1' as statusVerifikasi
                FROM dplega_000_lembaga l
                JOIN
                    dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                WHERE l.statusAktif = '1'
            ) as table_1 
            UNION
            SELECT * FROM (
                SELECT 
                    noRegistrasi,
                    TRIM(LEADING ' ' FROM l.nama) as nama,
                    bl.namaBentukLembaga,
                    COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as urlGambarLogo,
                    '0' as statusVerifikasi
                FROM dplega_000_lembaga_temp l
                JOIN
                    dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                WHERE l.statusAktif = '1'
            ) as table_2 
        ) as main_table ORDER BY nama LIMIT ".$offset.", 25
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/count/lembaga/", function (Request $request, Response $response, $args){
    
    $sql = "
        SELECT 
        b.kodeBentukLembaga,
        b.namaBentukLembaga,
        b.deskripsi,
        COALESCE(b.urlGambar, 'icon-1.png') as `urlGambar`,
        (SELECT COUNT(noRegistrasi) FROM dplega_000_lembaga l WHERE l.kodeBentukLembaga = b.kodeBentukLembaga AND l.statusAktif = '1') as `valid`,
        (SELECT COUNT(noRegistrasi) FROM dplega_000_lembaga_temp lt WHERE lt.kodeBentukLembaga = b.kodeBentukLembaga AND lt.statusAktif = '1') as `ajuan`
        FROM
        dplega_200_bentuklembaga b
        ORDER BY kodeBentukLembaga ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/count/summary/", function (Request $request, Response $response, $args){
    
    $sql = "
        SELECT 
        ((SELECT COUNT(idData) FROM dplega_005_koleksi p WHERE p.judulKoleksi <> '-' AND p.judulKoleksi <> 'belum ada' AND p.judulKoleksi <> '' AND p.judulKoleksi <> 'Tidak ada' AND LOWER(p.judulKoleksi) <> 'buku') + (SELECT COUNT(idData) FROM dplega_005_koleksi_temp p WHERE p.judulKoleksi <> '-' AND p.judulKoleksi <> 'belum ada' AND p.judulKoleksi <> '' AND p.judulKoleksi <> 'Tidak ada' AND LOWER(p.judulKoleksi) <> 'buku')) as `koleksi`,
        ((SELECT COUNT(idData) FROM dplega_006_prestasi p WHERE p.deskripsi <> '-' AND p.deskripsi <> 'belum ada' AND p.deskripsi <> '') + (SELECT COUNT(idData) FROM dplega_006_prestasi_temp p WHERE p.deskripsi <> '-' AND p.deskripsi <> 'belum ada' AND p.deskripsi <> '')) as `prestasi`,
        (SELECT COUNT(noRegistrasi) FROM dplega_000_lembaga l WHERE l.statusAktif = '1') as `valid`,
        (SELECT COUNT(noRegistrasi) FROM dplega_000_lembaga_temp lt WHERE lt.statusAktif = '1') as `ajuan`
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "
        SELECT 
            '".$status."' as statusVerifikasi,
            COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as avatar, 
            l.noRegistrasi as noreg, 
            l.nama,
            l.kodeBentukLembaga,
            b.namaBentukLembaga,
            l.catatanLain,
            l.noTelp as telp,
            l.email,
            l.mediaSosial,
            l.langitude,
            l.latitude,
            CONCAT_WS(' ', `alamat`, 'RT.',`noRt`, '/', 'RW.', `noRw`, `namaKelurahan`, `namaKecamatan`, `namaWilayah`, `namaProvinsi`) as alamat,
            l.noNpwp,
            l.jumlahPengurus,
            l.visiLembaga,
            l.misiLembaga,
            l.organisasiAfiliasi,
            g.namaBidangGerak,
            l.alamat as alamat_,
            l.noRt,
            l.noRw,
            l.kodeWilayah,
            l.kodeKecamatan,
            l.kodeKelurahan,
            l.kodeBidangGerak
        FROM
            dplega_000_lembaga".$dumbTable." l
        JOIN
            dplega_200_bentuklembaga b ON l.kodeBentukLembaga = b.kodeBentukLembaga
        LEFT JOIN
            dplega_100_provinsi p ON l.kodeProvinsi = p.idData
        LEFT JOIN
            dplega_101_wilayah w ON l.kodeWilayah = w.idData
        LEFT JOIN
            dplega_102_kecamatan kc ON l.kodeKecamatan = kc.idData
        LEFT JOIN
            dplega_103_kelurahan kl ON l.kodeKelurahan = kl.idData
        LEFT JOIN
            dplega_210_bidanggerak g ON l.kodeBidangGerak = g.kodeBidangGerak
        WHERE
            l.noRegistrasi = '".$noRegistrasi."'
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/legalitas/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "
        SELECT 
            l.noLegalitas,
            l.tanggalLegalitas,
            l.urlFile,
            l.statusVerifikasi,
            p.namaPersyaratan
        FROM
            dplega_009_legalitas".$dumbTable." l
        LEFT JOIN
            dplega_201_persyaratan p ON l.kodePersyaratan = p.kodePersyaratan
        WHERE
            l.noRegistrasi = '".$noRegistrasi."'
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/legalitas-form/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $kodeBentukLembaga = '';
    $status = 'valid';
    $sql  = "SELECT noRegistrasi, kodeBentukLembaga FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 'ajuan';

        $sql  = "SELECT noRegistrasi, kodeBentukLembaga FROM dplega_000_lembaga_temp WHERE noRegistrasi = '".$noRegistrasi."'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $res  = $stmt->fetch();
    }

    $kodeBentukLembaga = $res['kodeBentukLembaga'];
    
    $sql = "
        SELECT 
            p.kodePersyaratan as kodePersyaratan,
            p.namaPersyaratan as namaPersyaratan,
            COALESCE((SELECT noLegalitas FROM dplega_009_legalitas".$dumbTable." l1 WHERE l1.noRegistrasi = '".$noRegistrasi."' AND l1.kodePersyaratan = p.kodePersyaratan), '') as noLegalitas,
            COALESCE((SELECT tanggalLegalitas FROM dplega_009_legalitas".$dumbTable." l1 WHERE l1.noRegistrasi = '".$noRegistrasi."' AND l1.kodePersyaratan = p.kodePersyaratan), '') as tanggalLegalitas,
            COALESCE((SELECT urlFile FROM dplega_009_legalitas".$dumbTable." l1 WHERE l1.noRegistrasi = '".$noRegistrasi."' AND l1.kodePersyaratan = p.kodePersyaratan), '') as urlFile,
            COALESCE((SELECT statusVerifikasi FROM dplega_009_legalitas".$dumbTable." l1 WHERE l1.noRegistrasi = '".$noRegistrasi."' AND l1.kodePersyaratan = p.kodePersyaratan), '') as statusVerifikasi
        FROM
            dplega_201_persyaratan p
            
        WHERE
            p.kodeBentukLembaga = '".$kodeBentukLembaga."'
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/sejarah/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "
        SELECT 
            *
        FROM
            dplega_001_sejarah".$dumbTable." l
        WHERE
            l.noRegistrasi = '".$noRegistrasi."'
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/kepengurusan/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "
        SELECT 
            l.*,
            CONCAT_WS(' ', `alamat`, 'RT.',`noRt`, '/', 'RW.', `noRw`, `namaKelurahan`, `namaKecamatan`, `namaWilayah`, `namaProvinsi`) as alamatLengkap
        FROM
            dplega_002_kepengurusan".$dumbTable." l
        LEFT JOIN
            dplega_100_provinsi p ON l.kodeProvinsi = p.idData
        LEFT JOIN
            dplega_101_wilayah w ON l.kodeWilayah = w.idData
        LEFT JOIN
            dplega_102_kecamatan kc ON l.kodeKecamatan = kc.idData
        LEFT JOIN
            dplega_103_kelurahan kl ON l.kodeKelurahan = kl.idData
        WHERE
            l.noRegistrasi = '".$noRegistrasi."'
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/usaha/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "
        SELECT 
            *
        FROM
            dplega_003_usaha".$dumbTable." l
        WHERE
            l.noRegistrasi = '".$noRegistrasi."'
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/prestasi/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "
        SELECT 
            *
        FROM
            dplega_006_prestasi".$dumbTable." l
        WHERE
            l.noRegistrasi = '".$noRegistrasi."'
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/koleksi/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "
        SELECT 
            *
        FROM
            dplega_005_koleksi".$dumbTable." l
        WHERE
            l.noRegistrasi = '".$noRegistrasi."'
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/verifikasi/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $kodeBentukLembaga = '';
    $status = 'valid';
    $sql  = "SELECT noRegistrasi, kodeBentukLembaga FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 'ajuan';

        $sql  = "SELECT noRegistrasi, kodeBentukLembaga FROM dplega_000_lembaga_temp WHERE noRegistrasi = '".$noRegistrasi."'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $res  = $stmt->fetch();
    }

    $kodeBentukLembaga = $res['kodeBentukLembaga'];
    
    $sql = "
        SELECT * FROM (
            SELECT * FROM (
                SELECT 
                    'legalitas' as idGrup,
                    'Kelengkapan legalitas' as namaGrup,
                    '".$status."' as statusValid,
                    p.kodePersyaratan as kode,
                    p.namaPersyaratan as nama,
                    COALESCE((SELECT noLegalitas FROM dplega_009_legalitas".$dumbTable." l1 WHERE l1.noRegistrasi = '".$noRegistrasi."' AND l1.kodePersyaratan = p.kodePersyaratan), '') as noLegalitas,
                    COALESCE((SELECT tanggalLegalitas FROM dplega_009_legalitas".$dumbTable." l1 WHERE l1.noRegistrasi = '".$noRegistrasi."' AND l1.kodePersyaratan = p.kodePersyaratan), '') as tanggalLegalitas,
                    COALESCE((SELECT urlFile FROM dplega_009_legalitas".$dumbTable." l1 WHERE l1.noRegistrasi = '".$noRegistrasi."' AND l1.kodePersyaratan = p.kodePersyaratan), '') as urlFile,
                    COALESCE((SELECT statusVerifikasi FROM dplega_009_legalitas".$dumbTable." l1 WHERE l1.noRegistrasi = '".$noRegistrasi."' AND l1.kodePersyaratan = p.kodePersyaratan), '') as statusVerifikasi
                FROM
                    dplega_201_persyaratan p
                    
                WHERE
                    p.kodeBentukLembaga = '".$kodeBentukLembaga."'
            ) as table_legalitas
            
            UNION
            
            SELECT * FROM (
                SELECT 
                    CONCAT_WS('', 'lainnya', v.kodeVerifikasi) as idGrup,
                    g.namaGrupVerifikasi as namaGrup,
                    '".$status."' as statusValid,
                    v.kodeVerifikasi as kode,
                    v.namaVerifikasi as nama,
                    '' as noLegalitas,
                    '' as tanggalLegalitas,
                    '' as urlFile,
                    COALESCE((SELECT status FROM dplega_012_verifikasi".$dumbTable." v1 WHERE v1.noRegistrasi = '".$noRegistrasi."' AND v1.kodeVerifikasi = v.kodeVerifikasi), '') as statusVerifikasi
                FROM
                    dplega_221_verifikasi v
                LEFT JOIN
                    dplega_220_grupverifikasi g ON v.kodeGrupVerifikasi = g.kodeGrupVerifikasi
            ) as table_lainnya

        ) as main_table
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/detail/lembaga/revisi/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "
     SELECT * FROM (
         SELECT * FROM (
            SELECT 
                'verifikasi' as grup,
                p.namaPersyaratan,
                l.catatan,
                IF(l.type = '0', 'Kelengkapan Legalitas', 'Kelengkapan Lainnya') as type,
                l.createdDate,
                DATE_FORMAT(l.createdDate, '%Y-%M-%d') as tanggal,
                DATE_FORMAT(l.createdDate,'%H:%i') jam
            FROM
                dplega_013_verifikasilogs".$dumbTable." l
            LEFT JOIN 
                dplega_201_persyaratan p ON l.kode = p.kodePersyaratan
            WHERE
                l.noRegistrasi = '".$noRegistrasi."'
        ) as verifikasi_logs
        UNION
        SELECT * FROM (
            SELECT 
                'revisi' as grup,
                '' as namaPersyaratan,
                l.catatan,
                '' as type,
                l.createdDate,
                DATE_FORMAT(l.createdDate, '%Y-%M-%d') as tanggal,
                DATE_FORMAT(l.createdDate,'%H:%i') jam
            FROM
                dplega_014_revisi".$dumbTable." l
            WHERE
                l.noRegistrasi = '".$noRegistrasi."'
        ) as revisi
    ) as main_table ORDER BY createdDate DESC
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->post("/insert/lembaga/revisi/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];
    $data = $request->getParsedBody();

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "INSERT INTO dplega_014_revisi".$dumbTable." (noRegistrasi, catatan, createdBy, createdDate) VALUES
        (
            :noRegistrasi,
            :catatan,
            :username,
            NOW()
        )
    ";

    $stmt = $this->db->prepare($sql);
    if($stmt->execute($data))
       return $response->withJson(["status" => "success", "data" => "1"], 200);
    
    return $response->withJson(["status" => "failed", "data" => $data, "noReg" => $noRegistrasi], 200);
});


// MANAGE KELEMBAGAAN
// ------------
$app->post("/insert/lembaga/", function (Request $request, Response $response, $args){
    $data = $request->getParsedBody();

    // for jabar
    $data["kodeProvinsi"] = '8';

    // translating

    $sql =
    "   SELECT 
            (SELECT kodeWilayah FROM dplega_101_wilayah WHERE idData  = '".$data['kodeWilayah']."' LIMIT 1) as idWilayah,
            (SELECT kodeKecamatan FROM dplega_102_kecamatan WHERE idData  = '".$data['kodeKecamatan']."' LIMIT 1) as idKecamatan,
            (SELECT kodeKelurahan FROM dplega_103_kelurahan WHERE idData  = '".$data['kodeKelurahan']."' LIMIT 1) as idKelurahan
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    $res['idProvinsi'] = '32';

    // checking noRegistrasi
	$idTemp = $res["idProvinsi"].$res["idWilayah"].$res["idKecamatan"];

    $sql =
    "
        SELECT noRegistrasi
        FROM dplega_000_lembaga_temp
        WHERE 
            noRegistrasi LIKE '".$idTemp."%'
        ORDER BY noRegistrasi DESC LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $idTemp = $idTemp.'00001';
    }else{
        $idTempB   = substr($res["noRegistrasi"],6,5);
        $idTempC   = $idTempB + 1;
        $str 	   = strlen($idTempC);
        switch ($str) {
            case 1:
                $idTemp = $idTemp.'0000'.$idTempC;
                break;
            case 2:
                $idTemp = $idTemp.'000'.$idTempC;
                break;
            case 3:
                $idTemp = $idTemp.'00'.$idTempC;
                break;
            case 4:
                $idTemp = $idTemp.'0'.$idTempC;
                break;
            default:
                $idTemp = $idTemp.$idTempC;
                break;
        }
    }
   
    //create new lembaga
    $sql = "INSERT INTO dplega_000_lembaga_temp 
        (
            noRegistrasi,
            nama,
            alamat,
            noRt,
            noRw,
            kodeKelurahan,
            kodeKecamatan,
            kodeWilayah,
            kodeProvinsi,
            langitude,
            latitude,
            noTelp,
            email,
            mediaSosial,
            kodeBentukLembaga,
            kodeBidangGerak,
            jumlahPengurus,
            noNpwp,
            visiLembaga,
            misiLembaga,
            organisasiAfiliasi,
            catatanLain,
            createdBy, createdDate
        ) 
            VALUES
        (
            '".$idTemp."',
            '".$data['nama']."',
            '".$data['alamat_']."',
            '".$data['noRt']."',
            '".$data['noRw']."',
            '".$data['kodeKelurahan']."',
            '".$data['kodeKecamatan']."',
            '".$data['kodeWilayah']."',
            '".$data['kodeProvinsi']."',
            '".$data['langitude']."',
            '".$data['latitude']."',
            '".$data['telp']."',
            '".$data['email']."',
            '".$data['medsos']."',
            '".$data['kodeBentukLembaga']."',
            '".$data['bidangGerak']."',
            '".$data['jumlahPengurus']."',
            '".$data['npwp']."',
            '".$data['visi']."',
            '".$data['misi']."',
            '".$data['afiliasi']."',
            '".$data['catatan']."',
            '".$data['username']."',
            NOW()
        )
    ";

    $stmt = $this->db->prepare($sql);
    if($stmt->execute()){

        // uploading avatar
        $dumbQuery = "";
        $dumbValue = "";
        $imageStat = 0;

        // NEW LEMBAGA NO NEED TO UPLOAD LOGO AT FIRST
        // $uploadedFiles = $request->getUploadedFiles();
        // // handle single input with single file upload
        // $uploadedFile = $uploadedFiles['file'];
        // if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            
        //     $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            
        //     // ubah nama file dengan id buku
        //     $filename = sprintf('%s.%0.8s', $idTemp.'_logo', $extension);
            
        //     $directory = $this->get('settings')['logo_directory'];
        //     $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        //     // simpan nama file ke database
        //     $sql = "UPDATE dplega_910_user SET urlGambarLogo=:urlGambarLogo WHERE noRegistrasi=:noRegistrasi";
        //     $stmt = $this->db->prepare($sql);
        //     $params = [
        //         ":noRegistrasi" => $idTemp,
        //         ":urlGambarLogo" => $filename
        //     ];
            
        //     if($stmt->execute($params)){
        //         $imageStat = 1;
        //     }
        // }

        // // create user
        // if($imageStat == 1 && $filename != ""){
        //     $dumbQuery = "urlGambar,";
        //     $dumbValue = "'".$filename."',";
        // }

        $usernameTemp = strtolower(preg_replace('/\s+/', '', $idTemp.$data['nama']));
		if(strlen($usernameTemp) > 20){ $usernameTemp = substr($usernameTemp, 0, 19); }

        $sql = 
        "	INSERT INTO dplega_910_user
            (
                noRegistrasi,
                nama,
                jabatan,
                alamat,
                noRt,
                noRw,
                kodeKelurahan,
                kodeKecamatan,
                kodeWilayah,
                kodeProvinsi,
                noTelp,
                email,
                username,
                password,
                userLevel,
                statusActive,
                ".$dumbQuery."
                createdBy, createdDate
            )
            VALUES
            (
                '".$idTemp."',
                '".$data['nama']."',
                'Penanggung jawab Lembaga',
                '".$data['alamat']."',
                '".$data['rt']."',
                '".$data['rw']."',
                '".$data['kodeKelurahan']."',
                '".$data['kodeKecamatan']."',
                '".$data['kodeWilayah']."',
                '".$data['kodeProvinsi']."',
                '".$data['telp']."',
                '".$data['email']."',
                '".$usernameTemp."',
                md5('jabarprov'),
                '1',
                '1',
                ".$dumbValue."
                '".$data['username']."', NOW()
            )
        ";

        $stmt = $this->db->prepare($sql);
        if($stmt->execute()){
            //access list
            $sql = 
            "	INSERT INTO dplega_911_useraccess
                (
                    username,
                    idApps,
                    module,
                    lihat,
                    tambah,
                    ubah,
                    hapus,
                    createdBy, createdDate
                )
                VALUES
                (
                    '".$usernameTemp."',
                    '1',
                    'kelembagaan',
                    '1',
                    '0',
                    '1',
                    '0',
                    'TESTSESSION', NOW()
                );
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }

       return $response->withJson(["status" => "success", "data" => "1"], 200);
    }

    return $response->withJson(["status" => "failed", "data" => $data, "noReg" => $idTemp, "sql" => $sql], 200);
});

$app->post('/upload/lembaga/logo/', function(Request $request, Response $response, $args) {
    
    $uploadedFiles = $request->getUploadedFiles();
    $credential    = $request->getParsedBody();

    $noRegistrasi = $credential["noRegistrasi"];

    // checking
    $dumbTable = '';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
    }

    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['file'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        
        // ubah nama file dengan id buku
        $filename = sprintf('%s.%0.8s', $credential["noRegistrasi"].'_logo', $extension);
        
        $directory = $this->get('settings')['logo_directory'];
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        // simpan nama file ke database
        $sql = "UPDATE dplega_000_lembaga".$dumbTable." SET urlGambarLogo=:urlGambarLogo WHERE noRegistrasi=:noRegistrasi";
        $stmt = $this->db->prepare($sql);
        $params = [
            ":noRegistrasi" => $credential["noRegistrasi"],
            ":urlGambarLogo" => $filename
        ];
        
        if($stmt->execute($params)){
            // ambil base url dan gabungkan dengan file name untuk membentuk URL file
            // $url = $request->getUri()->getBaseUrl().$filename;
            return $response->withJson(["status" => "success", "filename" => $filename], 200);
        }
        
    }

    return $response->withJson(["status" => "failed", "filename" => "0", "data"=> $credential], 200);
});

// ------------
$app->post("/update/lembaga/", function (Request $request, Response $response, $args){
    $data = $request->getParsedBody();
	$noRegistrasi = $data["noRegistrasi"];

    // checking
    $dumbTable = '';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
    }
    
    //update lembaga
    $sql = "
        UPDATE dplega_000_lembaga".$dumbTable."
        SET 
            `nama`='".$data["nama"]."', 
            `alamat`='".$data["alamat_"]."', 
            `noRt`='".$data["noRt"]."', 
            `noRw`='".$data["noRw"]."', 
            `kodeWilayah`='".$data["kodeWilayah"]."', 
            `kodeKelurahan`='".$data["kodeKelurahan"]."', 
            `kodeKecamatan`='".$data["kodeKecamatan"]."', 
            `noTelp`='".$data["telp"]."', 
            `email`='".$data["email"]."',
            `langitude`='".$data["langitude"]."',
            `latitude`='".$data["latitude"]."',
            `mediaSosial`='".$data["mediaSosial"]."',
            `kodeBidangGerak`='".$data["kodeBidangGerak"]."',
            `jumlahPengurus`='".$data["jumlahPengurus"]."',
            `noNpwp`='".$data["noNpwp"]."',
            `visiLembaga`='".$data["visiLembaga"]."',
            `misiLembaga`='".$data["misiLembaga"]."',
            `organisasiAfiliasi`='".$data["organisasiAfiliasi"]."',
            `catatanLain`='".$data["catatanLain"]."',
            `changedBy`='".$data["username"]."',
            `changedDate`= NOW()
        WHERE noRegistrasi='".$data["noRegistrasi"]."'";
    $stmt = $this->db->prepare($sql);

    if($stmt->execute()){
        return $response->withJson(["status" => "success", "data" => "1"], 200);
    }

    return $response->withJson(["status" => "failed", "data" => $data, "sql" => $noRegistrasi], 200);
});

$app->post('/update/lembaga/legalitas', function(Request $request, Response $response, $args) {
    
    $uploadedFiles = $request->getUploadedFiles();
    $data     = $request->getParsedBody();

    $filename = $data['urlFile'];
    $uploadStatus = "No file selected";

    // handle single input with single file upload
    if($uploadedFiles){
        $uploadedFile = $uploadedFiles['file'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            
            $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            
            // ubah nama file 
            $filename = sprintf('%s.%0.8s', $data["noRegistrasi"].'_'.$data["kodePersyaratan"].'_legalitas', $extension);
            
            $directory = $this->get('settings')['legalitas_directory'];
            $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
            $uploadStatus = "File uploaded";
        }else{
            $uploadStatus = "File error";
        }
    }

    // checking
    $dumbTable = '';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$data['noRegistrasi']."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
    }
    
    //checking existing data
    $sql  = "SELECT noRegistrasi FROM dplega_009_legalitas".$dumbTable." WHERE noRegistrasi = '".$data['noRegistrasi']."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){

        // simpan nama file ke database
        $sql = "INSERT INTO dplega_009_legalitas".$dumbTable." (
            noRegistrasi,
            kodePersyaratan,
            noLegalitas,
            tanggalLegalitas,
            urlFile,
            statusVerifikasi,
            createdBy,
            createdDate
        ) VALUES (
            '".$data["noRegistrasi"]."',
            '".$data["kodePersyaratan"]."',
            '".$data["noLegalitas"]."',
            '".$data["tanggalLegalitas"]."',
            '".$filename."',
            '0',
            '".$data["username"]."',
            NOW()
        ) 
        ";
    } else {
        $sql = "
        UPDATE dplega_009_legalitas".$dumbTable."
        SET 
            `noLegalitas`='".$data["noLegalitas"]."',
            `tanggalLegalitas`='".$data["tanggalLegalitas"]."',
            `urlFile`='".$filename."',
            `statusVerifikasi`='0',
            `changedBy`='".$data["username"]."',
            `changedDate`= NOW()
        WHERE noRegistrasi='".$data["noRegistrasi"]."' AND kodePersyaratan='".$data["kodePersyaratan"]."'";
    }

    $stmt = $this->db->prepare($sql);
    if($stmt->execute()){
        // ambil base url dan gabungkan dengan file name untuk membentuk URL file
        return $response->withJson(["status" => "success", "upload" => $uploadStatus], 200);
    }
    
    return $response->withJson(["status" => "failed", "upload" => $uploadStatus], 200);
});

$app->post("/update/lembaga/sejarah", function (Request $request, Response $response, $args){
    $data = $request->getParsedBody();
	$noRegistrasi = $data["noRegistrasi"];

    // checking
    $dumbTable = '';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
    }

    //checking existing data
    $sql  = "SELECT noRegistrasi FROM dplega_001_sejarah".$dumbTable." WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){

        //insert sejarah
        $sql = "INSERT INTO dplega_001_sejarah".$dumbTable." (
                noRegistrasi, 
                deskripsi, 
                tanggalDidirikan,
                kepemilikan,
                statusTanah,
                statusSertifikasi,
                luasTanah,
                satuanLuasTanah,
                luasBangunan,
                satuanLuasBangunan,
                kondisiBangunan,
                jumlahBangunan,
                statusSarana,
                statusStrukturKepengurusan,
                bahasaPengantar,
                statusSensus,
                statusBantuanPemerintah,
                kondisiGeografis,
                potensiWilayah,
                jenisWilayah,
                catatanLain,
                createdBy, createdDate) VALUES
            (
                '".$data["noRegistrasi"]."',
                '".$data["deskripsi"]."',
                '".$data["tanggalDidirikan"]."',
                '".$data["kepemilikan"]."',
                '".$data["statusTanah"]."',
                '".$data["statusSertifikasi"]."',
                '".$data["luasTanah"]."',
                '".$data["satuanLuasTanah"]."',
                '".$data["luasBangunan"]."',
                '".$data["satuanLuasBangunan"]."',
                '".$data["kondisiBangunan"]."',
                '".$data["JumlahBangunan"]."',
                '".$data["statusSarana"]."',
                '".$data["statusStrukturKepengurusan"]."',
                '".$data["bahasaPengantar"]."',
                '".$data["statusSensus"]."',
                '".$data["statusBantuanPemerintah"]."',
                '".$data["kondisiGeografis"]."',
                '".$data["potensiWilayah"]."',
                '".$data["jenisWilayah"]."',
                '".$data["catatanLain"]."',
                '".$data["username"]."',
                NOW()
            )
        ";
    } else {
    //update sejarah
    $sql = "
        UPDATE dplega_001_sejarah".$dumbTable."
        SET 
            `deskripsi`= '".$data["deskripsi"]."', 
            `tanggalDidirikan`= '".$data["tanggalDidirikan"]."',
            `kepemilikan`= '".$data["kepemilikan"]."',
            `statusTanah`= '".$data["statusTanah"]."',
            `statusSertifikasi`= '".$data["statusSertifikasi"]."',
            `luasTanah`= '".$data["luasTanah"]."',
            `satuanLuasTanah`= '".$data["atuanLuasTanah"]."',
            `luasBangunan`= '".$data["luasBangunan"]."',
            `satuanLuasBangunan`= '".$data["satuanLuasBangunan"]."',
            `kondisiBangunan`= '".$data["kondisiBangunan"]."',
            `jumlahBangunan`= '".$data["jumlahBangunan"]."',
            `statusSarana`= '".$data["statusSarana"]."',
            `statusStrukturKepengurusan`= '".$data["statusStrukturKepengurusan"]."',
            `bahasaPengantar`= '".$data["bahasaPengantar"]."',
            `statusSensus`= '".$data["statusSensus"]."',
            `statusBantuanPemerintah`= '".$data["statusBantuanPemerintah"]."',
            `kondisiGeografis`= '".$data["kondisiGeografis"]."',
            `potensiWilayah`= '".$data["potensiWilayah"]."',
            `jenisWilayah`= '".$data["jenisWilayah"]."',
            `catatanLain`= '".$data["catatanLain"]."',
            `changedBy`= '".$data["username"]."',
            `changedDate`= NOW()
        WHERE noRegistrasi= '".$data["noRegistrasi"]."'";
    }

    $stmt = $this->db->prepare($sql);

    if($stmt->execute()){
        return $response->withJson(["status" => "success", "data" => "1"], 200);
    }

    return $response->withJson(["status" => "failed", "data" => $data, "noReg" => $idTemp], 200);
});

$app->post("/update/lembaga/kepengurusan", function (Request $request, Response $response, $args){
    $data = $request->getParsedBody();
	$noRegistrasi = $data["noRegistrasi"];

    // checking
    $dumbTable = '';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
    }

    //checking existing data
    $sql  = "SELECT noRegistrasi FROM dplega_002_kepengurusan".$dumbTable." WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){

        //insert kepengurusan
        $sql = "INSERT INTO dplega_002_kepengurusan".$dumbTable." (
            noRegistrasi,
            penanggungJawab,
            jabatan,
            alamat,
            noRt,
            noRw,
            kodeKelurahan,
            kodeKecamatan,
            kodeWilayah,
            kodeProvinsi,
            noTelp,
            kewarganegaraan,
            tempatLahir,
            tanggalLahir,
            jenisKelamin,
            agama,
            jabatanLain,
            pendidikan,
            kompetensi,
            catatan,
            createdBy, createdDate) VALUES
        (
            '".$data["noRegistrasi"]."',
            '".$data["penanggungJawab"]."',
            '".$data["jabatan"]."',
            '".$data["alamat"]."',
            '".$data["noRt"]."',
            '".$data["noRw"]."',
            '".$data["kodeKelurahan"]."',
            '".$data["kodeKecamatan"]."',
            '".$data["kodeWilayah"]."',
            '".$data["kodeProvinsi"]."',
            '".$data["noTelp"]."',
            '".$data["kewarganegaraan"]."',
            '".$data["tempatLahir"]."',
            '".$data["tanggalLahir"]."',
            '".$data["jenisKelamin"]."',
            '".$data["agama"]."',
            '".$data["jabatanLain"]."',
            '".$data["pendidikan"]."',
            '".$data["kompetensi"]."',
            '".$data["catatan"]."',
            '".$data["username"]."',
            NOW()
        )
        ";
    } else {
    //update sejarah
    $sql = "
        UPDATE dplega_002_kepengurusan".$dumbTable."
        SET 
            `penanggungJawab`='".$data["penanggungJawab"]."', 
            `jabatan`='".$data["jabatan"]."',
            `alamat`='".$data["alamat"]."',
            `noRt`='".$data["noRt"]."',
            `noRw`='".$data["noRw"]."',
            `kodeKelurahan`='".$data["kodeKelurahan"]."',
            `kodeKecamatan`='".$data["kodeKecamatan"]."',
            `kodeWilayah`='".$data["kodeWilayah"]."',
            `noTelp`='".$data["noTelp"]."',
            `kewarganegaraan`='".$data["kewarganegaraan"]."',
            `tempatLahir`='".$data["tempatLahir"]."',
            `tanggalLahir`='".$data["tanggalLahir"]."',
            `jenisKelamin`='".$data["jenisKelamin"]."',
            `agama`='".$data["agama"]."',
            `jabatanLain`='".$data["jabatanLain"]."',
            `pendidikan`='".$data["pendidikan"]."',
            `kompetensi`='".$data["kompetensi"]."',
            `catatan`='".$data["catatan"]."',
            `changedBy`='".$data["username"]."',
            `changedDate`= NOW()
        WHERE noRegistrasi='".$data["noRegistrasi"]."'";
    }

    $stmt = $this->db->prepare($sql);

    if($stmt->execute()){
        return $response->withJson(["status" => "success", "data" => "1"], 200);
    }

    return $response->withJson(["status" => "failed", "data" => $data, "noReg" => $idTemp], 200);
});

$app->post("/update/lembaga/usaha", function (Request $request, Response $response, $args){
    $data = $request->getParsedBody();
	$noRegistrasi = $data["noRegistrasi"];

    // checking
    $dumbTable = '';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
    }

    //checking existing data
    $sql  = "SELECT noRegistrasi FROM dplega_003_usaha".$dumbTable." WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){

        //insert usaha
       $sql = "INSERT INTO dplega_003_usaha".$dumbTable." (noRegistrasi, namaUsaha, jenisUsaha, detailUsaha, jumlahPekerja, catatan, createdBy, createdDate) VALUES
        (
            '".$data["noRegistrasi"]."',
            '".$data["namaUsaha"]."',
            '".$data["jenisUsaha"]."',
            '".$data["detailUsaha"]."',
            '".$data["jumlahPekerja"]."',
            '".$data["catatan"]."',
            '".$data["username"]."',
            NOW()
        )
        ";
    } else {
    //update sejarah
    $sql = "
        UPDATE dplega_003_usaha".$dumbTable."
        SET 
            `namaUsaha`= '".$data["namaUsaha"]."', 
            `jenisUsaha`= '".$data["jenisUsaha"]."',
            `detailUsaha`= '".$data["detailUsaha"]."',
            `jumlahPekerja`= '".$data["jumlahPekerja"]."',
            `catatan`= '".$data["catatan"]."',
            `changedBy`= '".$data["username"]."',
            `changedDate`= NOW()
        WHERE noRegistrasi='".$data["noRegistrasi"]."'";
    }

    $stmt = $this->db->prepare($sql);

    if($stmt->execute()){
        return $response->withJson(["status" => "success", "data" => "1"], 200);
    }

    return $response->withJson(["status" => "failed", "data" => $data, "noReg" => $idTemp], 200);
});

$app->post("/update/lembaga/prestasi", function (Request $request, Response $response, $args){
    $data = $request->getParsedBody();
    $noRegistrasi  = $data["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "INSERT INTO dplega_006_prestasi".$dumbTable." (noRegistrasi, deskripsi, createdBy, createdDate) VALUES
        (
            '".$data["noRegistrasi"]."',
            '".$data["deskripsi"]."',
            '".$data["username"]."',
            NOW()
        )
    ";

    $stmt = $this->db->prepare($sql);
    if($stmt->execute($data))
       return $response->withJson(["status" => "success", "data" => "1"], 200);
    
    return $response->withJson(["status" => "failed", "data" => $data, "noReg" => $noRegistrasi], 200);
});

$app->post("/update/lembaga/koleksi", function (Request $request, Response $response, $args){
    $data = $request->getParsedBody();
    $noRegistrasi  = $data["noRegistrasi"];

    // checking
    $dumbTable = '';
    $status    = '1';
    $sql  = "SELECT noRegistrasi FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 0;
    }
    
    $sql = "INSERT INTO dplega_005_koleksi".$dumbTable." (noRegistrasi, jenisKoleksi, judulKoleksi, deskripsi, createdBy, createdDate) VALUES
        (
            '".$data["noRegistrasi"]."',
            '".$data["judulKoleksi"]."',
            '".$data["jenisKoleksi"]."',
            '".$data["deskripsi"]."',
            '".$data["username"]."',
            NOW()
        )
    ";

    $stmt = $this->db->prepare($sql);
    if($stmt->execute())
       return $response->withJson(["status" => "success", "data" => "1"], 200);
    
    return $response->withJson(["status" => "failed", "data" => $data, "noReg" => $noRegistrasi], 200);
});


// LIST KOLEKSI ------------
$app->get("/list/koleksi/{page}", function (Request $request, Response $response, $args){
    $page   = $args["page"];
    $offset = intval($page);
    $offset = ($offset - 1) * 25;

    $sql = "
        SELECT * FROM(
            SELECT * FROM (
                SELECT 
                    p.idData,
                    p.noRegistrasi,
                    l.nama,
                    p.jenisKoleksi,
                    REPLACE(p.judulKoleksi, '-', '') as judulKoleksi,
                    c.namaBentukLembaga
                FROM 
                    dplega_005_koleksi_temp p
                JOIN
                    dplega_000_lembaga_temp l ON p.noRegistrasi = l.noRegistrasi
                JOIN dplega_200_bentuklembaga as c	ON l.kodeBentukLembaga = c.kodeBentukLembaga
                WHERE l.statusAktif = '1' AND p.judulKoleksi <> '-' AND p.judulKoleksi <> 'belum ada' AND p.judulKoleksi <> '' AND p.judulKoleksi <> 'Tidak ada' AND LOWER(p.judulKoleksi) <> 'buku'
            ) as tabel_1
            UNION
            SELECT * FROM (
                SELECT 
                    p.idData,
                    p.noRegistrasi,
                    l.nama,
                    p.jenisKoleksi,
                    REPLACE(p.judulKoleksi, '-', '') as judulKoleksi,
                    c.namaBentukLembaga
                FROM 
                    dplega_005_koleksi p
                JOIN
                    dplega_000_lembaga l ON p.noRegistrasi = l.noRegistrasi
                JOIN dplega_200_bentuklembaga as c	ON l.kodeBentukLembaga = c.kodeBentukLembaga
                WHERE l.statusAktif = '1' AND p.judulKoleksi <> '-' AND p.judulKoleksi <> 'belum ada' AND p.judulKoleksi <> '' AND p.judulKoleksi <> 'Tidak ada' AND LOWER(p.judulKoleksi) <> 'buku'
            ) as tabel_2
        ) main_table ORDER BY judulKoleksi LIMIT ".$offset.", 25
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/summary/koleksi/", function (Request $request, Response $response, $args){

    $sql = "
        SELECT (
            (SELECT COUNT(idData) FROM dplega_005_koleksi p WHERE p.judulKoleksi <> '-' AND p.judulKoleksi <> 'belum ada' AND p.judulKoleksi <> '' AND p.judulKoleksi <> 'Tidak ada' AND LOWER(p.judulKoleksi) <> 'buku') +
            (SELECT COUNT(idData) FROM dplega_005_koleksi_temp p WHERE p.judulKoleksi <> '-' AND p.judulKoleksi <> 'belum ada' AND p.judulKoleksi <> '' AND p.judulKoleksi <> 'Tidak ada' AND LOWER(p.judulKoleksi) <> 'buku') 
        ) as summary
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $response->withJson($result, 200);
});

// LIST PRESTASI ------------
$app->get("/list/prestasi/{page}", function (Request $request, Response $response, $args){
    $page   = $args["page"];
    $offset = intval($page);
    $offset = ($offset - 1) * 25;

    $sql = "
        SELECT * FROM(
            SELECT * FROM (
                SELECT 
                    p.idData,
                    p.noRegistrasi,
                    l.nama,
                    REPLACE(p.deskripsi, '-', '') as deskripsi,
                    c.namaBentukLembaga
                FROM 
                    dplega_006_prestasi_temp p
                JOIN
                    dplega_000_lembaga_temp l ON p.noRegistrasi = l.noRegistrasi
                JOIN dplega_200_bentuklembaga as c	ON l.kodeBentukLembaga = c.kodeBentukLembaga
                WHERE l.statusAktif = '1' AND p.deskripsi <> '-' AND p.deskripsi <> 'belum ada' AND p.deskripsi <> ''
            ) as tabel_1
            UNION
            SELECT * FROM (
                SELECT 
                    p.idData,
                    p.noRegistrasi,
                    l.nama,
                    REPLACE(p.deskripsi, '-', '') as deskripsi,
                    c.namaBentukLembaga
                FROM 
                    dplega_006_prestasi p
                JOIN
                    dplega_000_lembaga l ON p.noRegistrasi = l.noRegistrasi
                JOIN dplega_200_bentuklembaga as c	ON l.kodeBentukLembaga = c.kodeBentukLembaga
                WHERE l.statusAktif = '1' AND p.deskripsi <> '-' AND p.deskripsi <> 'belum ada' AND p.deskripsi <> ''
            ) as tabel_2
        ) main_table ORDER BY deskripsi LIMIT ".$offset.", 25
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/summary/prestasi/", function (Request $request, Response $response, $args){

    $sql = "
        SELECT (
            (SELECT COUNT(idData) FROM dplega_006_prestasi p WHERE p.deskripsi <> '-' AND p.deskripsi <> 'belum ada' AND p.deskripsi <> '') +
            (SELECT COUNT(idData) FROM dplega_006_prestasi_temp p WHERE p.deskripsi <> '-' AND p.deskripsi <> 'belum ada' AND p.deskripsi <> '') 
        ) as summary
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $response->withJson($result, 200);
});


// LIST BERITA ------------
$app->get("/list/berita/{page}", function (Request $request, Response $response, $args){
    $page   = $args["page"];
    $offset = intval($page);
    $offset = ($offset - 1) * 3;

    $sql = "
        SELECT 
            idData,
            judulBerita,
            LEFT(deskripsi , 125) as deskripsi,
            urlGambar,
            createdBy,
            createdDate
        FROM 
            dplega_230_berita 
        ORDER BY createdDate DESC LIMIT ".$offset.", 3
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/detail/berita/{idData}", function (Request $request, Response $response, $args){
    $idData   = $args["idData"];

    $sql = "
        SELECT 
            idData,
            judulBerita,
            LEFT(deskripsi , 125) as deskripsi,
            urlGambar,
            createdBy,
            createdDate
        FROM 
            dplega_230_berita 
        WHERE
            idData = '".$idData."'
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    return $response->withJson($result, 200);
});

// LIST NOTIF ------------
$app->get("/list/notifications/{user}", function (Request $request, Response $response, $args){
    $user  = $args["user"];
    $sql = "
        SELECT 
        idData, judul, subjek, deskripsi, waktu, statusBaca,
        UNIX_TIMESTAMP(STR_TO_DATE(waktu, '%Y-%m-%d %H:%i:%s')) timestamp, 
        DATE_FORMAT(waktu, '%Y-%M-%d') tanggal, 
        DATE_FORMAT(waktu,'%H:%i') jam
        FROM dplega_901_notifications  
        WHERE targetUser =:user OR createdBy =:user
        ORDER BY statusBaca ASC, waktu DESC
    ";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":user" => $user]);
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/detail/notifications/{idData}", function (Request $request, Response $response, $args){
    $idData  = $args["idData"];

    $sql = "
        SELECT 
        idData, judul, subjek, deskripsi, waktu, statusBaca,
        UNIX_TIMESTAMP(STR_TO_DATE(waktu, '%Y-%m-%d %H:%i:%s')) timestamp, 
        DATE_FORMAT(waktu, '%Y-%M-%d') tanggal, 
        DATE_FORMAT(waktu,'%H:%i') jam,
        createdBy
        FROM dplega_901_notifications  
        WHERE idData =:idData 
    ";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":idData" => $idData]);
    $result = $stmt->fetch();

    if($result['statusBaca'] == '0'){
         $sql = "
            UPDATE dplega_901_notifications 
            SET `statusBaca`= '1'
            WHERE idData =:idData";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([":idData" => $idData]);
    }

    return $response->withJson($result, 200);
});

$app->get("/count/notifications/{user}", function (Request $request, Response $response, $args){
    $user  = $args["user"];
    $sql = "
        SELECT COUNT(*) as total
        FROM dplega_901_notifications  
        WHERE (targetUser =:user OR createdBy =:user) AND statusBaca = '0'
        ORDER BY statusBaca ASC, waktu DESC
    ";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":user" => $user]);
    $result = $stmt->fetch();
    return $response->withJson(intval($result['total']), 200);
});

// OPTION LIST ------------
$app->get("/option/bentuk-lembaga/", function (Request $request, Response $response, $args){

    $sql = "
        SELECT 
            kodeBentukLembaga as value,
            namaBentukLembaga as caption
        FROM 
            dplega_200_bentuklembaga 
        ORDER BY kodeBentukLembaga ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/option/bidang-gerak/", function (Request $request, Response $response, $args){

    $sql = "
        SELECT 
            kodeBidangGerak as value,
            namaBidangGerak as caption
        FROM 
            dplega_210_bidanggerak 
        ORDER BY namaBidangGerak ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/option/wilayah/", function (Request $request, Response $response, $args){

    $sql = "
        SELECT 
            idData as value,
            namaWilayah as caption
        FROM 
            dplega_101_wilayah 
        ORDER BY namaWilayah ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/option/kecamatan/{idWilayah}", function (Request $request, Response $response, $args){
    $idWilayah  = $args["idWilayah"];

    $sql = "
        SELECT 
            idData as value,
            namaKecamatan as caption
        FROM 
            dplega_102_kecamatan 
        WHERE idWilayah = '".$idWilayah."'
        ORDER BY namaKecamatan ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/option/kelurahan/{idKecamatan}", function (Request $request, Response $response, $args){
    $idKecamatan  = $args["idKecamatan"];

    $sql = "
        SELECT 
            idData as value,
            namaKelurahan as caption
        FROM 
            dplega_103_kelurahan 
        WHERE idKecamatan = '".$idKecamatan."'
        ORDER BY namaKelurahan ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

// SEARCH LIST -------------
$app->get("/search/{keyword}", function (Request $request, Response $response, $args){
    $keyword   = $args["keyword"];

    $sql = "
        SELECT * FROM(
            SELECT * FROM(
                SELECT 
                    'Lembaga' as grup,
                    noRegistrasi as kolom_1,
                    TRIM(LEADING ' ' FROM l.nama) as kolom_2,
                    bl.namaBentukLembaga as kolom_3,
                    COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as kolom_4,
                    '1' as kolom_5
                FROM dplega_000_lembaga l
                JOIN
                    dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                WHERE l.statusAktif = '1' AND (LOWER(l.nama) LIKE '%".strtolower($keyword)."%' OR LOWER(l.alamat) LIKE '%".strtolower($keyword)."%')
                LIMIT 10
            ) as lembaga_valid
            
            UNION
            SELECT * FROM(
                SELECT 
                    'Lembaga' as grup,
                    noRegistrasi as kolom_1,
                    TRIM(LEADING ' ' FROM l.nama) as kolom_2,
                    bl.namaBentukLembaga as kolom_3,
                    COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as kolom_4,
                    '0' as kolom_5
                FROM dplega_000_lembaga_temp l
                JOIN
                    dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                WHERE l.statusAktif = '1' AND (LOWER(l.nama) LIKE '%".strtolower($keyword)."%' OR LOWER(l.alamat) LIKE '%".strtolower($keyword)."%')
                LIMIT 10
            ) as lembaga_ajuan

            #koleksi ---------------------------------
            UNION
            SELECT * FROM(
                SELECT 
                    'Koleksi' as grup,
                    p.noRegistrasi as kolom_1,
                    TRIM(LEADING ' ' FROM l.nama) as kolom_2,
                    p.jenisKoleksi as kolom_3,
                    REPLACE(p.judulKoleksi, '-', '') as kolom_4,
                    c.namaBentukLembaga as kolom_5
                FROM 
                    dplega_005_koleksi p
                JOIN
                    dplega_000_lembaga l ON p.noRegistrasi = l.noRegistrasi
                JOIN dplega_200_bentuklembaga as c	ON l.kodeBentukLembaga = c.kodeBentukLembaga
                WHERE 
                    l.statusAktif = '1' AND p.judulKoleksi <> '-' AND p.judulKoleksi <> 'belum ada' AND p.judulKoleksi <> '' AND p.judulKoleksi <> 'Tidak ada' AND LOWER(p.judulKoleksi) <> 'buku'
                     AND (  
                            LOWER(l.nama) LIKE '%".strtolower($keyword)."%' OR 
                            LOWER(l.alamat) LIKE '%".strtolower($keyword)."%' OR
                            LOWER(p.judulKoleksi) LIKE '%".strtolower($keyword)."%'
                        )
                LIMIT 5
            ) as koleksi_valid

            UNION
            SELECT * FROM(
                SELECT 
                    'Koleksi' as grup,
                    p.noRegistrasi as kolom_1,
                    TRIM(LEADING ' ' FROM l.nama) as kolom_2,
                    p.jenisKoleksi as kolom_3,
                    REPLACE(p.judulKoleksi, '-', '') as kolom_4,
                    c.namaBentukLembaga as kolom_5
                FROM 
                    dplega_005_koleksi_temp p
                JOIN
                    dplega_000_lembaga_temp l ON p.noRegistrasi = l.noRegistrasi
                JOIN dplega_200_bentuklembaga as c	ON l.kodeBentukLembaga = c.kodeBentukLembaga
                WHERE 
                    l.statusAktif = '1' AND p.judulKoleksi <> '-' AND p.judulKoleksi <> 'belum ada' AND p.judulKoleksi <> '' AND p.judulKoleksi <> 'Tidak ada' AND LOWER(p.judulKoleksi) <> 'buku'
                    AND (  
                            LOWER(l.nama) LIKE '%".strtolower($keyword)."%' OR 
                            LOWER(l.alamat) LIKE '%".strtolower($keyword)."%' OR
                            LOWER(p.judulKoleksi) LIKE '%".strtolower($keyword)."%'
                        )
                LIMIT 5
            ) as koleksi_ajuan

            #prestasi ---------------------------------
            UNION
            SELECT * FROM(
                SELECT 
                    'Prestasi' as grup,
                    p.noRegistrasi as kolom_1,
                    l.nama as kolom_2,
                    REPLACE(p.deskripsi, '-', '') as kolom_3,
                    c.namaBentukLembaga as kolom_4,
                    '' as kolom_5
                FROM 
                    dplega_006_prestasi_temp p
                JOIN
                    dplega_000_lembaga_temp l ON p.noRegistrasi = l.noRegistrasi
                JOIN dplega_200_bentuklembaga as c	ON l.kodeBentukLembaga = c.kodeBentukLembaga
                WHERE 
                    l.statusAktif = '1' AND p.deskripsi <> '-' AND p.deskripsi <> 'belum ada' AND p.deskripsi <> ''
                    AND (  
                            LOWER(l.nama) LIKE '%".strtolower($keyword)."%' OR 
                            LOWER(l.alamat) LIKE '%".strtolower($keyword)."%' OR
                            LOWER(p.deskripsi) LIKE '%".strtolower($keyword)."%'
                        )
                LIMIT 5
            ) as prestasi_ajuan

            UNION
            SELECT * FROM(
                SELECT 
                    'Prestasi' as grup,
                    p.noRegistrasi as kolom_1,
                    l.nama as kolom_2,
                    REPLACE(p.deskripsi, '-', '') as kolom_3,
                    c.namaBentukLembaga as kolom_4,
                    '' as kolom_5
                FROM 
                    dplega_006_prestasi p
                JOIN
                    dplega_000_lembaga l ON p.noRegistrasi = l.noRegistrasi
                JOIN dplega_200_bentuklembaga as c	ON l.kodeBentukLembaga = c.kodeBentukLembaga
                WHERE 
                    l.statusAktif = '1' AND p.deskripsi <> '-' AND p.deskripsi <> 'belum ada' AND p.deskripsi <> ''
                    AND (  
                            LOWER(l.nama) LIKE '%".strtolower($keyword)."%' OR 
                            LOWER(l.alamat) LIKE '%".strtolower($keyword)."%' OR
                            LOWER(p.deskripsi) LIKE '%".strtolower($keyword)."%'
                        )
                LIMIT 5
            ) as prestasi_valid
        ) main_table
    ";
    
    // $result = $sql;
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});


// GALLERY
// ------------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------
//  normal select
$app->get("/list/lembaga/", function (Request $request, Response $response, $args){
    $sql = "
    SELECT * FROM (
        SELECT * FROM (
            SELECT 
                noRegistrasi,
                TRIM(LEADING ' ' FROM l.nama) as nama,
                bl.namaBentukLembaga,
                COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as urlGambarLogo,
                '1' as statusVerifikasi
            FROM dplega_000_lembaga l
            JOIN
                dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
        ) as table_1 
        UNION
        SELECT * FROM (
            SELECT 
                noRegistrasi,
                TRIM(LEADING ' ' FROM l.nama) as nama,
                bl.namaBentukLembaga,
                COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as urlGambarLogo,
                '0' as statusVerifikasi
            FROM dplega_000_lembaga_temp l
            JOIN
                dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
        ) as table_2 
    ) as main_table ORDER BY nama LIMIT  25";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

// select by id
$app->get("/books/{id}", function (Request $request, Response $response, $args){
    $id = $args["id"];
    $sql = "SELECT * FROM books WHERE book_id=:id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":id" => $id]);
    $result = $stmt->fetch();
    return $response->withJson(["status" => "success", "data" => $result], 200);
});


// ../books/serach?key=123&keyword=bukuphp
$app->get("/books/search/", function (Request $request, Response $response, $args){
    $keyword = $request->getQueryParam("keyword");
    $sql = "SELECT * FROM books WHERE title LIKE '%$keyword%' OR sinopsis LIKE '%$keyword%' OR author LIKE '%$keyword%'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result], 200);
});

// insert 
$app->post("/books/", function (Request $request, Response $response){

    $new_book = $request->getParsedBody();

    $sql = "INSERT INTO books (title, author, sinopsis) VALUE (:title, :author, :sinopsis)";
    $stmt = $this->db->prepare($sql);

    $data = [
        ":title" => $new_book["title"],
        ":author" => $new_book["author"],
        ":sinopsis" => $new_book["sinopsis"]
    ];

    if($stmt->execute($data))
       return $response->withJson(["status" => "success", "data" => "1"], 200);
    
    return $response->withJson(["status" => "failed", "data" => "0"], 200);
});

// update

$app->put("/books/{id}", function (Request $request, Response $response, $args){
    $id = $args["id"];
    $new_book = $request->getParsedBody();
    $sql = "UPDATE books SET title=:title, author=:author, sinopsis=:sinopsis WHERE book_id=:id";
    $stmt = $this->db->prepare($sql);
    
    $data = [
        ":id" => $id,
        ":title" => $new_book["title"],
        ":author" => $new_book["author"],
        ":sinopsis" => $new_book["sinopsis"]
    ];

    if($stmt->execute($data))
       return $response->withJson(["status" => "success", "data" => "1"], 200);
    
    return $response->withJson(["status" => "failed", "data" => "0"], 200);
});

//  delete
$app->delete("/books/{id}", function (Request $request, Response $response, $args){
    $id = $args["id"];
    $sql = "DELETE FROM books WHERE book_id=:id";
    $stmt = $this->db->prepare($sql);
    
    $data = [
        ":id" => $id
    ];

    if($stmt->execute($data))
       return $response->withJson(["status" => "success", "data" => "1"], 200);
    
    return $response->withJson(["status" => "failed", "data" => "0"], 200);
});


// Upload
$app->post('/books/cover/{id}', function(Request $request, Response $response, $args) {
    
    $uploadedFiles = $request->getUploadedFiles();
    
    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['cover'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        
        // ubah nama file dengan id buku
        $filename = sprintf('%s.%0.8s', $args["id"], $extension);
        
        $directory = $this->get('settings')['upload_directory'];
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        // simpan nama file ke database
        $sql = "UPDATE books SET cover=:cover WHERE book_id=:id";
        $stmt = $this->db->prepare($sql);
        $params = [
            ":id" => $args["id"],
            ":cover" => $filename
        ];
        
        if($stmt->execute($params)){
            // ambil base url dan gabungkan dengan file name untuk membentuk URL file
            $url = $request->getUri()->getBaseUrl()."/uploads/".$filename;
            return $response->withJson(["status" => "success", "data" => $url], 200);
        }
        
        return $response->withJson(["status" => "failed", "data" => "0"], 200);
    }
});



// TABAH
// ------------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------
//  Temporary
$app->get("/tabah/count/lembaga/", function (Request $request, Response $response, $args){
    
    $sql = "
        SELECT 
        b.kodeBentukLembaga,
        b.namaBentukLembaga,
        '0' as permohonanAwal,
        '0' as permohonanPencairan,
        '0' as pelaporan
        FROM
        dplega_200_bentuklembaga b
        ORDER BY kodeBentukLembaga ASC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->get("/tabah/search/{keyword}", function (Request $request, Response $response, $args){
    $keyword   = $args["keyword"];

    $sql = "
        SELECT * FROM(
            SELECT * FROM(
                SELECT 
                    'Lembaga' as grup,
                    noRegistrasi as kolom_1,
                    TRIM(LEADING ' ' FROM l.nama) as kolom_2,
                    bl.namaBentukLembaga as kolom_3,
                    COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as kolom_4,
                    '1' as kolom_5
                FROM dplega_000_lembaga l
                JOIN
                    dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                WHERE l.statusAktif = '1' AND (LOWER(l.nama) LIKE '%".strtolower($keyword)."%' OR LOWER(l.alamat) LIKE '%".strtolower($keyword)."%')
                LIMIT 10
            ) as lembaga_valid
            
            UNION
            SELECT * FROM(
                SELECT 
                    'Lembaga' as grup,
                    noRegistrasi as kolom_1,
                    TRIM(LEADING ' ' FROM l.nama) as kolom_2,
                    bl.namaBentukLembaga as kolom_3,
                    COALESCE(`urlGambarLogo`, CONCAT_WS('', 'avatar-', RIGHT(noRegistrasi,1) ,'.jpg')) as kolom_4,
                    '0' as kolom_5
                FROM dplega_000_lembaga_temp l
                JOIN
                    dplega_200_bentuklembaga bl ON l.kodeBentukLembaga = bl.kodeBentukLembaga
                WHERE l.statusAktif = '1' AND (LOWER(l.nama) LIKE '%".strtolower($keyword)."%' OR LOWER(l.alamat) LIKE '%".strtolower($keyword)."%')
                LIMIT 10
            ) as lembaga_ajuan
        ) main_table
    ";
    
    // $result = $sql;
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});

$app->post("/tabah/auth/", function (Request $request, Response $response){

    $credential = $request->getParsedBody();

    $sql = "
        SELECT 
            l.noRegistrasi,
            l.nama,
            l.jabatan,
            l.noTelp,
            l.email,
            l.username,
            l.alamat,
            l.noRt,
            l.noRw,
            l.kodeKelurahan,
            l.kodeKecamatan,
            l.kodeWilayah,
            COALESCE(l.`urlGambar`, CONCAT_WS('', 'avatar-', RIGHT(l.idData,1) ,'.jpg')) as urlGambar, 
            l.userLevel,
            l.lingkupArea,
            l.idBatasArea,
            CONCAT_WS(' ', l.`alamat`, 'RT/RW', COALESCE(l.`noRt`, '-'), '/', COALESCE(l.`noRw`, '-'), `namaKelurahan`, `namaKecamatan`, `namaWilayah`, `namaProvinsi`) as alamatLengkap
        FROM 
            dplega_910_user l
        LEFT JOIN
            dplega_100_provinsi p ON l.kodeProvinsi = p.idData
        LEFT JOIN
            dplega_101_wilayah w ON l.kodeWilayah = w.idData
        LEFT JOIN
            dplega_102_kecamatan kc ON l.kodeKecamatan = kc.idData
        LEFT JOIN
            dplega_103_kelurahan kl ON l.kodeKelurahan = kl.idData
        
        WHERE username=:username AND password=:password AND statusActive = 1 AND l.userLevel != '2'";
    $stmt = $this->db->prepare($sql);

    $data = [
        ":username" => $credential["username"],
        ":password" => md5($credential["password"])
    ];

    if($stmt->execute($data)){
        $result = $stmt->fetch();
        
        if($result['nama'] != ''){
            $result['namaLembaga'] = "";
            
            if($result['userLevel'] == '1'){
                $sql  = "SELECT nama FROM dplega_000_lembaga WHERE noRegistrasi = '".$result['noRegistrasi']."'";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $res  = $stmt->fetch();

                if($res['nama'] == ''){
                    $sql  = "SELECT nama FROM dplega_000_lembaga_temp WHERE noRegistrasi = '".$result['noRegistrasi']."'";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                    $res  = $stmt->fetch();
                }

                $result['namaLembaga'] = $res['nama'];
            }
        }


       return $response->withJson($result, 200);
    }
    
    return $response->withJson(["status" => "failed", "data" => '0'], 200);
});


$app->get("/tabah/detail/lembaga/legalitas-form/{noRegistrasi}", function (Request $request, Response $response, $args){
    $noRegistrasi  = $args["noRegistrasi"];

    // checking
    $dumbTable = '';
    $kodeBentukLembaga = '';
    $status = 'valid';
    $sql  = "SELECT noRegistrasi, kodeBentukLembaga FROM dplega_000_lembaga WHERE noRegistrasi = '".$noRegistrasi."'";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $res  = $stmt->fetch();

    if($res['noRegistrasi'] == ''){
        $dumbTable = '_temp';
        $status = 'ajuan';

        $sql  = "SELECT noRegistrasi, kodeBentukLembaga FROM dplega_000_lembaga_temp WHERE noRegistrasi = '".$noRegistrasi."'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $res  = $stmt->fetch();
    }

    $kodeBentukLembaga = $res['kodeBentukLembaga'];
    
    $sql = "
        SELECT 
            p.kodePersyaratan as kodePersyaratan,
            p.namaPersyaratan as namaPersyaratan,
            '' as noLegalitas,
            '' as tanggalLegalitas,
            '' as urlFile,
            '' as statusVerifikasi
        FROM
            dplega_201_persyaratan p
            
        WHERE
            p.kodeBentukLembaga = '".$kodeBentukLembaga."'
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson($result, 200);
});