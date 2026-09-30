<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Testigo;
use App\Models\Divipoletestigo;

class TestigoController extends Controller
{
    public function destroy($id){
        $testigo = Testigo::find($id)->delete();
        return 'ok';
    }
    public function index(){
        $testigos = Testigo::conLider()->with(['mesas', 'puestoVotacion'])->where('testigos.candidato_id', Auth::user()->candidato_id)->get();
        $data = array(
            'status' => 'success',
            'code' => 200,
            'testigos' => $testigos
        );
        return response()->json($data, $data['code']);
    }

    public function getTestigosLider($lidere_id)
    {
        $testigos = Testigo::conLider()->with(['mesas', 'puestoVotacion'])
            ->where('testigos.candidato_id', Auth::user()->candidato_id)
            ->where('testigos.lidere_id', $lidere_id)
            ->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'testigos' => $testigos,
        ]);
    }

    public function store(Request $request){

        $validator = \Validator::make($request->all(), [
            'cedula' => 'required',
            'nom1' => 'required',
            'ape1' => 'required',
            'partido_id' => 'required',
            'lidere_id' => 'nullable|integer|exists:lideres,id'
        ]);

        if($validator->fails()){
            return response()->json($validator->messages(), 200);
        }

        $testigo = new testigo();
        $testigo->divipoletestigo_id = $request->divipoletestigo_id;
        $testigo->candidato_id = Auth::user()->candidato_id;
        $testigo->partido_id = $request->partido_id;
        $testigo->lidere_id = $request->lidere_id;
        $testigo->cedula = $request->cedula;
        $testigo->nom1 = $request->nom1;
        $testigo->nom2 = $request->nom2;
        $testigo->ape1 = $request->ape1;
        $testigo->ape2 = $request->ape2;
        $testigo->email = $request->email;
        $testigo->telefono = $request->telefono;
        $testigo->user_id = Auth::user()->id;
        
        $testigo->save();

        $testigo = Testigo::conLider()->findOrFail($testigo->id);

        $data = array(
            'status' => 'success',
            'code' => 200,
            'testigo' => $testigo
        );
        
        return response()->json($data, $data['code']);
    }

    public function testigos_puesto(Request $request){
        $zona = $request->zona;
        $puesto = $request->puesto;

        $testigos = Divipoletestigo::with(['testigo' => function ($query) {
            $query->conLider();
        }])->where([
            'cod_zona' => $zona,
            'cod_puesto' => $puesto
        ])->get();
        $data = array(
            'status' => 'success',
            'code' => 200,
            'testigos' => $testigos
        );

        return response()->json($data, $data['code']);
    }

    public function testigosxmcpio($dpto, $mcpio){ //Funcion sin probar
        $testigos = Testigo::conLider()->with('mesas')
            ->where([
            ['departamento_id', $dpto],
            ['municipio_id', $mcpio],
        ])->get();
        return $testigos;
    }

    public function testigosxdpto(){  //Funcion sin probar
        $testigos = DB::select('SELECT departamento_id, departamento,  municipio, municipio_id,  COUNT(municipio_id) as total FROM testigos GROUP BY departamento_id, departamento, municipio, municipio_id ORDER BY departamento_id asc');
        return $testigos;
    }
    
    public function update(Request $request, $id){
        $testigo = Testigo::findOrFail($id);
        $validator = \Validator::make($request->all(), [
            'cedula' => 'sometimes|required',
            'nom1' => 'sometimes|required',
            'ape1' => 'sometimes|required',
            'partido_id' => 'sometimes|required',
            'lidere_id' => 'sometimes|nullable|integer|exists:lideres,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 200);
        }

        foreach ($request->only([
            'divipoletestigo_id', 'partido_id', 'lidere_id', 'cedula',
            'nom1', 'nom2', 'ape1', 'ape2', 'email', 'telefono',
        ]) as $field => $value) {
            $testigo->$field = $value;
        }
        $testigo->user_id = Auth::user()->id;
        $testigo->save();

        if ($request->has('mesa')) {
            $testigo->mesas()->delete();
            $testigo->mesas()->create(['mesa' => $request->mesa]);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'testigo' => Testigo::conLider()->findOrFail($id),
        ]);
    }
}
