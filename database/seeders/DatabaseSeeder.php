<?php

namespace Database\Seeders;

use App\Models\ConfiguracionGeneral;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Puebla la base de datos con un set de demostración: un usuario de acceso
     * fijo (mismo email/password siempre, ver credenciales al final de este
     * método) y una jerarquía de locaciones con contratos, garantías, costos
     * fijos, lecturas de medidor y recibos en distintos estados, para poder
     * recorrer visualmente cada pantalla de la aplicación sin datos reales.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrador Demo',
            'email' => 'admin@rent-tracker.test',
            'password' => Hash::make('demo1234'),
        ]);

        ConfiguracionGeneral::query()->delete();
        ConfiguracionGeneral::create([
            'correo_notificaciones_vencimiento' => $admin->email,
            'tarifa_luz_por_unidad' => 0.85,
            'dias_anticipacion_alerta_pago' => 5,
        ]);

        $galeria = Locacion::create([
            'nombre' => 'Galería Los Pinos',
            'tamano' => 500,
            'ubicacion_fisica' => 'Av. Principal 123, Arequipa',
            'descripcion' => 'Galería comercial de dos niveles',
            'locacion_padre_id' => null,
            'es_alquilable' => false,
        ]);

        $piso1 = Locacion::create([
            'nombre' => 'Piso 1',
            'tamano' => 250,
            'ubicacion_fisica' => 'Nivel calle',
            'descripcion' => 'Primer nivel de la galería',
            'locacion_padre_id' => $galeria->id,
            'es_alquilable' => false,
        ]);

        $piso2 = Locacion::create([
            'nombre' => 'Piso 2',
            'tamano' => 250,
            'ubicacion_fisica' => 'Segundo nivel',
            'descripcion' => 'Segundo nivel de la galería',
            'locacion_padre_id' => $galeria->id,
            'es_alquilable' => false,
        ]);

        $local101 = Locacion::create(['nombre' => 'Local 101', 'tamano' => 25, 'ubicacion_fisica' => 'Piso 1, frente a escaleras', 'descripcion' => 'Local esquinero con vitrina doble', 'locacion_padre_id' => $piso1->id, 'es_alquilable' => true]);
        $local102 = Locacion::create(['nombre' => 'Local 102', 'tamano' => 30, 'ubicacion_fisica' => 'Piso 1, pasillo central', 'descripcion' => 'Local amplio con depósito', 'locacion_padre_id' => $piso1->id, 'es_alquilable' => true]);
        $local103 = Locacion::create(['nombre' => 'Local 103', 'tamano' => 20, 'ubicacion_fisica' => 'Piso 1, fondo', 'descripcion' => 'Local compacto', 'locacion_padre_id' => $piso1->id, 'es_alquilable' => true]);
        $local201 = Locacion::create(['nombre' => 'Local 201', 'tamano' => 28, 'ubicacion_fisica' => 'Piso 2, frente a escaleras', 'descripcion' => 'Local con balcón interior', 'locacion_padre_id' => $piso2->id, 'es_alquilable' => true]);
        $local202 = Locacion::create(['nombre' => 'Local 202', 'tamano' => 22, 'ubicacion_fisica' => 'Piso 2, pasillo central', 'descripcion' => 'Local con buena iluminación natural', 'locacion_padre_id' => $piso2->id, 'es_alquilable' => true]);

        $rufina = Inquilino::create(['apellidos' => 'Canahuire Pinto', 'nombres' => 'Rufina', 'dni' => '41234567', 'fecha_nacimiento' => '1968-03-12']);
        $walter = Inquilino::create(['apellidos' => 'Salluca Canahuire', 'nombres' => 'Walter', 'dni' => '42345678', 'fecha_nacimiento' => '1975-07-22']);
        $mario = Inquilino::create(['apellidos' => 'Gomez Valdez', 'nombres' => 'Mario', 'dni' => '43456789', 'fecha_nacimiento' => '1980-11-05']);
        $mariaTeresa = Inquilino::create(['apellidos' => 'Mamani Huanca', 'nombres' => 'Maria Teresa', 'dni' => '44567890', 'fecha_nacimiento' => '1972-01-30']);
        $epifania = Inquilino::create(['apellidos' => 'Canahuiri Pinto', 'nombres' => 'Epifania', 'dni' => '45678901', 'fecha_nacimiento' => '1965-09-18']);

        // Local 101: contrato activo, garantía ya resuelta, con historial de lecturas y un recibo pagado.
        $contrato101 = Contrato::create([
            'locacion_id' => $local101->id,
            'fecha_inicio' => now()->subMonths(6)->startOfMonth(),
            'fecha_fin' => now()->addMonths(6)->endOfMonth(),
            'monto_renta' => 800,
            'estado' => 'activo',
            'costo_agua' => 40,
            'costo_luz' => 60,
            'costo_pasadizo' => 12,
            'costo_seguridad' => 20,
            'monto_garantia' => 800,
            'fecha_entrega_garantia' => now()->subMonths(6)->startOfMonth(),
            'medio_entrega_garantia' => 'transferencia',
            'estado_garantia' => 'resuelta',
            'monto_devuelto_garantia' => 750,
            'monto_retenido_garantia' => 50,
            'motivo_retencion_garantia' => 'Reparación de vitrina dañada',
            'fecha_resolucion_garantia' => now()->subMonth(),
        ]);
        $contrato101->inquilinos()->attach($rufina->id, ['es_principal' => true]);

        $lecturaAnterior = null;
        foreach ([1900, 1965, 2040] as $indice => $lectura) {
            $periodo = now()->subMonths(3 - $indice)->startOfMonth();
            LecturaMedidor::create([
                'locacion_id' => $local101->id,
                'periodo' => $periodo,
                'lectura_anterior' => $lecturaAnterior,
                'lectura_actual' => $lectura,
                'consumo_calculado' => $lecturaAnterior === null ? null : $lectura - $lecturaAnterior,
                'fecha_registro' => $periodo,
            ]);
            $lecturaAnterior = $lectura;
        }

        Recibo::create([
            'contrato_id' => $contrato101->id,
            'locacion_id' => $local101->id,
            'monto_renta' => 800,
            'monto_agua' => 40,
            'monto_luz' => 63.75,
            'monto_pasadizo' => 12,
            'monto_seguridad' => 20,
            'periodo' => now()->startOfMonth(),
            'fecha_emision' => now()->startOfMonth()->addDays(2),
            'estado' => 'pagado',
            'fecha_pago' => now()->startOfMonth()->addDays(5),
        ]);

        // Local 102: contrato activo, garantía entregada sin resolver, recibo pendiente.
        $contrato102 = Contrato::create([
            'locacion_id' => $local102->id,
            'fecha_inicio' => now()->subMonths(3)->startOfMonth(),
            'fecha_fin' => now()->addMonths(9)->endOfMonth(),
            'monto_renta' => 950,
            'estado' => 'activo',
            'costo_agua' => 45,
            'costo_luz' => 70,
            'costo_pasadizo' => 12,
            'costo_seguridad' => 20,
            'monto_garantia' => 950,
            'fecha_entrega_garantia' => now()->subMonths(3)->startOfMonth(),
            'medio_entrega_garantia' => 'efectivo',
            'estado_garantia' => 'entregada',
        ]);
        $contrato102->inquilinos()->attach($walter->id, ['es_principal' => true]);

        Recibo::create([
            'contrato_id' => $contrato102->id,
            'locacion_id' => $local102->id,
            'monto_renta' => 950,
            'monto_agua' => 45,
            'monto_luz' => 70,
            'monto_pasadizo' => 12,
            'monto_seguridad' => 20,
            'periodo' => now()->startOfMonth(),
            'fecha_emision' => now()->startOfMonth()->addDays(1),
            'estado' => 'pendiente',
        ]);

        // Local 103: contrato vencido, sin garantía, con un recibo anulado (para ver esa marca).
        $contrato103 = Contrato::create([
            'locacion_id' => $local103->id,
            'fecha_inicio' => now()->subYear()->startOfMonth(),
            'fecha_fin' => now()->subMonths(2)->endOfMonth(),
            'monto_renta' => 650,
            'estado' => 'vencido',
        ]);
        $contrato103->inquilinos()->attach($mario->id, ['es_principal' => true]);

        Recibo::create([
            'contrato_id' => $contrato103->id,
            'locacion_id' => $local103->id,
            'monto_renta' => 650,
            'monto_agua' => 0,
            'monto_luz' => 0,
            'monto_pasadizo' => 0,
            'monto_seguridad' => 0,
            'incluye_agua' => false,
            'incluye_luz' => false,
            'incluye_pasadizo' => false,
            'incluye_seguridad' => false,
            'periodo' => now()->subMonths(3)->startOfMonth(),
            'fecha_emision' => now()->subMonths(3)->startOfMonth()->addDays(2),
            'estado' => 'anulado',
            'fecha_anulacion' => now()->subMonths(3)->startOfMonth()->addDays(10),
        ]);

        // Local 201: contrato en borrador (todavía no inicia), dos inquilinos (Principal + acompañante).
        $contrato201 = Contrato::create([
            'locacion_id' => $local201->id,
            'fecha_inicio' => now()->addMonth()->startOfMonth(),
            'fecha_fin' => now()->addMonths(13)->endOfMonth(),
            'monto_renta' => 700,
            'estado' => 'borrador',
        ]);
        $contrato201->inquilinos()->attach($mariaTeresa->id, ['es_principal' => true]);
        $contrato201->inquilinos()->attach($epifania->id, ['es_principal' => false]);

        // Local 202: sin contrato, para mostrar una locación alquilable todavía disponible.
        unset($local202);

        $this->command?->info('');
        $this->command?->info('Acceso de demostración:');
        $this->command?->info('  Correo:     admin@rent-tracker.test');
        $this->command?->info('  Contraseña: demo1234');
    }
}
