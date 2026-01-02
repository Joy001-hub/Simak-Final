<!-- Cancellation Modal -->
<div id="cancelModal" class="modal-backdrop">
    <div class="modal-card" style="max-width: 480px; padding: 28px;">
        <div class="modal-header" style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div
                style="width: 44px; height: 44px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="15" y1="9" x2="9" y2="15" />
                    <line x1="9" y1="9" x2="15" y2="15" />
                </svg>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #111827;">Batalkan Penjualan</h2>
                <p style="margin: 0; font-size: 13px; color: #6b7280;">Pilih jenis pembatalan transaksi</p>
            </div>
        </div>

        <form action="{{ route('penjualan.cancel', $sale) }}" method="POST" id="cancelForm">
            @csrf
            <div class="cancel-options">
                <label class="cancel-option">
                    <input type="radio" name="type" value="refund" required>
                    <div class="cancel-option-content">
                        <div class="cancel-option-icon" style="background: #ecfdf5;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669"
                                stroke-width="2">
                                <path
                                    d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83" />
                            </svg>
                        </div>
                        <div class="cancel-option-text">
                            <strong>Refund</strong>
                            <span>Kembalikan dana ke pembeli</span>
                        </div>
                        <div class="cancel-option-check">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="3">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>
                        </div>
                    </div>
                </label>

                <label class="cancel-option">
                    <input type="radio" name="type" value="oper_kredit">
                    <div class="cancel-option-content">
                        <div class="cancel-option-icon" style="background: #fef3c7;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706"
                                stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <div class="cancel-option-text">
                            <strong>Oper Kredit</strong>
                            <span>Alihkan ke pembeli baru</span>
                        </div>
                        <div class="cancel-option-check">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="3">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>
                        </div>
                    </div>
                </label>
            </div>

            <div id="refundInput" class="cancel-extra-field hidden">
                <label class="hint">Nominal Refund (Rp)</label>
                <input type="text" name="refund_amount" class="input currency-input"
                    placeholder="Masukkan nominal refund">
            </div>

            <div id="operKreditInput" class="cancel-extra-field hidden">
                <div class="field" style="margin-bottom: 12px;">
                    <label class="hint">Pilih Pelanggan Baru <span style="color:#dc2626;">*</span></label>
                    <select name="new_buyer_id" class="input" id="newBuyerSelect">
                        <option value="">-- Pilih Pelanggan --</option>
                        @foreach($buyers as $buyer)
                            <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="hint">Pilih Marketing (Opsional)</label>
                    <select name="new_marketer_id" class="input">
                        <option value="">-- Pilih Marketing --</option>
                        @foreach($marketers as $marketer)
                            <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn light" id="closeCancelModal">Batal</button>
                <button type="submit" class="btn danger">Proses Pembatalan</button>
            </div>
        </form>
    </div>
</div>

<style>
    .cancel-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .cancel-option {
        cursor: pointer;
    }

    .cancel-option input {
        display: none;
    }

    .cancel-option-content {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        transition: all 0.2s ease;
        background: #fff;
    }

    .cancel-option-content:hover {
        border-color: #d1d5db;
        background: #f9fafb;
    }

    .cancel-option input:checked+.cancel-option-content {
        border-color: #b4232a;
        background: #fef2f2;
    }

    .cancel-option-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .cancel-option-text {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .cancel-option-text strong {
        font-size: 14px;
        font-weight: 600;
        color: #111827;
    }

    .cancel-option-text span {
        font-size: 12px;
        color: #6b7280;
    }

    .cancel-option-check {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: transparent;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .cancel-option input:checked+.cancel-option-content .cancel-option-check {
        background: #b4232a;
        border-color: #b4232a;
        color: #fff;
    }

    .cancel-extra-field {
        margin-top: 16px;
        padding: 16px;
        background: #f9fafb;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }

    .cancel-extra-field.hidden {
        display: none;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 24px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }

    .btn.danger {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: #fff;
        border: none;
        box-shadow: 0 4px 14px rgba(185, 28, 28, 0.25);
    }

    .btn.danger:hover {
        background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
    }
</style>