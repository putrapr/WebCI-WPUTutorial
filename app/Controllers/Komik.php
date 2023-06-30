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
    $validation = (isset(session()->validation))? session()->validation : \Config\Services::validation();
    $data = [
      'title' => 'Form Tambah Data Komik',
      'validation' => $validation
    ];
    unset(session()->validation);
    return view('/komik/create', $data);
  }

  public function save() {
    $rules = [
      'judul' => [
        'rules' => 'required|is_unique[komik.judul]',
        'errors' => [
          'required' => 'Judul komik harus diisi',
          'is_unique' => 'Judul komik sudah terdaftar'
        ]
      ]
      // ,
      // 'sampul' => [
      //   'rules' => 'uploaded[sampul]|max_size[sampul,1024]|is_image[sampul]|mime_in[sampul,image/jpg,image/jpeg,image/png]',
      //   'errors' => [
      //     'uploaded' => 'Pilih gambar sampul terlebih dahulu',
      //     'max_size' => 'Ukuran gambar terlalu besar',
      //     'is_image' => 'Yang anda pilih bukan gambar',
      //     'mime_in' => 'Yang anda pilh bukan gambar' 
      //   ]
      // ]

      //, 'sampul' => 'uploaded[sampul]|max_size[sampul,1024]|is_image[sampul]|mime_in[sampul,image/jpg,image/jpeg,image/png]'
      , 'sampul' => 'uploaded[sampul]'
    ];

    if (!$this->validate($rules)) {      
      session()->setFlashdata('validation', $this->validator);
      // dd(session()->validation);
      return redirect()->to('/komik/create')->withInput();
    }

    $slug = url_title($this->request->getVar('judul'), '-', true);
    $this->komikModel->save([
      'judul' => $this->request->getVar('judul'),
      'slug' => $slug,
      'penulis' => $this->request->getVar('penulis'),
      'penerbit' => $this->request->getVar('penerbit'),
      'sampul' => $this->request->getVar('sampul')
    ]);

    session()->setFlashdata('pesan','Data berhasil ditambahkan.');

    return redirect()->to('/komik');
  }

  public function delete($id) {
    $this->komikModel->delete($id);
    session()->setFlashdata('pesan', 'Data berhasil dihapus');
    return redirect()->to('/komik');
  }

  public function edit($slug) {
    $validation = (isset(session()->validation))? session()->validation : \Config\Services::validation() ;
    $data = [
      'title' => 'Form Ubah Data Komik',
      'validation' => $validation, 
      'komik' => $this->komikModel->getKomik($slug)
    ];
    unset(session()->validation);
    return view('/komik/edit', $data);
  }

  public function update($id) {
    // cek judul
    $komikLama = $this->komikModel->getKomik($this->request->getVar('slug'));
    if ($komikLama['judul'] == $this->request->getVar('judul')) {
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
      ]
    ];

    if (!$this->validate($rules)) {
      session()->setFlashdata('validation', $this->validator);
      return redirect()->to('/komik/edit/'. $this->request->getVar('slug'))->withInput();
    }


    $slug = url_title($this->request->getVar('judul'), '-', true);
    $this->komikModel->save([
      'id' => $id,
      'judul' => $this->request->getVar('judul'),
      'slug' => $slug,
      'penulis' => $this->request->getVar('penulis'),
      'penerbit' => $this->request->getVar('penerbit'),
      'sampul' => $this->request->getVar('sampul')
    ]);

    session()->setFlashdata('pesan','Data berhasil diubah.');

    return redirect()->to('/komik');
  }
}
