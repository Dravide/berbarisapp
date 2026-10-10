@extends('errors.layout')

@section('title', 'Halaman tidak ditemukan')
@section('kelas', 'not-found')

@section('isi')
    <div class="kode-utama" style="color: #0f6bde;">404</div>
    <h1 class="judul">Halaman tidak ditemukan</h1>
    <p class="teks">Alamat yang kamu tuju tidak ada atau sudah dipindahkan.</p>

    <div class="tombol-wrap">
        <a href="{{ url('/') }}" class="tombol tombol-utama">Ke Beranda</a>
        <a href="javascript:history.back()" class="tombol tombol-netral">Kembali</a>
    </div>
@endsection
