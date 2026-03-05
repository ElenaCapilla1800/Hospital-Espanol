<?php

namespace App\DataFixtures;

use App\Entity\Usuario;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
// Esta herramienta sirve para que las contraseñas no se guarden en texto plano (seguridad)
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    // Creamos una variable para guardar el "encriptador" de contraseñas
    private $hasher;

    // El "constructor" prepara las herramientas que necesitamos antes de empezar
    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    // Esta es la función principal que Symfony ejecuta para llenar la base de datos
    public function load(ObjectManager $manager): void
    {
        // 1. Creamos una lista (array) con los datos de los usuarios que queremos
        $usuariosPrueba = [
            ['email' => 'admin@hospital.es', 'rol' => 'ROLE_ADMIN', 'nombre' => 'Admin de Seguridad'],
            ['email' => 'medico@hospital.es', 'rol' => 'ROLE_MEDICO', 'nombre' => 'Dr. Garcia'],
            ['email' => 'enfermero@hospital.es', 'rol' => 'ROLE_ENFERMERO', 'nombre' => 'Enfermera Marta'],
            ['email' => 'paciente@hospital.es', 'rol' => 'ROLE_USER', 'nombre' => 'Juan Paciente'],
        ];

        // 2. Usamos un bucle "foreach" para procesar cada usuario de la lista anterior
        foreach ($usuariosPrueba as $datos) {

            // Creamos una nueva "ficha" de Usuario vacía
            $usuario = new Usuario();

            // Rellenamos el email
            $usuario->setEmail($datos['email']);

            // Asignamos el rol (notar que Symfony pide que sea una lista [], aunque sea solo uno)
            $usuario->setRoles([$datos['rol']]);

            // Ponemos el nombre que definimos en nuestra entidad
            $usuario->setNombreCompleto($datos['nombre']);

            // 3. Ciframos la contraseña "123456" para que en la base de datos se vea como algo ilegible (ej: $2y$13$...)
            $passwordCifrada = $this->hasher->hashPassword($usuario, '123456_789');
            $usuario->setPassword($passwordCifrada);

            // 4. "Persist" es como decirle a Doctrine: "Ten este usuario en la mano, todavía no lo guardes"
            $manager->persist($usuario);
        }

        // 5. "Flush" es el comando final: envía todos los usuarios de golpe a la base de datos
        $manager->flush();
    }
}
