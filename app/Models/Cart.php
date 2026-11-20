<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'product_id',
        'qty',
    ];

    /**
     * product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * products (alias agar kompatibel jika ada pemanggilan jamak)
     */
    public function products()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}