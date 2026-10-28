{{-- Barcode input with live preview. Empty = generated on save (in-store EAN-13).
     Params: $value (current barcode), $productId (null on create). --}}
<label class="form-label" for="barcodeInput">Barcode</label>
<div class="input-group">
    <span class="input-group-text bg-white"><i class="fa-solid fa-barcode text-primary"></i></span>
    <input type="text" class="form-control @error('barcode') is-invalid @enderror" name="barcode" id="barcodeInput"
           value="{{ $value }}" placeholder="Scan the product's barcode, or leave empty" autocomplete="off" maxlength="48">
    @if($value)
        <button type="button" class="btn btn-outline-secondary" id="barcodeClear" title="Clear to generate a new in-store barcode on save"><i class="fa-solid fa-rotate"></i></button>
    @endif
    @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="barcode-preview mt-2" id="barcodePreview" aria-live="polite">
    <svg id="barcodeSvg" role="img" aria-label="Barcode preview"></svg>
    <div class="barcode-preview__hint" id="barcodeHint"></div>
    @if($productId)
        <a href="{{ route('products.labels', ['ids' => $productId]) }}" class="small fw-semibold"><i class="fa-solid fa-print me-1"></i>Print labels</a>
    @endif
</div>

@once
@push('scripts')
<style>
    .barcode-preview { display: flex; flex-direction: column; align-items: flex-start; gap: .25rem; padding: .6rem .75rem; border: 1.5px dashed var(--line); border-radius: 12px; background: #fff; min-height: 64px; }
    .barcode-preview svg { max-width: 100%; height: 56px; }
    .barcode-preview__hint { font-size: .75rem; color: var(--muted); }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
    (function () {
        var input = document.getElementById('barcodeInput');
        var svg = document.getElementById('barcodeSvg');
        var hint = document.getElementById('barcodeHint');

        // Valid 13-digit codes print as EAN-13 (best for scanners), anything else as CODE128
        function isEan13(v) {
            if (!/^\d{13}$/.test(v)) { return false; }
            var sum = 0;
            for (var i = 0; i < 12; i++) { sum += +v[i] * (i % 2 ? 3 : 1); }
            return (10 - sum % 10) % 10 === +v[12];
        }

        function render() {
            var v = input.value.trim();
            if (!v) {
                svg.style.display = 'none';
                hint.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles me-1"></i>A scannable barcode will be created automatically when you save.';
                return;
            }
            try {
                JsBarcode(svg, v, { format: isEan13(v) ? 'EAN13' : 'CODE128', height: 46, fontSize: 13, margin: 0, width: 1.6 });
                svg.style.display = '';
                hint.textContent = isEan13(v) ? 'EAN-13' : 'Code 128';
            } catch (e) {
                svg.style.display = 'none';
                hint.textContent = 'This value cannot be printed as a barcode.';
            }
        }

        // A scanner types the code then presses Enter: keep that from submitting the form
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); render(); }
        });
        input.addEventListener('input', render);
        var clear = document.getElementById('barcodeClear');
        if (clear) {
            clear.addEventListener('click', function () { input.value = ''; render(); input.focus(); });
        }
        render();
    })();
</script>
@endpush
@endonce
