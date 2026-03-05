<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * La clase Usuario representa a cualquier persona que entra al sistema.
 * Implementa UserInterface para que Symfony gestione la seguridad.
 */
#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $nombreCompleto = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    // --- TAREA 5 - PUNTO 22: SEGURIDAD AVANZADA (BLOQUEO) ---

    /**
     * @ORM\Column(type: "boolean")
     * Determina si la cuenta está activa o bloqueada.
     */
    #[ORM\Column]
    private bool $activo = true;

    /**
     * @ORM\Column(type: "integer")
     * Contador de fallos consecutivos al meter la contraseña.
     */
    #[ORM\Column]
    private int $intentosFallidos = 0;

    // --- MÉTODOS EXISTENTES ---

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getNombreCompleto(): ?string { return $this->nombreCompleto; }

    public function setNombreCompleto(string $nombreCompleto): static
    {
        $this->nombreCompleto = $nombreCompleto;
        return $this;
    }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string { return $this->password; }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    // --- NUEVOS MÉTODOS PARA EL BLOQUEO (Getters y Setters) ---

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;
        return $this;
    }

    public function getIntentosFallidos(): int
    {
        return $this->intentosFallidos;
    }

    public function setIntentosFallidos(int $intentosFallidos): static
    {
        $this->intentosFallidos = $intentosFallidos;
        return $this;
    }

    /**
     * Método de conveniencia para resetear el contador cuando el login es un éxito.
     */
    public function resetIntentos(): void
    {
        $this->intentosFallidos = 0;
    }

    // --- SERIALIZACIÓN Y LIMPIEZA ---

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        return $data;
    }

    public function eraseCredentials(): void { }
}
