<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class EstadisticasSegundaVuelta2026Controller extends Controller
{
    
    public function estadisticas2026($dpto = null, $tipo_reporte = 1, $mun = null, $par = null, $can = null, $puesto = null){

        $sinFiltro = static function ($valor) {
            return in_array($valor, [null, '', 'null', -1, '-1'], true);
        };

        if ($sinFiltro($dpto)) {
            $select = 'm.DEP as departamento_id, d.departamento,';
            $inner = '';
            $group = 'm.DEP, d.departamento,';
            $order = ',d.departamento ASC';
            $limit = '';
        } elseif ($sinFiltro($mun)) {
            $select = 'm.DEP as departamento_id, d.departamento, m.MUN as municipio_id, mu.municipio,';
            $inner = 'INNER JOIN municipios as mu ON m.MUN = mu.id AND m.DEP = mu.departamento_id';
            $group = 'm.DEP, d.departamento, m.MUN, mu.municipio,';
            $order = ',d.departamento ASC, mu.municipio ASC';
            $limit = '';
        } elseif($tipo_reporte == 1){
            $select = '';
            $inner = '';
            $group = '';
            $order = '';
            $limit = '';
        }else{
            $select = 'mu.municipio, m.PUESNOMBRE,';
            $inner = 'INNER JOIN municipios as mu ON m.MUN = mu.id AND m.DEP = mu.departamento_id';
            $group = 'mu.municipio, m.PUESNOMBRE,';
            $order = ',mu.municipio ASC, m.PUESNOMBRE ASC';
            $limit = '';
        }

        $bindings = [3];
        $condiciones = [];

        foreach (['DEP' => $dpto, 'MUN' => $mun, 'PAR' => $par, 'CAN' => $can, 'PUESNOMBRE' => $puesto] as $columna => $valor) {
            if (!$sinFiltro($valor)) {
                $condiciones[] = "m.$columna = ?";
                $bindings[] = $valor;
            }
        }

        $filtros = $condiciones ? 'AND ' . implode(' AND ', $condiciones) : '';

        $estadisticas = DB::select("SELECT $select p.PARNOMBRE as partido, m.CAN, m.CANNOMBRE, SUM(m.VOTOS) as total FROM estadisticas_segunda_vuelta_2026 as m 
            $inner
            INNER JOIN departamentos as d ON m.DEP = d.id
            INNER JOIN corporacionescongreso2022 as co ON m.CORCODIGO = co.id
            INNER JOIN partidos_segunda_vuelta_2026 as p ON m.PAR = p.PAR
            WHERE m.CORCODIGO = ? $filtros
            GROUP BY $group p.PARNOMBRE, m.CAN, m.CANNOMBRE
            ORDER BY total DESC $order 
            $limit", $bindings);

        $data = array(
            'status' => 'success',
            'code' => 200,
            'estadisticas' => $estadisticas
        );

        return response()->json($data, $data['code']);
    }

    public function getCandidatos($dpto = null, $partido = null){
        $bindings = [];
        $condiciones = [];

        foreach (['CORCODIGO' => 3, 'DEP' => $dpto, 'PAR' => $partido] as $columna => $valor) {
            // La URL puede enviar "null" o -1 para indicar que no hay filtro.
            if (in_array($valor, [null, '', 'null', -1, '-1'], true)) {
                continue;
            }

            $condiciones[] = "m.$columna = ?";
            $bindings[] = $valor;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        $candidatos = DB::select("SELECT DISTINCT m.CAN, m.CANNOMBRE, m.PAR
            FROM estadisticas_segunda_vuelta_2026 as m
            $where
            ORDER BY m.CAN, m.CANNOMBRE, m.PAR", $bindings);

        $data = array(
            'status' => 'success',
            'code' => 200,
            'candidatos' => $candidatos
        );

        return response()->json($data, $data['code']);        
    }

    public function getCorporaciones(){

        $corporaciones = DB::select("SELECT * FROM corporacionescongreso2022 WHERE id = 3");

        $data = array(
            'status' => 'success',
            'code' => 200,
            'corporaciones' => $corporaciones
        );

        return response()->json($data, $data['code']);
    }

    public function getPartidos($dpto){
        $partidos = DB::select('SELECT m.PAR, p.PARNOMBRE as partido FROM estadisticas_segunda_vuelta_2026 as m
            INNER JOIN departamentos as d ON m.DEP = d.id
            INNER JOIN corporacionescongreso2022 as co ON m.CORCODIGO = co.id
            INNER JOIN partidos_segunda_vuelta_2026 as p ON m.PAR = p.PAR
            WHERE m.DEP = ? AND m.CORCODIGO = ?
            GROUP BY m.PAR, p.PARNOMBRE
            ORDER BY m.PAR, p.PARNOMBRE', [$dpto, 3]);

        $data = array(
            'status' => 'success',
            'code' => 200,
            'partidos' => $partidos
        );

        return response()->json($data, $data['code']);
    }

    public function getPuestos($dpto, $mcpio){
        $puestos = DB::select('SELECT PUESNOMBRE FROM estadisticas_segunda_vuelta_2026 WHERE DEP = ? AND MUN = ? 
        GROUP BY PUESNOMBRE
        ORDER BY PUESNOMBRE ASC', [$dpto, $mcpio]);

        $data = array(
            'status' => 'success',
            'code' => 200,
            'puestos' => $puestos
        );

        return response()->json($data, $data['code']);
    }

    public function regiones(){
        $regiones = DB::select("SELECT region FROM regiones GROUP BY region ORDER BY region ASC");

        $data = array(
            'status' => 'success',
            'code' => 200,
            'regiones' => $regiones
        );

        return response()->json($data, $data['code']);
    }

    public function votacion_por_municipio(){

        // Se conservan el departamento y los candidatos fijos del reporte original.
            $votacion = DB::select("SELECT r.id, r.region, m.id as municipio_id, m.municipio, p.PAR as partido_id, p.PARNOMBRE as partido, c.CAN, c.CANNOMBRE, SUM(c.VOTOS) as votacion
                FROM estadisticas_segunda_vuelta_2026 as c 
                INNER JOIN municipios as m ON c.MUN = m.id AND c.DEP = m.departamento_id
                INNER JOIN departamentos as d ON c.DEP = d.id
                INNER JOIN corporacionescongreso2022 as co ON c.CORCODIGO = co.id
                INNER JOIN partidos_segunda_vuelta_2026 as p ON c.PAR = p.PAR
                INNER JOIN regiones as r ON m.id = r.municipio_id
                WHERE c.DEP = 13 AND c.CORCODIGO = ? AND ((p.PAR = 8 AND c.CAN = 5) OR (p.PAR = 8 AND c.CAN = 6) OR (p.PAR = 8 AND c.CAN = 8) OR (p.PAR = 8 AND c.CAN = 14) OR (p.PAR = 2 AND c.CAN = 7) OR (p.PAR = 2 AND c.CAN = 11) OR (p.PAR = 1 AND c.CAN = 2))
                GROUP BY r.id, r.region, m.id, m.municipio, p.PAR, p.PARNOMBRE,  c.CAN, c.CANNOMBRE
                ORDER BY m.id ASC, votacion DESC", [3]);

        $data = array(
            'status' => 'success',
            'code' => 200,
            'votacion' => $votacion
        );

        return response()->json($data, $data['code']);
    }
}
