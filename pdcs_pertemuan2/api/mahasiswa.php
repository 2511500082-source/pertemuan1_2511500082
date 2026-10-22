<?php

require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        
      //Get by id
//mahasiswa.php?id=1
if(isset($_GET['id'])) {
    $id = $_GET['id'];

    $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j on m.jurusan_id = j.id WHERE m.id = $id";

    $r = mysqli_query($koneksi, $q);

    if(!$r) {
        sendResponse(
            false,
            "Query gagal : " . mysqli_error($koneksi),
            null,
            500
        );

    }

    $data = mysqli_fetch_assoc($r);

    if(!$data) {
        sendResponse(
            false,
            "Mahasiswa tidak ditemukan",
            null,
            404
        );
    }

    sendResponse(
        true,
        "Berhasil", 
        $data,
        200
    );

}

//search
//api/mahasiswa.php?search=a
if(isset($_GET['search'])) {
    $search = $_GET['search'];

    $q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j on m.jurusan_id = j.id WHERE m.nama LIKE '%$search%' OR m.nim LIKE '%$search%' ORDER BY m.id DESC";

    $r = mysqli_query($koneksi, $q);

    if(!$r) {
        sendResponse(
            false,
            "Query gagal : " . mysqli_error($koneksi),
            null,
            500
        );

    }

     $data = [];
        while($row = mysqli_fetch_assoc($r)) {
            $data[] = $row;
        }

    sendResponse(
        true,
        "Berhasil", 
        $data,
        200
    );

}

//limit&offset
//pagination
//mahasiswa.php?limit=2
//mahasiswa.php?page=2
if(isset($_GET['page']) || isset($_GET['limit'])) {

    $page = isset($_GET['page'])
        ? (int)$_GET['page']
        : 1;

    $limit = isset($_GET['limit'])
        ? (int)$_GET['limit']
        : 10;

if($page < 1) $page = 1;

if($limit < 1) $limit = 10;

$offset = ($page - 1) * $limit;

$q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j on m.jurusan_id = j.id ORDER BY m.id DESC LIMIT $limit OFFSET $offset";

$r = mysqli_query($koneksi, $q);

if(!$r) {
    sendResponse(
        false,
        "Query gagal : " . mysqli_error($koneksi),
        null,
        500
    );

}

$data = [];
    while($row = mysqli_fetch_assoc($r)) {
        $data[] = $row;
    }

sendResponse(
    true,
    "Berhasil", 
    $data,
    200
);

    $offset = ($page - 1) * $limit;
}
// get All
$q = "SELECT m.id, m.nama, m.nim, j.nama_jurusan AS jurusan FROM mahasiswa m LEFT JOIN jurusan j on m.jurusan_id = j.id ORDER BY m.id DESC";


$r = mysqli_query($koneksi, $q);

if(!$r) {
    sendResponse(
        false,
        "Query gagal : " . mysqli_error($koneksi),
        null,
        500
    );

}

 $data = [];
    while($row = mysqli_fetch_assoc($r)) {
        $data[] = $row;
    }

sendResponse(
    true,
    "Berhasil", 
    $data,
    200
);  
        break;
    case 'POST':

        $input = json_decode(file_get_contents('php://input'), true);

        // cek apakah input ada dan field nama, nim, jurusan_id ada
        if (!$input || !isset($input['nama']) || !isset($input['nim']) || !isset($input['jurusan_id'])) {
            sendResponse(false, "field nama, nim, dan jurusan_id wajib", null, 400);
        }

        $nama = $input['nama'];
        $nim = $input['nim'];
        $jurusan_id = (int) $input['jurusan_id'];

        //cek jurusan apakah ada
        $cek = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id='$jurusan_id'");
        if (mysqli_num_rows($cek) == 0) {
            sendResponse(false, "Jurusan_id tidak ditemukan", null, 404);
        }

        // cek nim apakah unik atau tidak
        $cekNim = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE nim='$nim'");
        if (mysqli_num_rows($cekNim) > 0) {
            sendResponse(false, "nim sudah terdaftar", null, 400);
        }

        // insert data
        $q = "INSERT INTO mahasiswa (nama, nim, jurusan_id, created_at) VALUES ('$nama', '$nim', '$jurusan_id', CURDATE())";
        
        if (mysqli_query($koneksi, $q)) {
            sendResponse(true, "data tersimpan", ["id" => mysqli_insert_id($koneksi)], 201);
        } else {
            sendResponse(false,mysqli_error($koneksi), null, 500);
        }
        break;

    //put & delete
   case 'PUT':

        $input = json_decode(file_get_contents("php://input"), true);

        if (
            !$input ||
            !isset($input['nama']) ||
            !isset($input['nim']) ||
            !isset($input['jurusan_id']) ||
            !isset($_GET['id'])
        ) {
            sendResponse(
                false,
                "field id, nama, nim, jurusan_id wajib",
                null,
                400
            );
        }

        $id = (int) $_GET['id'];
        $nama = mysqli_real_escape_string($koneksi, $input['nama']);
        $nim = mysqli_real_escape_string($koneksi, $input['nim']);
        $jurusan_id = (int) $input['jurusan_id'];

        // Cek mahasiswa berdasarkan ID
        $cek_mahasiswa = mysqli_query(
            $koneksi,
            "SELECT id FROM mahasiswa WHERE id='$id'"
        );

        if (!$cek_mahasiswa) {
            sendResponse(false, mysqli_error($koneksi), null, 500);
        }

        if (mysqli_num_rows($cek_mahasiswa) == 0) {
            sendResponse(false, "Mahasiswa tidak ditemukan", null, 404);
        }

        // Cek jurusan
        $cek = mysqli_query(
            $koneksi,
            "SELECT id FROM jurusan WHERE id='$jurusan_id'"
        );

        if (!$cek) {
            sendResponse(false, mysqli_error($koneksi), null, 500);
        }

        if (mysqli_num_rows($cek) == 0) {
            sendResponse(false, "jurusan_id tidak ditemukan", null, 404);
        }

        // Cek NIM agar tidak dipakai mahasiswa lain
        $cekNim = mysqli_query(
            $koneksi,
            "SELECT id FROM mahasiswa
             WHERE nim='$nim' AND id!='$id'"
        );

        if (!$cekNim) {
            sendResponse(false, mysqli_error($koneksi), null, 500);
        }

        if (mysqli_num_rows($cekNim) > 0) {
            sendResponse(false, "nim sudah terdaftar", null, 409);
        }

        // Update mahasiswa
        $q = "UPDATE mahasiswa
              SET nama='$nama', 
                  nim='$nim',
                  jurusan_id='$jurusan_id'
              WHERE id='$id'";

        if (mysqli_query($koneksi, $q)) {
            sendResponse(true, "data berhasil diperbarui", ["id" => $id], 200);
        } else {
            sendResponse(false, mysqli_error($koneksi), null, 500);
        }

        break;
}
