<?php

namespace App\Security;

use App\Repository\UsuarioRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Esta clase es el "Segurata" del hospital.
 * Gestiona el proceso de login y ahora también verifica si la cuenta está bloqueada.
 */
class AutenticadorLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    /**
     * Inyectamos el Generador de URLs y el Repositorio de Usuarios.
     * El repositorio es necesario para buscar al usuario y ver si está activo.
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UsuarioRepository $usuarioRepository
    ) {
    }

    /**
     * PASO 1: Autenticación.
     * Extrae los datos y verifica el estado de la cuenta (Tarea 5 - Punto 22).
     */
    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        // 1. Buscamos al usuario en la base de datos antes de seguir
        $user = $this->usuarioRepository->findOneBy(['email' => $email]);

        // 2. TAREA 5 - PUNTO 22: BLOQUEO DE CUENTA
        // Si el usuario existe pero su campo 'activo' es false, lanzamos una excepción.
        // Symfony capturará esto y mostrará el mensaje en el formulario de login.
        if ($user && !$user->isActivo()) {
            throw new CustomUserMessageAuthenticationException(
                'Tu cuenta ha sido bloqueada tras 5 intentos fallidos. Contacta con el administrador.'
            );
        }

        // 3. Guardamos el email para que no tenga que reescribirlo si falla la contraseña
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // 4. Devolvemos el Pasaporte para que Symfony verifique la contraseña
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    /**
     * PASO 2: Éxito en la Autenticación.
     * Se ejecuta si el usuario está activo y la contraseña es correcta.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Si el usuario intentaba entrar a una página protegida antes de loguearse, lo mandamos allí
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Si no, lo mandamos a la página de inicio del hospital
        return new RedirectResponse($this->urlGenerator->generate('app_inicio'));
    }

    /**
     * Define la URL a la que Symfony debe redirigir si se requiere login.
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
