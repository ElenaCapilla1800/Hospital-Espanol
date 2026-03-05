<?php

namespace App\Service;

use App\Entity\RegistroAcceso;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Servicio centralizado de Auditoría.
 * Este "notario" digital registra todo lo que ocurre a nivel de seguridad.
 * Tarea 4 - Punto 15, 16 y 18
 */
class AccessLogService
{
    private $em;
    private $requestStack;
    private $security;

    /**
     * Inyectamos las dependencias necesarias:
     * - EntityManagerInterface: Para persistir en la base de datos.
     * - RequestStack: Para acceder a la petición actual (y sacar la IP).
     * - Security: Para identificar al usuario conectado.
     */
    public function __construct(
        EntityManagerInterface $em,
        RequestStack $requestStack,
        Security $security
    ) {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    /**
     * MÉTODO: registrar
     * Crea una nueva entrada en la tabla RegistroAcceso.
     * * @param string $accion Descripción del evento (Ej: "LOGIN_FALLIDO", "VER_PACIENTE_ID_5")
     * @param bool $exito Define si el acceso fue permitido (true) o denegado (false)
     */
    public function registrar(string $accion, bool $exito): void
    {
        // 1. Obtenemos datos del contexto actual
        $usuario = $this->security->getUser();
        $request = $this->requestStack->getCurrentRequest();

        // 2. Creamos la instancia de la Entidad
        $log = new RegistroAcceso();

        // 3. QUIÉN: Obtenemos el identificador del usuario (email).
        // Si no hay sesión iniciada (ej. fallo de login), marcamos como 'ANÓNIMO'.
        $log->setUsuarioEmail($usuario ? $usuario->getUserIdentifier() : 'ANÓNIMO');

        // 4. QUÉ: Guardamos el mensaje de la acción.
        $log->setAccion($accion);

        // 5. CUÁNDO: Guardamos el momento exacto.
        // Usamos DateTime para total compatibilidad con los filtros del Repositorio.
        $log->setFecha(new \DateTime());

        // 6. DESDE DÓNDE: Intentamos capturar la IP del cliente.
        // Si se ejecuta desde consola o similar, ponemos la IP local por defecto.
        $log->setIp($request ? $request->getClientIp() : '127.0.0.1');

        // 7. RESULTADO: Guardamos si fue un éxito o un intento fallido (Tarea 4 - Punto 14).
        $log->setExito($exito);

        // 8. PERSISTENCIA: Enviamos los datos a la base de datos.
        $this->em->persist($log);
        $this->em->flush();
    }
}
