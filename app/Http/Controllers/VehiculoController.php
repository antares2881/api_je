<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehiculoController extends Controller
{
    private function vehiculosConLider()
    {
        return Vehiculo::query()
            ->leftJoin('lideres', 'vehiculos.lidere_id', '=', 'lideres.id')
            ->select('vehiculos.*', 'lideres.nombres as lider_nombres', 'lideres.apellidos as lider_apellidos');
    }

    public function getVehiculos()
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'vehiculos' => $this->vehiculosConLider()->get(),
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'vehiculo' => $this->vehiculosConLider()->where('vehiculos.id', $id)->firstOrFail(),
        ]);
    }

    private function validatedData(Request $request, $updating = false)
    {
        return $request->validate([
            'lidere_id' => [$updating ? 'sometimes' : 'required', 'required', 'integer', 'exists:lideres,id'],
            'tipo_vehiculo' => [$updating ? 'sometimes' : 'required', 'required', Rule::in(['Carro', 'Motocicleta'])],
            'nombre_conductor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'numero_tarjeta_propiedad' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
    }

    public function store(Request $request)
    {
        $vehiculo = Vehiculo::create($this->validatedData($request));

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'vehiculo' => $vehiculo,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->update($this->validatedData($request, true));

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'vehiculo' => $vehiculo,
        ]);
    }

    public function delete($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Vehículo eliminado correctamente',
        ]);
    }

    public function getVehiculosLider($lidere_id)
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'vehiculos' => $this->vehiculosConLider()->where('vehiculos.lidere_id', $lidere_id)->get(),
        ]);
    }
}
