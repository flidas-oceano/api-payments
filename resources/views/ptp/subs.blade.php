@extends('layouts.app')

@section('content')
    @dump($transactions)

    <div class="container">
        <h1 class="text-center mb-4">Listado de Suscripciones PTP</h1>
        <div class="row">
            @foreach ($transactions as $transaction)
                @php
                    $nextPayment = $transaction->subscriptions
                        ->filter(function ($sub) {
                            return $sub->status === null;
                        })
                        ->first();
                    $hasPayment = !is_null($nextPayment);
                    $nextPaymentText = $hasPayment ? date('d/m/y', strtotime($nextPayment->date_to_pay)) : 'No hay pagos pendientes';
                @endphp
                <div class="col-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            Detalles de la Transacción <a
                                href="ptp/{{ $transaction->reference }}"><strong>{{ $transaction->reference }}</strong></a> -
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
                                <a href="ptp/{{ $transaction->reference }}/renew" class="btn btn-info"> Renovar
                                    Suscripcion</a>
                            @elseif($transaction->status === 'RENEW')
                                <a href="ptp/{{ $transaction->reference }}" class="btn btn-primary">
                                    Ver pagos
                                </a>

                                <a href="http://localhost:3000/#/ptp/{{ $transaction->reference }}/renew" target="_blank"
                                    class="btn btn-success">
                                    Activar Suscripcion
                                </a>
                            @else
                                <a href="ptp/{{ $transaction->reference }}" class="btn btn-primary">
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
