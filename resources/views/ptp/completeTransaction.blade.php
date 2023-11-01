@extends('layouts.app')

@section('content')
     <div class="container">
        <h1 class="text-center">Listado de pagos de {{ $completeTransaction->reference }}</h1>
        <div class="row justify-content-center">
            <form action="{{ route('ptp.delete.sub', ['id' => $completeTransaction->id]) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger my-3">Cancelar Suscripcion</button>
            </form>
        </div>
        <div class="accordion" id="subscriptionAccordion">
            @php
                // Ordena las suscripciones primero por estado ('APPROVED' primero)
                $sortedSubscriptions = $completeTransaction->subscriptions->sort(function ($a, $b) {
                    if ($a->status === 'APPROVED' && $b->status !== 'APPROVED') {
                        return -1; // $a va antes que $b
                    } elseif ($a->status !== 'APPROVED' && $b->status === 'APPROVED') {
                        return 1; // $b va antes que $a
                    } elseif ($a->status === 'REJECTED' && $b->status !== 'REJECTED') {
                        return -1; // $a va antes que $b
                    } elseif ($a->status !== 'REJECTED' && $b->status === 'REJECTED') {
                        return 1; // $b va antes que $a
                    } else {
                        // Si ambos tienen el mismo estado, ordena por cuota ascendente
                        return $a->cuota - $b->cuota;
                    }
                });
            @endphp

            @foreach ($sortedSubscriptions as $key => $subscription)
                @php

                    $siguienteFechaPago = Carbon\Carbon::parse($subscription->date_to_pay);
                    $hoy = Carbon\Carbon::now();
                    // Define una variable para la clase CSS de la tarjeta (card)
                    $cardClass = '';

                    if ($siguienteFechaPago->isToday() && $subscription->status === null) {
                        $cardClass .= 'bg-warning '; // Fondo amarillo para la fecha de pago hoy
                    } else {
                        $cardClass .= 'bg-gray '; // Fondo gris para fechas de pago pasadas
                    }

                    if ($subscription->status === 'APPROVED') {
                        $cardClass .= 'bg-success'; // Fondo verde para Aprobado
                    } elseif ($subscription->status === 'REJECTED') {
                        $cardClass .= 'bg-danger'; // Fondo rojo para Rechazado
                    }
                @endphp

                <div class="{{ 'card text-white ' . $cardClass }}">
                    <div class="card-header" id="heading{{ $subscription->transactionId }}">
                        <h2 class="mb-0">
                            <button class="btn btn-link text-dark" type="button" data-toggle="collapse"
                                data-target="#collapse{{ $key }}" aria-expanded="true"
                                aria-controls="collapse{{ $key }}">
                                {{ $subscription->nro_quote }} -
                                {{ $subscription->reference ?? var_dump($subscription->reference) }} -
                                {{ $subscription->status ?? 'Pendiente de Cobro' }} -
                                {{ date('d-m-y', strtotime($subscription->date_to_pay)) }}
                            </button>
                        </h2>
                    </div>

                    <div id="collapse{{ $key }}" class="collapse"
                        aria-labelledby="heading{{ $subscription->transactionId }}" data-parent="#subscriptionAccordion">
                        <div class="card-body">

                            @if ($subscription->status === 'APPROVED')
                                <div class="alert alert-success" role="alert">
                                    Estado: Aprobado
                                </div>
                            @elseif ($subscription->status === 'REJECTED')
                                <div class="alert alert-danger" role="alert">
                                    Estado: Rechazado
                                </div>
                            @endif

                            <p><strong>Request ID:</strong> {{ $subscription->requestId }}</p>
                            <p><strong>Razón:</strong> {{ $subscription->reason }}</p>
                            <p><strong>Mensaje:</strong> {{ $subscription->message }}</p>
                            <p><strong>Pago:</strong> {{ $subscription->total }} {{ $subscription->currency }}</p>
                            <!-- Agrega aquí los demás atributos y sus valores -->
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
