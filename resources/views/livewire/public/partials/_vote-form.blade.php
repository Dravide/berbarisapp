{{-- Vote Form Header --}}
<div class="bg-gradient-to-r from-primary to-tertiary text-white px-5 py-4 border-b border-outline-variant/30">
    <h5 class="font-display text-sm font-bold text-white inline-flex items-center gap-1.5 mb-0">
        <i class="ti ti-heart-filled"></i>
        Formulir Dukungan Voting
    </h5>
</div>

{{-- Form Body --}}
<div class="p-5 font-sans" x-data>
    <form wire:submit.prevent="submitVote" class="flex flex-col gap-4">
        {{-- Vote Count Counter --}}
        <div>
            <label class="text-xs font-bold text-deep-slate block mb-1.5 uppercase tracking-wider">Jumlah Vote</label>
            <div class="flex max-w-[160px] border border-outline-variant/60 rounded-lg overflow-hidden h-11 bg-surface">
                <button type="button" x-on:click="$wire.voteCount = Math.max(1, Number($wire.voteCount || 0) - 1)" class="w-12 flex items-center justify-center font-bold text-lg text-primary hover:bg-primary/5 border-r border-outline-variant/60 transition cursor-pointer select-none">−</button>
                <input type="number" x-model="$wire.voteCount" class="flex-1 text-center font-bold text-sm text-deep-slate border-none outline-none h-full w-full bg-transparent px-1 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" min="1">
                <button type="button" x-on:click="$wire.voteCount = Number($wire.voteCount || 0) + 1" class="w-12 flex items-center justify-center font-bold text-lg text-primary hover:bg-primary/5 border-l border-outline-variant/60 transition cursor-pointer select-none">+</button>
            </div>

            {{-- Quick select buttons --}}
            <div class="grid grid-cols-4 gap-1.5 mt-2.5">
                <button type="button" x-on:click="$wire.voteCount = 10" class="chip py-1.5 text-center font-bold justify-center hover:bg-primary hover:text-white transition cursor-pointer select-none">10</button>
                <button type="button" x-on:click="$wire.voteCount = 50" class="chip py-1.5 text-center font-bold justify-center hover:bg-primary hover:text-white transition cursor-pointer select-none">50</button>
                <button type="button" x-on:click="$wire.voteCount = 100" class="chip py-1.5 text-center font-bold justify-center hover:bg-primary hover:text-white transition cursor-pointer select-none">100</button>
                <button type="button" x-on:click="$wire.voteCount = 500" class="chip py-1.5 text-center font-bold justify-center hover:bg-primary hover:text-white transition cursor-pointer select-none">500</button>
            </div>
            @error('voteCount') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Name --}}
        <div>
            <label class="text-xs font-bold text-deep-slate block mb-1.5 uppercase tracking-wider">Nama Lengkap</label>
            <input type="text" wire:model="voterName" placeholder="Contoh: Budi Santoso" class="field-input w-full">
            @error('voterName') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Email --}}
        <div>
            <label class="text-xs font-bold text-deep-slate block mb-1.5 uppercase tracking-wider">Email (Untuk Bukti Transaksi)</label>
            <input type="email" wire:model="voterEmail" placeholder="email@contoh.com" class="field-input w-full">
            @error('voterEmail') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Price Summary --}}
        <div class="bg-surface-container-low border border-outline-variant/40 rounded-xl p-3.5 mt-2">
            <div class="flex justify-between items-center text-xs font-semibold text-on-surface-variant mb-1.5">
                <span>Harga per Vote</span>
                <span>Rp {{ number_format($eventner->vote_price ?? 1000, 0, ',', '.') }}</span>
            </div>
            <div class="border-t border-outline-variant/30 pt-1.5 flex justify-between items-center">
                <span class="text-xs font-bold text-deep-slate">Total Pembayaran</span>
                <span class="text-base font-extrabold text-primary" x-text="'Rp ' + (Number($wire.voteCount || 0) * {{ $eventner->vote_price ?? 1000 }}).toLocaleString('id-ID')">Rp {{ number_format((int)$voteCount * ($eventner->vote_price ?? 1000), 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Submit button --}}
        <button type="submit"
            class="btn-primary py-3.5 px-6 font-bold text-sm w-full text-center inline-flex justify-center cursor-pointer shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed mt-2"
            {{ !$selectedRegistrationId ? 'disabled' : '' }}
            wire:loading.attr="disabled">
            <span wire:loading.remove>Lanjutkan Ke Pembayaran</span>
            <span wire:loading class="inline-flex items-center gap-2">
                <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                Memproses...
            </span>
        </button>

        @if(!$selectedRegistrationId)
            <div class="p-2.5 rounded-lg bg-red-500/5 border border-red-500/10 text-red-500 text-center text-xs font-semibold leading-normal">
                <i class="ti ti-alert-circle"></i> Silakan pilih kontingen/sekolah terlebih dahulu
            </div>
        @endif

        <div class="text-center mt-3 border-t border-outline-variant/30 pt-3">
            <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" class="h-5 opacity-60 mx-auto" alt="QRIS">
            <p class="text-[10px] text-on-surface-variant font-medium mt-1">Pembayaran aman dengan QRIS GoPay</p>
        </div>
    </form>
</div>
