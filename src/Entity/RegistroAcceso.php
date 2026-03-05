<?php

namespace App\Entity;

use App\Repository\RegistroAccesoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad encargada de almacenar la auditoría del sistema.
 * Registra quién, cuándo, desde dónde y qué acción se realizó.
 */
#[ORM\Entity(repositoryClass: RegistroAccesoRepository::class)]
class RegistroAcceso
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * TAREA 4 - PUNTO 18: Momento exacto de la acción.
     * Usamos DATETIME_MUTABLE para facilitar comparaciones en filtros de fecha.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fecha = null;

    /**
     * TAREA 4 - PUNTO 18: Identificación del usuario.
     * Guardamos el email o el UserIdentifier para saber quién realizó la acción.
     */
    #[ORM\Column(length: 255)]
    private ?string $usuarioEmail = null;

    /**
     * TAREA 4 - PUNTO 18: Descripción de la actividad.
     * Ejemplo: "LOGIN_SUCCESS", "ACCESO_DENEGADO", "PACIENTE_EDITAR".
     */
    #[ORM\Column(length: 255)]
    private ?string $accion = null;

    /**
     * TAREA 4 - PUNTO 18: Dirección IP de origen.
     * Longitud 45 para soportar tanto IPv4 como IPv6.
     */
    #[ORM\Column(length: 45)]
    private ?string $ip = null;

    /**
     * TAREA 4 - PUNTO 18: Resultado de la autorización.
     * TRUE si se permitió el acceso, FALSE si fue bloqueado o fallido.
     */
    #[ORM\Column]
    private ?bool $exito = null;

    // --- GETTERS Y SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;
        return $this;
    }

    public function getUsuarioEmail(): ?string
    {
        return $this->usuarioEmail;
    }

    public function setUsuarioEmail(string $usuarioEmail): static
    {
        $this->usuarioEmail = $usuarioEmail;
        return $this;
    }

    public function getAccion(): ?string
    {
        return $this->accion;
    }

    public function setAccion(string $accion): static
    {
        $this->accion = $accion;
        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): static
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Usamos isExito() para seguir la convención de Symfony para booleanos.
     */
    public function isExito(): ?bool
    {
        return $this->exito;
    }

    public function setExito(bool $exito): static
    {
        $this->exito = $exito;
        return $this;
    }
}
