{{-- Vote Form Header --}}
<div style="background: linear-gradient(135deg, var(--event-primary, #0072FF), var(--event-accent, #00D4AA)); padding: 16px 20px;">
    <h5 style="margin: 0; color: #fff; font-weight: 600; font-size: 16px;">
        <i class="fa fa-heart" style="margin-right: 6px;"></i>Form Voting
    </h5>
</div>

{{-- Form Body --}}
<div style="padding: 20px;">
    <form wire:submit.prevent="submitVote">
        {{-- Vote Count --}}
        <div style="margin-bottom: 18px;">
            <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Jumlah Vote</label>
            <div style="display: flex;">
                <button type="button" x-on:click="$wire.voteCount = Math.max(1, Number($wire.voteCount) - 1)" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 10px 0 0 10px; width: 48px; height: 48px; cursor: pointer; font-size: 20px; color: var(--event-primary, #0072FF); display: flex; align-items: center; justify-content: center;">−</button>
                <input type="number" wire:model.live="voteCount" style="border: 1px solid #e5e7eb; border-left: none; border-right: none; text-align: center; font-weight: 700; font-size: 18px; width: 100%; padding: 10px; outline: none; height: 48px;" min="1">
                <button type="button" x-on:click="$wire.voteCount = Number($wire.voteCount) + 1" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 0 10px 10px 0; width: 48px; height: 48px; cursor: pointer; font-size: 20px; color: var(--event-primary, #0072FF); display: flex; align-items: center; justify-content: center;">+</button>
            </div>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-top: 8px;">
                <button type="button" wire:click="$set('voteCount', 10)" style="background: rgba(0,114,255,0.06); border: 1px solid rgba(0,114,255,0.15); border-radius: 8px; padding: 6px; cursor: pointer; color: var(--event-primary, #0072FF); font-weight: 700; font-size: 13px;">10</button>
                <button type="button" wire:click="$set('voteCount', 50)" style="background: rgba(0,114,255,0.06); border: 1px solid rgba(0,114,255,0.15); border-radius: 8px; padding: 6px; cursor: pointer; color: var(--event-primary, #0072FF); font-weight: 700; font-size: 13px;">50</button>
                <button type="button" wire:click="$set('voteCount', 100)" style="background: rgba(0,114,255,0.06); border: 1px solid rgba(0,114,255,0.15); border-radius: 8px; padding: 6px; cursor: pointer; color: var(--event-primary, #0072FF); font-weight: 700; font-size: 13px;">100</button>
                <button type="button" wire:click="$set('voteCount', 500)" style="background: rgba(0,114,255,0.06); border: 1px solid rgba(0,114,255,0.15); border-radius: 8px; padding: 6px; cursor: pointer; color: var(--event-primary, #0072FF); font-weight: 700; font-size: 13px;">500</button>
            </div>
            @error('voteCount') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
        </div>

        {{-- Name --}}
        <div style="margin-bottom: 14px;">
            <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 14px;">Nama Lengkap</label>
            <input type="text" wire:model="voterName" placeholder="Contoh: Budi Santoso" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; font-size: 15px; outline: none; min-height: 48px;">
            @error('voterName') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
        </div>

        {{-- Email --}}
        <div style="margin-bottom: 18px;">
            <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 14px;">Email (Untuk Bukti)</label>
            <input type="email" wire:model="voterEmail" placeholder="email@contoh.com" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; font-size: 15px; outline: none; min-height: 48px;">
            @error('voterEmail') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
        </div>

        {{-- Summary --}}
        <div style="background: #f8fafc; border-radius: 12px; padding: 14px; margin-bottom: 18px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <span style="color: #6b7280; font-size: 14px;">Harga per Vote</span>
                <span style="font-weight: 600; font-size: 14px;">Rp 1.000</span>
            </div>
            <div style="border-top: 1px solid #e5e7eb; padding-top: 6px; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; font-size: 15px;">Total Bayar</span>
                <span style="font-weight: 800; color: var(--event-primary, #0072FF); font-size: 20px;">Rp {{ number_format((int)$voteCount * 1000, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit"
            class="zubuz-default-btn"
            style="width: 100%; height: 50px; font-size: 16px; {{ !$selectedRegistrationId ? 'opacity: 0.5; cursor: not-allowed;' : '' }}"
            {{ !$selectedRegistrationId ? 'disabled' : '' }}
            wire:loading.attr="disabled">
            <span wire:loading.remove>Lanjutkan Ke Pembayaran</span>
            <span wire:loading>
                <span style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid #fff; border-radius: 50%; animation: spin 0.6s linear infinite;"></span>
                Memproses...
            </span>
        </button>

        @if(!$selectedRegistrationId)
        <p style="text-align: center; color: #ef4444; font-size: 13px; margin-top: 8px;">
            <i class="fa fa-exclamation-circle"></i> Pilih kontingen terlebih dahulu
        </p>
        @endif

        <div style="text-align: center; margin-top: 14px;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" height="20" alt="QRIS" style="opacity: 0.7;">
            <p style="color: #9ca3af; font-size: 11px; margin-top: 4px;">Pembayaran aman via QRIS GoPay</p>
        </div>
    </form>
</div>
