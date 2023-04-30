@php
    use Illuminate\Support\Str;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container card-deck">
        @foreach ($elements as $element)
            <div class="card mb-3">
                <div class="card-header">
                    {{ $element->type }} - SO Number: {{ $element->so_number }}
                </div>
                <div class="card-body">
                    <p class="card-text">{{ Str::limit($element->data, 100) }}</p>
                </div>
                <div class="card-footer">
                    <a href="#" class="card-link" data-toggle="modal" data-target="#elementModal{{ $element->id }}">View
                        Data</a>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="elementModal{{ $element->id }}" tabindex="-1" role="dialog" <div
                class="modal fade" id="dataModal{{ $element->id }}" tabindex="-1" role="dialog"
                aria-labelledby="dataModalLabel{{ $element->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="dataModalLabel{{ $element->id }}">Data for SO#
                                {{ $element->so_number }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <pre style="max-height: 400px; overflow-y: auto;">{{ json_encode($element->data, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
@section('scripts')
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script>
        $(document).ready(function() {
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('form')[0].reset();
            });
        });
    </script>
@endsection
