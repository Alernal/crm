<x-app-layout>
<x-slot name="title">{{ $document->documentType->label }} {{ $document->full_number }}</x-slot>

@include('documents._document-show', ['routePrefix' => 'documents.contracts', 'showCommunication' => true])

</x-app-layout>
