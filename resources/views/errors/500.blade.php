@extends('errors.layout')

@section('title', 'Terjadi Kesalahan')
@section('kelas', 'serius')

@section('isi')
    <div class="kotak-aksen" style="padding-top: 20px;">
        <h1 class="judul">Terjadi Kesalahan</h1>
        <p class="teks">Kami mohon maaf, terjadi kesalahan di sisi server kami.</p>

        <div class="kotak-kode">
            <div class="label">Kode Error</div>
            <div class="nilai">{{ $errorCode }}</div>
        </div>
        <p class="teks" style="font-size: 13px;">Sertakan kode di atas saat menghubungi bantuan.</p>
    </div>

    <div class="tombol-wrap">
        <a href="{{ url()->previous() ?: url('/') }}" class="tombol tombol-utama" style="background: #c0392b;">Coba Lagi</a>
    </div>
@endsection
