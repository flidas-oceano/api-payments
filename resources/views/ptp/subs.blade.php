@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="text-center mb-4">Listado de Suscripciones PTP</h1>

        <!-- Formulario de búsqueda -->
        <form action="{{ route('ptp.search.transactions') }}" method="GET" class="mb-4" >
            <div class="input-group">
                <input type="text" name="ref" class="form-control" placeholder="Buscar por referencia">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </form>

        <div class="row">

            @if ($transactions->isEmpty())
            <div class="col-12">
                <div class="alert alert-danger text-center" role="alert">
                    <strong>¡Error!</strong> No se encontraron transacciones.
                </div>
            </div>

            @endif

            @if (session('success'))
            <div class="col-12">
                <div class="alert alert-success text-center" role="alert">
                   {!! session('success') !!}
                </div>
            </div>

            @endif

            @foreach ($transactions as $transaction)
                @php
                    $nextPayment = $transaction->subscriptions
                        ->filter(function ($sub) {
                            return $sub->status === null;
                        })->first();
                    $hasPayment = !is_null($nextPayment);
                    $nextPaymentText = $hasPayment ? date('d/m/y', strtotime($nextPayment->date_to_pay)) : 'No hay pagos pendientes';
                @endphp
                <div class="col-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            Detalles de la Transacción <a
                                href="/ptp/{{ $transaction->reference }}"><strong>{{ $transaction->reference }}</strong></a> -
                            {{ $transaction->status }}
                        </div>
                        <div class="card-body">
                            <strong>Realizado {{ date('d/m/y H:i:s a', strtotime($transaction->date)) }}</strong>
                            <p>Mensaje: {{ $transaction->message }}</p>
                            <p>Cuotas: {{ $transaction->quotes }}</p>
                            <p>Pagos realizados: {{ $transaction->installments_paid }}</p>
                            <p>Proximo cobro:
                                {{ $nextPaymentText }}
                            </p>
                        </div>
                        <div class="card-footer">

                            @if ($transaction->status === 'SUSPEND')
                                <a href="/ptp/{{ $transaction->reference }}/renew" class="btn btn-info"> Renovar
                                    Suscripcion</a>
                            @elseif($transaction->status === 'RENEW')
                                <a href="/ptp/{{ $transaction->reference }}" class="btn btn-primary">
                                    Ver pagos
                                </a>

                                <a href="http://localhost:3000/#/ptp/{{ $transaction->reference }}/renew" target="_blank"
                                    class="btn btn-success">
                                    Activar Suscripcion
                                </a>
                            @else
                                <a href="/ptp/{{ $transaction->reference }}" class="btn btn-primary">
                                    Ver pagos
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
