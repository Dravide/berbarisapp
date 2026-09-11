{{-- Modal tutorial per halaman (khusus role Eventner) --}}
@php($help = auth()->user()?->role === 'Eventner' ? \App\Support\EventnerHelp::current() : null)
@if($help)
  <div class="modal fade" id="eventnerHelpModal" tabindex="-1" aria-labelledby="eventnerHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title d-flex align-items-center gap-2" id="eventnerHelpModalLabel">
            <i class="ti ti-help fs-6"></i> Cara menggunakan: {{ $help['title'] }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <p class="text-body-secondary">{{ $help['intro'] }}</p>
          <ol class="ps-4 d-grid gap-2 mb-0">
            @foreach($help['steps'] as $step)
              <li>{{ $step }}</li>
            @endforeach
          </ol>
          @if(!empty($help['tips']))
            <div class="alert alert-light-primary mt-4 mb-0">
              <strong class="d-flex align-items-center gap-1"><i class="ti ti-bulb"></i> Tips</strong>
              <ul class="ps-3 mb-0 mt-1">
                @foreach($help['tips'] as $tip)
                  <li>{{ $tip }}</li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Mengerti</button>
        </div>
      </div>
    </div>
  </div>
@endif
