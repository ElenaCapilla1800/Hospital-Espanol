<?php

namespace App\Security\Voter;

use App\Entity\Paciente;
use App\Service\AccessLogService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Este Voter es el "Guardia de Seguridad" del historial clínico.
 * Tarea 3 - Punto 11 (Voter) | Tarea 4 - Punto 16 (Log en Voter)
 */
class PacienteVoter extends Voter
{
    // Definimos las acciones que este Voter sabe supervisar
    public const VER    = 'PACIENTE_VER';
    public const EDITAR = 'PACIENTE_EDITAR';

    private $logService;
    private $security;

    /**
     * Inyectamos el servicio de auditoría y el componente de seguridad de Symfony.
     */
    public function __construct(AccessLogService $logService, Security $security)
    {
        $this->logService = $logService;
        $this->security   = $security;
    }

    /**
     * PASO 1: ¿Debe este Voter tomar la decisión?
     * Solo actúa si el atributo es uno de los nuestros y el objeto es un Paciente.
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VER, self::EDITAR])
            && $subject instanceof Paciente;
    }

    /**
     * PASO 2: La lógica del permiso.
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // 1. Identificamos al usuario
        $user = $token->getUser();
        $username = $user instanceof UserInterface ? $user->getUserIdentifier() : 'Anónimo';

        /** @var Paciente $paciente */
        $paciente = $subject;
        $decision = false;

        // 2. LÓGICA DE ROLES (Tarea 3)
        // Usamos $this->security->isGranted() en lugar de in_array para que
        // funcione la JERARQUÍA de roles definida en security.yaml.
        switch ($attribute) {
            case self::VER:
                // Permitimos ver si es ROLE_MEDICO o superior (ROLE_ADMIN)
                if ($this->security->isGranted('ROLE_MEDICO')) {
                    $decision = true;
                }
                break;

            case self::EDITAR:
                // Solo permitimos editar si es ROLE_MEDICO o superior
                if ($this->security->isGranted('ROLE_MEDICO')) {
                    $decision = true;
                }
                break;
        }

        // 3. REGISTRO AUTOMÁTICO DE AUDITORÍA (Tarea 4 - Punto 16 y Punto 14)
        // Registramos tanto el éxito como el intento denegado.
        $status = $decision ? "ACCESO CONCEDIDO" : "ACCESO DENEGADO";
        $mensaje = sprintf(
            "%s: Acción [%s] sobre Paciente ID: %d (%s)",
            $status,
            $attribute,
            $paciente->getId(),
            $paciente->getNombre()
        );

        // Guardamos en la base de datos a través de nuestro servicio
        $this->logService->registrar($mensaje, $decision);

        return $decision;
    }
}
