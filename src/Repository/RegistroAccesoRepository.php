<?php

namespace App\Repository;

use App\Entity\RegistroAcceso;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * REPOSITORIO DE AUDITORÍA (RegistroAcceso)
 * Aquí centralizamos todas las consultas a la tabla de logs.
 * * @extends ServiceEntityRepository<RegistroAcceso>
 */
class RegistroAccesoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegistroAcceso::class);
    }

    /**
     * TAREA 5 - PUNTO 24: Informe de accesos sospechosos de las últimas 24h.
     * Este método busca específicamente los "fallos" (denegados) recientes.
     * * @return RegistroAcceso[]
     */
    public function buscarAccesosSospechosos24h(): array
    {
        // 1. Calculamos el tiempo exacto (24 horas atrás desde este momento)
        // Usamos DateTime normal para asegurar compatibilidad con el tipo de columna en la BD
        $hace24Horas = new \DateTime('-24 hours');

        // 2. Usamos el QueryBuilder para filtrar la base de datos
        return $this->createQueryBuilder('r')
            // Filtramos solo registros donde 'exito' sea false (denegados/fallidos)
            ->andWhere('r.exito = :exito')
            // Filtramos registros cuya fecha sea igual o posterior a hace 24h
            ->andWhere('r.fecha >= :fecha')
            // Seteamos los parámetros de forma segura (Previene SQL Injection)
            ->setParameter('exito', false)
            ->setParameter('fecha', $hace24Horas)
            // Ordenamos: lo más reciente aparece arriba del todo
            ->orderBy('r.fecha', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * TAREA 4 - PUNTO 19: Implementar filtros de búsqueda por usuario, fecha y tipo de acción.
     * Este método es el que alimenta el motor de búsqueda del panel de administrador.
     * * @param string|null $usuario Email del usuario
     * @param string|null $fecha Fecha en formato YYYY-MM-DD
     * @param string|null $accion Nombre de la acción (ver, editar, borrar)
     * @return RegistroAcceso[]
     */
    public function filtrarLogs(?string $usuario, ?string $fecha, ?string $accion): array
    {
        // Iniciamos el constructor de consultas con el alias 'r'
        $qb = $this->createQueryBuilder('r');

        // FILTRO 1: Por UsuarioEmail (CORRECCIÓN: Debe coincidir con la propiedad de la Entidad)
        if ($usuario) {
            $qb->andWhere('r.usuarioEmail LIKE :u')
                ->setParameter('u', '%' . $usuario . '%');
        }

        // FILTRO 2: Por Fecha (Cubre el día completo de 00:00 a 23:59)
        if ($fecha) {
            try {
                // Definimos el inicio y fin del día seleccionado
                $fechaInicio = new \DateTime($fecha . ' 00:00:00');
                $fechaFin = new \DateTime($fecha . ' 23:59:59');

                $qb->andWhere('r.fecha BETWEEN :inicio AND :fin')
                    ->setParameter('inicio', $fechaInicio)
                    ->setParameter('fin', $fechaFin);
            } catch (\Exception $e) {
                // Si la fecha enviada no tiene formato válido, ignoramos este filtro silenciosamente
            }
        }

        // FILTRO 3: Por Acción (Mejorado con LIKE para que busque "VER" dentro de "PACIENTE_VER")
        if ($accion) {
            $qb->andWhere('r.accion LIKE :acc')
                ->setParameter('acc', '%' . $accion . '%');
        }

        // Devolvemos el resultado ordenado cronológicamente (lo más nuevo primero)
        return $qb->orderBy('r.fecha', 'DESC')
                    ->getQuery()
                    ->getResult();
    }
}
