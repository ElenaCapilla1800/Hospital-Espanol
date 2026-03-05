# Sistema Hospitalario - Seguridad Avanzada

Este proyecto implementa un sistema de gestión de historiales clínicos con Symfony 6/7.

## Características Implementadas

- **Autenticación Híbrida**: Formulario Web (Session) y API REST (JWT).
- **Seguridad**: Rate Limiting (bloqueo tras 5 intentos), protección CSRF y Voters de acceso.
- **Auditoría**: Log completo de accesos con IP, usuario y éxito/fallo.
- **Informes**: Exportación filtrada a PDF y Excel (PhpSpreadsheet / Dompdf).

## Credenciales de Acceso

- **Admin**: `admin@hospital.es` / `123456_789`
- **Médico**: `medico@hospital.es` / `123456_789`

## Instalación rápida

1. `ddev start`
2. `ddev composer install`
3. `ddev php bin/console doctrine:migrations:migrate`
4. `ddev php bin/console doctrine:fixtures:load`
5. `ddev php bin/console lexik:jwt:generate-keypair`
