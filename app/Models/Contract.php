<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'entity_id_crm',
        'installments',
        'Fecha_de_Vto',
        'lead_source',
        'name',
        'address',
        'payment_type',
        'country',
        'is_sub',
        'payment_in_advance',
        'left_installments',
        'left_payment_type',
        'currency',
    ];
    private static $formAttributes = [
        'id',
        'installments',
        'Fecha_de_Vto',
        'lead_source',
        'name',
        'address',
        'payment_type',
        'country',
        'is_sub',
        'payment_in_advance',
        'left_installments',
        'left_payment_type',
        'currency'
    ];
    protected $table = 'contracts';
    public $hidden = ['created_at', 'updated_at', 'products'];

    public static function getFormAttributes()
    {
        return self::$formAttributes;
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'contract_id', 'id');
    }

    public static function getProducts($lineItems)
    {
        $answer = [];
        foreach ($lineItems as $p) {
            $newP = [];
            $newP['name'] = $p->getProduct()->getLookupLabel();
            $newP['quantity'] = $p->getQuantity();
            $newP['id'] = $p->getId();
            $newP['price'] = $p->getNetTotal();

            $answer[] = $newP;

        }
        return $answer;
    }
}