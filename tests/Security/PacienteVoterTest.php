<?php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Clase de prueba para verificar la seguridad y los Voters del Hospital Español.
 * Utilizamos WebTestCase para simular un navegador real.
 */
class PacienteVoterTest extends WebTestCase
{
    /**
     * TEST 1: Verificar que un usuario no autenticado (público)
     * no pueda acceder al listado de pacientes.
     */
    public function testPublicCannotAccessPacientes(): void
    {
        // 1. Creamos el cliente que simula el navegador
        $client = static::createClient();

        // 2. Obtenemos el servicio de rutas (Router) para generar la URL dinámicamente
        $router = static::getContainer()->get('router');
        $url = $router->generate('app_paciente_listado');

        // 3. Ejecutamos la petición GET a la lista de pacientes
        $client->request('GET', $url);

        /**
         * 4. AFIRMACIÓN (Assertion):
         * Según la traza de PHPUnit, tu aplicación redirige a '/inicio'
         * cuando un usuario anónimo intenta entrar. Verificamos que esto ocurra.
         */
        $this->assertResponseRedirects('/inicio', 302, 'La seguridad redirigió correctamente al usuario anónimo a /inicio');
    }

    /**
     * TEST 2: Verificar que la zona de Auditoría (Logs) esté protegida
     * contra accesos no autorizados.
     */
    public function testAdminLogsAreProtected(): void
    {
        $client = static::createClient();
        $router = static::getContainer()->get('router');

        // Generamos la URL de la página de logs de administración
        $url = $router->generate('app_admin_logs');

        // Intentamos acceder sin habernos logueado como Admin
        $client->request('GET', $url);

        /**
         * 5. AFIRMACIÓN:
         * Verificamos que el código de estado NO sea 200 (OK).
         * El sistema debe o bien redirigir (302) o denegar el acceso (403 Forbidden).
         */
        $statusCode = $client->getResponse()->getStatusCode();

        $this->assertTrue(
            $client->getResponse()->isRedirect() || $statusCode === 403,
            "Seguridad confirmada: La zona de auditoría no es pública. Status recibido: " . $statusCode
        );
    }
}
