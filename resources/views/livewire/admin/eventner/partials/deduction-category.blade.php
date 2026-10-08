{{-- Satu kategori pengurangan: nama + tabel kriterianya beserta opsi potongan --}}
<div class="mb-2">
    <div class="fw-medium mb-1">{{ $deductionCat->name }}</div>
    @if($deductionCat->criterias->isNotEmpty())
        <table class="table table-bordered table-sm mb-0">
            <tbody>
                @foreach($deductionCat->criterias as $deductionCrit)
                    <tr>
                        <td width="40%" class="fw-medium align-middle text-danger">{{ $deductionCrit->name }}</td>
                        <td width="60%" class="text-center align-middle">
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                @foreach($deductionCrit->deduction_options ?? [] as $option)
                                    <span class="badge bg-danger-subtle text-danger">{{ $option }}</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
