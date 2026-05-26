<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';

    public $timestamps = false;


    const STATUS =
    [
        'PENDING'   => 'pending',
        'PREPARING' => 'preparing',
        'FINISHED'  => 'finished',
        'CANCELLED' => 'canceled',
    ];

    const PRODUCTS =
    [
        'COFFEE'    => 'coffee',
        'BREAD'     => 'bread',
        'CAT_EAR'   => 'cat_ear',
    ];

    const PRICES =
    [
        self::PRODUCTS['COFFEE']    => 1.5,
        self::PRODUCTS['BREAD']     => 3,
        self::PRODUCTS['CAT_EAR']   => 2,
    ];


    protected $fillable = [
        'customer',
        'product',
        'quantity',
        'price',
        'created_at',
        'status',
    ];
}
