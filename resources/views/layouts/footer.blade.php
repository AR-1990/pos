            </main>
        </div>
    </div>

    <div class="app-toasts" id="appToasts" aria-live="polite"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    window.APP = {
        currency: @json($globalSettings->currency ?? 'Rs'),
        salesUrl: @json(auth()->user()->hasPermission('sales', 'view') ? url('sales') : null),
        flash: {
            success: @json(session('success')),
            error: @json(session('error') ?? ($errors->any() ? $errors->first() : null)),
        },
    };
</script>
<script src="{{ asset('assets/js/custom.min.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}?v=6"></script>

@stack('scripts')

</body>

</html>
