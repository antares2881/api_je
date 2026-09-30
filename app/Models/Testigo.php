<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testigo extends Model
{
    use HasFactory;

    public function scopeConLider($query)
    {
        return $query->leftJoin('lideres', 'testigos.lidere_id', '=', 'lideres.id')
            ->select('testigos.*', 'lideres.nombres as lider_nombres', 'lideres.apellidos as lider_apellidos');
    }

    public function puestoVotacion()
    {
        return $this->belongsTo(Divipoletestigo::class, 'divipoletestigo_id');
    }

    public function lider()
    {
        return $this->belongsTo(Lidere::class, 'lidere_id');
    }

    public function partido()
    {
        return $this->hasOneThrough(
            Partido::class,
            Candidato::class,
            'id', // Foreign key on the cars table...
            'id', // Foreign key on the owners table...
            'candidato_id', // Local key on the mechanics table...
            'partido_id' // Local key on the cars table...
        );
    }    
    public function departamento(){
        return $this->belongsTo(Departamento::class);
    }

    public function mesas(){
        return $this->hasMany(Testigosmesa::class, 'testigo_id');
    }
}
