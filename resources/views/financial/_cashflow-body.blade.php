@foreach($rows as $row)
    @include('financial._cashflow-row', ['row' => $row, 'periodLabels' => $periodLabels])
@endforeach
