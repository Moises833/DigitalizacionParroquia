<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear un usuario de prueba (para la auditoría)
        $user = \App\Models\User::firstOrCreate([
            'email' => 'admin@parroquia.org'
        ], [
            'name' => 'Secretario Parroquial',
            'password' => bcrypt('password'),
        ]);

        // Autenticar al usuario para la consola/seeder para que los disparadores de auditoría registren este usuario
        \Illuminate\Support\Facades\Auth::login($user);

        // 2. Crear libros físicos (Tomos)
        $libro1 = \App\Models\Libro::firstOrCreate(['numero_libro' => 'Tomo I (1950-1970)'], [
            'anio_inicio' => 1950,
            'anio_fin' => 1970,
            'observaciones' => 'Libro histórico en encuadernación de cuero rojo. Hojas delicadas.'
        ]);

        $libro2 = \App\Models\Libro::firstOrCreate(['numero_libro' => 'Tomo II (1970-1990)'], [
            'anio_inicio' => 1970,
            'anio_fin' => 1990,
            'observaciones' => 'Libro restaurado en 2015. Buen estado de legibilidad.'
        ]);

        $libro3 = \App\Models\Libro::firstOrCreate(['numero_libro' => 'Tomo III (1990-2010)'], [
            'anio_inicio' => 1990,
            'anio_fin' => 2010,
            'observaciones' => 'Encuadernación azul clásica.'
        ]);

        $libro4 = \App\Models\Libro::firstOrCreate(['numero_libro' => 'Tomo IV (2010-Activo)'], [
            'anio_inicio' => 2010,
            'anio_fin' => 2026,
            'observaciones' => 'Libro en curso.'
        ]);

        // 3. Crear personas ficticias para un acta de bautizo de demostración
        $bautizado = \App\Models\Persona::firstOrCreate([
            'nombres' => 'Manuel Alejandro',
            'apellidos' => 'Rodríguez Castillo',
        ], [
            'cedula' => null,
            'fecha_nacimiento' => '1998-05-15',
            'genero' => 'M'
        ]);

        $padre = \App\Models\Persona::firstOrCreate(['cedula' => 'V-11222333'], [
            'nombres' => 'Carlos Alberto',
            'apellidos' => 'Rodríguez Díaz',
            'fecha_nacimiento' => '1970-08-20',
            'genero' => 'M'
        ]);

        $madre = \App\Models\Persona::firstOrCreate(['cedula' => 'V-12345432'], [
            'nombres' => 'Carmen Elena',
            'apellidos' => 'Castillo Mendoza',
            'fecha_nacimiento' => '1973-11-02',
            'genero' => 'F'
        ]);

        $padrino = \App\Models\Persona::firstOrCreate(['cedula' => 'V-9988776'], [
            'nombres' => 'José Gregorio',
            'apellidos' => 'Castillo Mendoza',
            'fecha_nacimiento' => '1968-04-12',
            'genero' => 'M'
        ]);

        $madrina = \App\Models\Persona::firstOrCreate(['cedula' => 'V-10203040'], [
            'nombres' => 'Luisa Isabel',
            'apellidos' => 'Díaz Rodríguez',
            'fecha_nacimiento' => '1971-09-25',
            'genero' => 'F'
        ]);

        // 4. Crear acta de bautizo asociada
        \App\Models\ActaBautizo::firstOrCreate([
            'libro_id' => $libro3->id,
            'bautizado_id' => $bautizado->id,
            'numero_pagina' => '124',
            'numero_acta' => '482'
        ], [
            'padre_id' => $padre->id,
            'madre_id' => $madre->id,
            'padrino_1_id' => $padrino->id,
            'madrina_1_id' => $madrina->id,
            'fecha_bautizo' => '1998-10-12',
            'ministro' => 'Presbítero Ramón Ignacio Valera',
            'notas_marginales' => 'Matrimonio contraído con Sofía María López el 24/09/2022 en la Parroquia San José.',
        ]);

        // Segundo ejemplo de demostración
        $bautizado2 = \App\Models\Persona::firstOrCreate([
            'nombres' => 'Sofía Valentina',
            'apellidos' => 'Mendoza Silva',
        ], [
            'cedula' => null,
            'fecha_nacimiento' => '2001-09-08',
            'genero' => 'F'
        ]);

        $madre2 = \App\Models\Persona::firstOrCreate(['cedula' => 'V-15888999'], [
            'nombres' => 'Adriana Josefina',
            'apellidos' => 'Silva Cruz',
            'fecha_nacimiento' => '1980-01-30',
            'genero' => 'F'
        ]);

        \App\Models\ActaBautizo::firstOrCreate([
            'libro_id' => $libro3->id,
            'bautizado_id' => $bautizado2->id,
            'numero_pagina' => '201',
            'numero_acta' => '715'
        ], [
            'padre_id' => null, // Madre soltera registrada
            'madre_id' => $madre2->id,
            'padrino_1_id' => null,
            'madrina_1_id' => null,
            'fecha_bautizo' => '2001-12-25',
            'ministro' => 'Monseñor Bernardo Heredia',
            'notas_marginales' => null,
        ]);
    }
}
