<?php

namespace App\EventSubscriber;

use App\Service\AccessLogService;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * El "Vigilante" del Hospital: Gestiona auditoría, bloqueos y redirecciones.
 */
class LoginFailureSubscriber implements EventSubscriberInterface
{
    private $logService;
    private $urlGenerator;
    private $entityManager;

    /**
     * Inyectamos el servicio de logs, el generador de URLs y el EntityManager para la BD.
     */
    public function __construct(
        AccessLogService $logService,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager
    ) {
        $this->logService = $logService;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
            LoginSuccessEvent::class => 'onLoginSuccess',
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    /**
     * TAREA 5 - PUNTO 22 y 23: Fallo de login y Bloqueo de cuenta.
     */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $passport = $event->getPassport();
        $email = 'Desconocido';

        if ($passport) {
            // Intentamos obtener el objeto Usuario del Passport
            $user = $passport->getUser();

            if ($user instanceof Usuario) {
                $email = $user->getUserIdentifier();

                // 1. Aumentamos el contador de intentos fallidos
                $intentos = $user->getIntentosFallidos() + 1;
                $user->setIntentosFallidos($intentos);

                // 2. Si llega a 5 intentos, bloqueamos la cuenta
                if ($intentos >= 5) {
                    $user->setActivo(false);
                    $this->logService->registrar("🚨 CUENTA BLOQUEADA: Usuario [$email] tras 5 fallos.", false);
                } else {
                    $this->logService->registrar("⚠️ FALLO DE LOGIN: Intento #$intentos para [$email]", false);
                }

                // Guardamos los cambios en la base de datos (importante)
                $this->entityManager->flush();
            }
        }
    }

    /**
     * EVENTO: Login Correcto.
     * Si entra bien, debemos resetear su contador de fallos.
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof Usuario) {
            // TAREA 5: Si el login es éxito, reseteamos los intentos a 0
            $user->resetIntentos();
            $this->entityManager->flush();

            $this->logService->registrar("✅ LOGIN EXITOSO: [" . $user->getUserIdentifier() . "] ha entrado", true);
        }
    }

    /**
     * TAREA 3 - PUNTO 14: Gestión de accesos denegados (403).
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Solo actuamos si es un error de permisos
        if (!$exception instanceof AccessDeniedException) {
            return;
        }

        $request = $event->getRequest();
        $ruta = $request->attributes->get('_route');

        // Registro de auditoría
        $this->logService->registrar("🚫 ACCESO PROHIBIDO: Intento en ruta [$ruta]", false);

        // Mensaje flash para el usuario
        $request->getSession()->getFlashBag()->add('danger', 'Acceso denegado: No tienes permisos suficientes.');

        // Redirigimos a inicio para evitar la pantalla de error de Symfony
        $response = new RedirectResponse($url = $this->urlGenerator->generate('app_inicio'));
        $event->setResponse($response);
    }
}
