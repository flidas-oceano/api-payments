## API Payments

Pertime gestionar pagos actualmente con Stripe

- [Documentacion del Framework (Laravel)](https://laravel.com).




## Requisitos para usar la api

### Establecer variables de entorno
Copiar `.env.example` y renombrarlo a `.env` luego agrege las siguientes variables al final del archivo, usando como valores las claves de Stripe

```
STRIPE_MX_PK_TEST="La public-key de la cuenta de Stripe Oceano Medicina MX (Modo Prueba)"
STRIPE_MX_SK_TEST="La secret-key de la cuenta de Stripe Oceano Medicina MX (Modo Prueba)"

STRIPE_MX_PK_PROD="La public-key de la cuenta de Stripe Oceano Medicina MX (Produccion)"
STRIPE_MX_SK_PROD="La secret-key de la cuenta de Stripe Oceano Medicina MX (Produccion)"


STRIPE_OCEANO_PK_TEST="La public-key de la cuenta de Stripe Oceano Medicina (Modo Prueba)"
STRIPE_OCEANO_SK_TEST="La secrect-key de la cuenta de Stripe Oceano Medicina (Modo Prueba)"

STRIPE_OCEANO_PK_PROD="La public-key de la cuenta de Stripe Oceano Medicina (Produccion)"
STRIPE_OCEANO_SK_PROD="La secret-key de la cuenta de Stripe Oceano Medicina (Produccion)"
```


- `php artisan serve`
Abre en localhost:8000 y permite comunicacion con sus servicios

