<?php

namespace App\Controller\Api;

use App\Repository\PacienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

/**
 * CONTROLADOR DE API PARA PACIENTES
 * Gestiona búsquedas inteligentes (DNI/Nombre) y actualizaciones.
 */
#[Route('/api/pacientes')]
#[OA\Tag(name: 'Pacientes', description: 'Consultas por DNI, Nombre o Listado')]
class PacienteApiController extends AbstractController
{
    /**
     * 1. LISTADO GENERAL SEGURO
     * Hemos "formateado" manualmente los datos para evitar el error 500
     * que suelen causar los objetos de tipo Fecha o las relaciones complejas.
     */
    #[Route('', name: 'api_paciente_listado', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(summary: 'Listado completo de pacientes registrados')]
    public function index(PacienteRepository $pacienteRepository): JsonResponse
    {
        try {
            $pacientes = $pacienteRepository->findAll();
            $data = [];

            foreach ($pacientes as $p) {
                $data[] = [
                    'id' => $p->getId(),
                    'nombre_completo' => $p->getNombre() . ' ' . $p->getApellidos(),
                    'dni' => $p->getDni(),
                    // Convertimos el objeto DateTime a un texto legible para evitar el Error 500
                    'fecha_nac' => $p->getFechaNacimiento() ? $p->getFechaNacimiento()->format('d/m/Y') : null
                ];
            }

            return new JsonResponse($data, 200);
        } catch (\Exception $e) {
            // Si algo falla, atrapamos el error y lo mostramos en el JSON
            return new JsonResponse(['error' => 'Error al listar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 2. BUSCAR POR DNI
     * Sustituimos la búsqueda por ID por el DNI, que es el identificador real.
     */
    #[Route('/dni/{dni}', name: 'api_paciente_por_dni', methods: ['GET'])]
    #[IsGranted('ROLE_MEDICO')]
    #[OA\Get(
        summary: 'Obtener ficha por DNI',
        description: 'Introduce el DNI del paciente (con letra) para ver sus datos.'
    )]
    public function buscarPorDni(string $dni, PacienteRepository $pacienteRepository): JsonResponse
    {
        // Buscamos directamente por la columna 'dni' en la base de datos
        $paciente = $pacienteRepository->findOneBy(['dni' => $dni]);

        if (!$paciente) {
            return new JsonResponse(['error' => 'No se encontró ningún paciente con DNI: ' . $dni], 404);
        }

        return new JsonResponse([
            'nombre'    => $paciente->getNombre(),
            'apellidos' => $paciente->getApellidos(),
            'dni'       => $paciente->getDni(),
            'historial' => $paciente->getHistorialClinico(),
            'status'    => 'success'
        ], 200);
    }

    /**
     * 3. BUSCAR POR NOMBRE O APELLIDO (Búsqueda parcial)
     * Permite buscar "Juan" y que aparezcan todos los Juanes.
     */
    #[Route('/buscar/{termino}', name: 'api_paciente_busqueda', methods: ['GET'])]
    #[IsGranted('ROLE_MEDICO')]
    #[OA\Get(summary: 'Búsqueda por nombre o apellido (Texto parcial)')]
    public function buscarPorNombre(string $termino, PacienteRepository $pacienteRepository): JsonResponse
    {
        // Usamos QueryBuilder para hacer un "LIKE" (buscar coincidencias parciales)
        $pacientes = $pacienteRepository->createQueryBuilder('p')
            ->where('p.nombre LIKE :t OR p.apellidos LIKE :t')
            ->setParameter('t', '%'.$termino.'%')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($pacientes as $p) {
            $data[] = [
                'dni'    => $p->getDni(),
                'nombre' => $p->getNombre() . ' ' . $p->getApellidos()
            ];
        }

        return new JsonResponse($data, 200);
    }

    /**
     * 4. ACTUALIZAR HISTORIAL POR DNI (PUT)
     * Ya no pedimos el ID, ahora actualizamos buscando por DNI.
     */
    #[Route('/dni/{dni}/historial', name: 'api_paciente_update_historial', methods: ['PUT'])]
    #[IsGranted('ROLE_MEDICO')]
    #[OA\Put(
        summary: 'Actualizar historial usando el DNI',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'historial_clinico', type: 'string', example: 'Nueva nota médica...')
                ]
            )
        )
    )]
    public function actualizarHistorial(string $dni, Request $request, PacienteRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $paciente = $repo->findOneBy(['dni' => $dni]);

        if (!$paciente) {
            return new JsonResponse(['error' => 'Paciente no localizado'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['historial_clinico'])) {
            return new JsonResponse(['error' => 'El campo historial_clinico es obligatorio'], 400);
        }

        $paciente->setHistorialClinico($data['historial_clinico']);
        $em->flush(); // Guardamos cambios en BD

        return new JsonResponse(['message' => 'Historial de ' . $paciente->getNombre() . ' actualizado.'], 200);
    }
}
