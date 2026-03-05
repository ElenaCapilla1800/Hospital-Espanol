<?php

namespace App\Entity;

use App\Repository\PacienteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types; // Importante para campos de texto largo
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad Paciente: Representa la tabla de pacientes en la base de datos.
 */
#[ORM\Entity(repositoryClass: PacienteRepository::class)]
class Paciente
{
    #[ORM\Id] // Identificador único (clave primaria)
    #[ORM\GeneratedValue] // Autoincremental
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)] // Nombre del paciente
    private ?string $nombre = null;

    #[ORM\Column(length: 255)] // Apellidos del paciente
    private ?string $apellidos = null;

    #[ORM\Column(length: 20, unique: true)] // DNI único para evitar duplicados
    private ?string $dni = null;

    #[ORM\Column(length: 20, nullable: true)] // Teléfono (puede quedar vacío)
    private ?string $telefono = null;

    #[ORM\Column(type: 'datetime_immutable')] // Fecha de nacimiento (formato fecha de PHP)
    private ?\DateTimeImmutable $fechaNacimiento = null;

    // --- LA PROPIEDAD QUE TE FALTABA ---
    #[ORM\Column(type: Types::TEXT, nullable: true)] // Campo de texto largo para notas médicas
    private ?string $historialClinico = null;

    /**
     * Relación con la entidad HistorialMedico (Varios registros para un paciente)
     * @var Collection<int, HistorialMedico>
     */
    #[ORM\OneToMany(targetEntity: HistorialMedico::class, mappedBy: 'paciente')]
    private Collection $historialMedicos;

    public function __construct()
    {
        // Inicializamos la colección para que no sea nula al crear un paciente nuevo
        $this->historialMedicos = new ArrayCollection();
    }

    // --- MÉTODOS GET Y SET (Acceso a los datos) ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getApellidos(): ?string { return $this->apellidos; }
    public function setApellidos(string $apellidos): static
    {
        $this->apellidos = $apellidos;
        return $this;
    }

    public function getDni(): ?string { return $this->dni; }
    public function setDni(string $dni): static
    {
        $this->dni = $dni;
        return $this;
    }

    public function getTelefono(): ?string { return $this->telefono; }
    public function setTelefono(?string $telefono): static
    {
        $this->telefono = $telefono;
        return $this;
    }

    public function getFechaNacimiento(): ?\DateTimeImmutable { return $this->fechaNacimiento; }
    public function setFechaNacimiento(\DateTimeImmutable $fechaNacimiento): static
    {
        $this->fechaNacimiento = $fechaNacimiento;
        return $this;
    }

    // --- MÉTODOS PARA EL HISTORIAL CLÍNICO (NUEVO) ---

    /**
     * Twig lo usará al poner {{ paciente.historialClinico }}
     */
    public function getHistorialClinico(): ?string
    {
        return $this->historialClinico;
    }

    /**
     * El formulario lo usará para guardar el texto
     */
    public function setHistorialClinico(?string $historialClinico): static
    {
        $this->historialClinico = $historialClinico;
        return $this;
    }

    // --- MÉTODOS PARA LA RELACIÓN EXTERNA (HistorialMedicos) ---

    public function getHistorialMedicos(): Collection
    {
        return $this->historialMedicos;
    }

    public function addHistorialMedico(HistorialMedico $historialMedico): static
    {
        if (!$this->historialMedicos->contains($historialMedico)) {
            $this->historialMedicos->add($historialMedico);
            $historialMedico->setPaciente($this);
        }
        return $this;
    }

    public function removeHistorialMedico(HistorialMedico $historialMedico): static
    {
        if ($this->historialMedicos->removeElement($historialMedico)) {
            if ($historialMedico->getPaciente() === $this) {
                $historialMedico->setPaciente(null);
            }
        }
        return $this;
    }
}
