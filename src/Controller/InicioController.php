<?php

namespace App\Controller;

// Importamos las herramientas necesarias de Symfony
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Este controlador se encarga de gestionar la pantalla de bienvenida
 * una vez que el usuario ha pasado por el Autenticador.
 */
class InicioController extends AbstractController
{
    // Definimos la ruta '/inicio' y le damos el nombre 'app_inicio'
    #[Route('/inicio', name: 'app_inicio')]
    public function indice(): Response
    {
        // 1. Obtenemos el objeto del usuario que tiene la sesión abierta
        // 'getUser()' es un método interno de Symfony que nos da los datos del logueado
        $usuarioActual = $this->getUser();

        // 2. Seguridad extra: Si alguien intenta entrar por la URL sin estar logueado,
        // $usuarioActual será nulo, por lo que lo devolvemos a la página de login.
        if (!$usuarioActual) {
            return $this->redirectToRoute('app_login');
        }

        // 3. Extraemos el nombre completo usando el método que creamos en la Entidad Usuario
        $nombreParaMostrar = $usuarioActual->getNombreCompleto();

        // 4. Cargamos la plantilla de Twig y le pasamos la variable con el nombre
        return $this->render('inicio/index.html.twig', [
            'nombre_del_usuario' => $nombreParaMostrar,
            'fecha_actual' => date('d/m/Y'), // Pasamos también la fecha del día
        ]);
    }
}
