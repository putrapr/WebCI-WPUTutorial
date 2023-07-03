<?php

namespace App\Controllers;

use App\Models\KomikModel;

class Komik extends BaseController { 
  protected $komikModel;
  public function __construct() {
    $this->komikModel = new KomikModel();
    
  }

  public function index() {
    $data = [
      'title' => 'Daftar Komik',
      'komik' => $this->komikModel->getKomik()
    ];
    return view('komik/index', $data);
  }

  public function detail($slug) {
    $data = [
      'title' => 'Detail Komik',
      'komik' => $this->komikModel->getKomik($slug)
    ];

    // jika komik tidak ada di tabel
    if (empty($data['komik'])) {
      throw new \CodeIgniter\Exceptions\PageNotFoundException('Judul komik '.$slug.' tidak ditemukan.');
    }


    return view('komik/detail', $data);
  }

  public function create() {   
    $old = array(
      'judul' => '',
      'penulis' => '',
      'penerbit' => ''
    ); 
    $data = [
      'title' => 'Form Tambah Data Komik',
      'validation' => \Config\Services::validation(),
      'old' => $old
    ];
    return view('/komik/create', $data);
  }

  public function save() {
    $judul = $this->request->getVar('judul');
    $slug = url_title($judul, '-', true);
    $penulis = $this->request->getVar('penulis');
    $penerbit = $this->request->getVar('penerbit');

    $rules = [
      'judul' => [
        'rules' => 'required|is_unique[komik.judul]',
        'errors' => [
          'required' => 'Judul komik harus diisi',
          'is_unique' => 'Judul komik sudah terdaftar'
        ]
      ],
      'sampul' => [
        'rules' => 'max_size[sampul,1024]|is_image[sampul]|mime_in[sampul,image/jpg,image/jpeg,image/png]',
        'errors' => [
          'max_size' => 'Ukuran gambar terlalu besar',
          'is_image' => 'Yang anda pilih bukan gambar',
          'mime_in' => 'Yang anda pilh bukan gambar' 
        ]
      ]
    ];

    if (!$this->validate($rules)) {
      $old = array(
        'judul' => $judul,
        'penulis' => $penulis,
        'penerbit' => $penerbit
      );
      $data = [
        'title' => 'Form Tambah Data Komik',
        'validation' => $this->validator,
        'old' => $old
      ];
      return view('/komik/create', $data);
    }

    // ambil gambar
    $fileSampul = $this->request->getFile('sampul');
    // apakah tidak ada gambar yang diupload
    if ($fileSampul->getError() == 4) {
      $namaSampul = 'default.jpg';
    } else {
      // generate name sampul random
      $namaSampul = $fileSampul->getRandomName();
      // pindahkan file ke folder img
      $fileSampul->move('img', $namaSampul);
    }

    $this->komikModel->save([
      'judul' => $judul,
      'slug' => $slug,
      'penulis' => $penulis,
      'penerbit' => $penerbit,
      'sampul' => $namaSampul
    ]);

    session()->setFlashdata('pesan','Data berhasil ditambahkan.');
    return redirect()->to('/komik');
  }

  public function delete($id) {
    // cari gambar berdasarkan id
    $komik = $this->komikModel->find($id);

    // cek jika file gambarnya default.jpg
    if ($komik['sampul'] != 'default.jpg') {
      // hapus gambar
      unlink('img/'. $komik['sampul']);
    }

    $this->komikModel->delete($id);
    session()->setFlashdata('pesan', 'Data berhasil dihapus');
    return redirect()->to('/komik');
  }

  public function edit($slug) {
    $komiks = $this->komikModel->getKomik($slug);

    $old = array(
      'judul' => $komiks['judul'],
      'slug' => $komiks['slug'],
      'penulis' => $komiks['penulis'],
      'penerbit' => $komiks['penerbit'],
      'sampulLama' => $komiks['sampul'],
    );

    $data = [
      'title' => 'Form Ubah Data Komik',
      'validation' => \Config\Services::validation(),
      'old' => $old,
      'komik' => $komiks
    ];
    return view('/komik/edit', $data);
  }

  public function update($id) {
    // Dapatkan data
    $dataInput = array(
      'judulLama'=> $this->komikModel->getKomik($this->request->getVar('slug'))['judul'],
      'judul' => $this->request->getVar('judul'),
      'slug' => url_title($this->request->getVar('judul'), '-', true),
      'penulis' => $this->request->getVar('penulis'),
      'penerbit' => $this->request->getVar('penerbit'),
      'sampulLama' => $this->request->getVar('sampulLama'),
    );

    // Validasi
    if (!$this->validasiUpdate($dataInput)){
      $data = [
        'title' => 'Form Tambah Data Komik',
        'validation' => $this->validator,
        'old' => $dataInput,
        'komik' => $this->komikModel->getKomik($this->request->getVar('slug'))
      ];
      return view('/komik/edit', $data);
    }   

    $fileSampul = $this->request->getFile('sampul');
    // cek gambar, apakah tetap gambar lama
    if ($fileSampul->getError() == 4) {
      $namaSampul = $dataInput['sampulLama'];
    } else {
      // generate nama file random
      $namaSampul = $fileSampul->getRandomName();
      // pindahkan gambar
      $fileSampul->move('img', $namaSampul);
      // hapus file yang lama
      if ($dataInput['sampulLama'] != 'default.jpg') {
        unlink('img/' . $dataInput['sampulLama']);
      }
    }

    $this->komikModel->save([
      'id' => $id,
      'judul' => $dataInput['judul'],
      'slug' => $dataInput['slug'],
      'penulis' => $dataInput['penulis'],
      'penerbit' => $dataInput['penerbit'],
      'sampul' => $namaSampul
    ]);

    session()->setFlashdata('pesan','Data berhasil diubah.');

    return redirect()->to('/komik');
  }

  public function validasiUpdate($data){
    // Cek Judul
    if ($data['judulLama'] == $data['judul']) {
      $rule_judul = 'required';
    } else {
      $rule_judul = 'required|is_unique[komik.judul]';
    }

    $rules = [
      'judul' => [
        'rules' => $rule_judul,
        'errors' => [
          'required' => 'Judul komik harus diisi',
          'is_unique' => 'Judul komik sudah terdaftar'
        ]
      ],
      'sampul' => [
        'rules' => 'max_size[sampul,1024]|is_image[sampul]|mime_in[sampul,image/jpg,image/jpeg,image/png]',
        'errors' => [
          'max_size' => 'Ukuran gambar terlalu besar',
          'is_image' => 'Yang anda pilih bukan gambar',
          'mime_in' => 'Yang anda pilh bukan gambar' 
        ]
      ]      
    ];
    if (!$this->validate($rules)) {
      return false;
    }
    return true;    
  }
}
