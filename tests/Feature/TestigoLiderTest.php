<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TestigoLiderTest extends TestCase
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
        Schema::create('testigos', function (Blueprint $table) {
            $table->id();
            foreach (['divipoletestigo_id', 'candidato_id', 'partido_id', 'lidere_id', 'user_id', 'cedula'] as $field) {
                $table->bigInteger($field)->nullable();
            }
            foreach (['nom1', 'nom2', 'ape1', 'ape2', 'email', 'telefono'] as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('testigosmesas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('testigo_id');
            $table->string('mesa');
            $table->timestamps();
        });
        Schema::create('divipoletestigos', function (Blueprint $table) {
            $table->id();
            $table->string('cod_zona');
            $table->string('cod_puesto');
        });
        DB::table('lideres')->insert(['id' => 1, 'nombres' => 'Ana', 'apellidos' => 'Perez']);
        DB::table('divipoletestigos')->insert(['id' => 1, 'cod_zona' => '01', 'cod_puesto' => '02']);
        $user = new User();
        $user->id = 1;
        $user->candidato_id = 10;
        $this->actingAs($user)->withoutMiddleware(Authenticate::class);
    }

    public function test_search_filters_by_leader_and_authenticated_candidate()
    {
        DB::table('testigos')->insert([
            ['id' => 1, 'lidere_id' => 1, 'candidato_id' => 10],
            ['id' => 2, 'lidere_id' => null, 'candidato_id' => 10],
            ['id' => 3, 'lidere_id' => 1, 'candidato_id' => 20],
        ]);
        DB::table('testigosmesas')->insert(['testigo_id' => 1, 'mesa' => '5']);

        $this->getJson('/api/testigos-lider/1')->assertOk()
            ->assertJsonCount(1, 'testigos')
            ->assertJsonPath('testigos.0.id', 1)
            ->assertJsonPath('testigos.0.lider_nombres', 'Ana')
            ->assertJsonPath('testigos.0.lider_apellidos', 'Perez')
            ->assertJsonPath('testigos.0.mesas.0.mesa', '5');
        $this->getJson('/api/testigos-lider/999')->assertOk()->assertJsonCount(0, 'testigos');
        $this->getJson('/api/testigos-lider/abc')->assertNotFound();
    }

    public function test_leader_is_saved_joined_updated_and_optional()
    {
        $payload = ['cedula' => 123, 'nom1' => 'Luis', 'ape1' => 'Diaz', 'partido_id' => 1, 'divipoletestigo_id' => 1];
        $id = $this->postJson('/api/testigos', $payload + ['lidere_id' => 1])->assertOk()
            ->assertJsonPath('testigo.lidere_id', 1)->assertJsonPath('testigo.lider_nombres', 'Ana')->json('testigo.id');
        $this->postJson('/api/testigos', $payload)->assertOk()->assertJsonPath('testigo.lidere_id', null);
        $this->getJson('/api/testigos')->assertOk()->assertJsonCount(2, 'testigos')
            ->assertJsonPath('testigos.0.lider_apellidos', 'Perez');
        $this->postJson('/api/testigos-puesto', ['zona' => '01', 'puesto' => '02'])->assertOk()
            ->assertJsonPath('testigos.0.testigo.0.lider_nombres', 'Ana');
        $this->putJson('/api/testigos/'.$id, ['telefono' => '555'])->assertOk()
            ->assertJsonPath('testigo.lidere_id', 1)->assertJsonPath('testigo.telefono', '555');
        $this->putJson('/api/testigos/'.$id, ['lidere_id' => null])->assertOk()->assertJsonPath('testigo.lider_nombres', null);
        $this->putJson('/api/testigos/'.$id, ['lidere_id' => 1])->assertOk()->assertJsonPath('testigo.lider_nombres', 'Ana');
        $this->putJson('/api/testigos/'.$id, ['lidere_id' => 999])->assertJsonStructure(['lidere_id']);
        $this->postJson('/api/testigos', $payload + ['lidere_id' => 999])->assertJsonStructure(['lidere_id']);
        $this->assertDatabaseHas('testigos', ['id' => $id, 'lidere_id' => 1]);
        $this->assertDatabaseCount('testigos', 2);
    }
}
