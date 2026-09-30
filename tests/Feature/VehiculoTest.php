<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehiculoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('lideres', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
        });
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lidere_id');
            $table->string('tipo_vehiculo');
            $table->string('nombre_conductor')->nullable();
            $table->string('numero_tarjeta_propiedad')->nullable();
            $table->timestamps();
        });
        DB::table('lideres')->insert([
            ['id' => 1, 'nombres' => 'Ana', 'apellidos' => 'Perez'],
            ['id' => 2, 'nombres' => 'Luis', 'apellidos' => 'Diaz'],
        ]);
    }

    public function test_vehicle_crud_and_leader_filter()
    {
        $this->withoutMiddleware(Authenticate::class);
        $id = $this->postJson('/api/vehiculos', [
            'lidere_id' => 1, 'tipo_vehiculo' => 'Carro',
            'nombre_conductor' => 'Maria', 'numero_tarjeta_propiedad' => '00123',
        ])->assertCreated()->json('vehiculo.id');
        $this->postJson('/api/vehiculos', ['lidere_id' => 2, 'tipo_vehiculo' => 'Motocicleta'])->assertCreated();
        $this->getJson('/api/vehiculos')->assertOk()->assertJsonCount(2, 'vehiculos');
        $this->getJson('/api/vehiculos/'.$id)->assertOk()
            ->assertJsonPath('vehiculo.lider_nombres', 'Ana')
            ->assertJsonPath('vehiculo.lider_apellidos', 'Perez')
            ->assertJsonPath('vehiculo.numero_tarjeta_propiedad', '00123');
        $this->getJson('/api/vehiculos-lider/1')->assertOk()->assertJsonCount(1, 'vehiculos');
        $this->getJson('/api/vehiculos-lider/999')->assertOk()->assertJsonCount(0, 'vehiculos');
        $this->patchJson('/api/vehiculos/'.$id, ['nombre_conductor' => null])->assertOk()
            ->assertJsonPath('vehiculo.nombre_conductor', null)
            ->assertJsonPath('vehiculo.tipo_vehiculo', 'Carro');
        $this->putJson('/api/vehiculos/'.$id, ['tipo_vehiculo' => 'Motocicleta'])->assertOk();
        $this->deleteJson('/api/vehiculos/'.$id)->assertOk();
        $this->getJson('/api/vehiculos/'.$id)->assertNotFound();
        $this->putJson('/api/vehiculos/'.$id, [])->assertNotFound();
        $this->deleteJson('/api/vehiculos/'.$id)->assertNotFound();
    }

    public function test_invalid_vehicle_data_is_rejected()
    {
        $this->withoutMiddleware(Authenticate::class);
        $this->postJson('/api/vehiculos', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['lidere_id', 'tipo_vehiculo']);
        $this->postJson('/api/vehiculos', [
            'lidere_id' => 999, 'tipo_vehiculo' => 'Camion',
            'nombre_conductor' => str_repeat('a', 256), 'numero_tarjeta_propiedad' => 123,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'lidere_id', 'tipo_vehiculo', 'nombre_conductor', 'numero_tarjeta_propiedad',
        ]);
        $id = $this->postJson('/api/vehiculos', ['lidere_id' => 1, 'tipo_vehiculo' => 'Carro'])->json('vehiculo.id');
        $this->patchJson('/api/vehiculos/'.$id, ['lidere_id' => null, 'tipo_vehiculo' => null])
            ->assertUnprocessable()->assertJsonValidationErrors(['lidere_id', 'tipo_vehiculo']);
    }

    public function test_vehicle_routes_require_authentication()
    {
        foreach ([['GET', '/vehiculos'], ['GET', '/vehiculos/1'], ['GET', '/vehiculos-lider/1'],
            ['POST', '/vehiculos'], ['PUT', '/vehiculos/1'], ['PATCH', '/vehiculos/1'], ['DELETE', '/vehiculos/1']] as [$method, $path]) {
            $this->json($method, '/api'.$path)->assertUnauthorized();
        }
    }
}
