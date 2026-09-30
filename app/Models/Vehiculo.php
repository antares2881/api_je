<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $fillable = [
        'lidere_id',
        'tipo_vehiculo',
        'nombre_conductor',
        'numero_tarjeta_propiedad',
    ];
}
