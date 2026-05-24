<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

/**
 * Alta del usuario dueño en una instancia nueva (aprovisionamiento).
 * Se corre una vez por instancia al desplegar para un cliente.
 *
 *   php artisan crono:crear-dueno --name="..." --email="..." [--password=...]
 *
 * Si no se pasa --password, se pide de forma interactiva (no queda en el historial).
 */
class CrearDueno extends Command
{
    protected $signature = 'crono:crear-dueno
                            {--name= : Nombre del dueño}
                            {--email= : Email (será su usuario de login)}
                            {--password= : Contraseña (si se omite, se pide interactivo)}';

    protected $description = 'Crea el usuario dueño de esta instancia y le asigna el rol dueno.';

    public function handle(): int
    {
        $name  = $this->option('name')  ?: $this->ask('Nombre del dueño');
        $email = $this->option('email') ?: $this->ask('Email del dueño');
        $password = $this->option('password') ?: $this->secret('Contraseña');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'unique:users,email'],
                'password' => ['required', Password::min(8)],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('dueno');

        $this->info("Dueño creado: {$user->email} (rol: dueno).");
        return self::SUCCESS;
    }
}
