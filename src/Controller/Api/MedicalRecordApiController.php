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
 * Este controlador gestiona los datos sensibles y el historial clínico.
 * Está protegido: solo usuarios con ROLE_MEDICO o ROLE_ADMIN pueden entrar.
 */
#[Route('/api/records')]
#[OA\Tag(name: 'Historiales Clínicos', description: 'Operaciones exclusivas para personal médico')]
class MedicalRecordApiController extends AbstractController
{
    /**
     * MÉTODOS GET: Obtener información del historial.
     */
    #[Route('/{id}', name: 'api_medical_record_show', methods: ['GET'])]
    #[IsGranted('ROLE_MEDICO')]
    #[OA\Get(
        summary: 'Consultar historial clínico',
        description: 'Muestra los datos médicos y personales de un paciente por su ID.',
        responses: [
            new OA\Response(response: 200, description: 'Historial encontrado'),
            new OA\Response(response: 404, description: 'El paciente no existe'),
            new OA\Response(response: 401, description: 'Token JWT no válido o ausente')
        ]
    )]
    public function show(int $id, PacienteRepository $pacienteRepository): JsonResponse
    {
        $paciente = $pacienteRepository->find($id);

        if (!$paciente) {
            return new JsonResponse(['error' => 'Paciente no encontrado'], 404);
        }

        return new JsonResponse([
            'id'                => $paciente->getId(),
            'paciente'          => $paciente->getNombre() . ' ' . $paciente->getApellidos(),
            'dni'               => $paciente->getDni(),
            'historial_clinico' => $paciente->getHistorialClinico(),
            'status'            => 'success'
        ], 200);
    }

    /**
     * MÉTODO PUT: Actualizar el historial médico.
     * Aquí es donde el médico escribe sus nuevas notas.
     */
    #[Route('/{id}', name: 'api_medical_record_update', methods: ['PUT'])]
    #[IsGranted('ROLE_MEDICO')]
    #[OA\Put(
        summary: 'Actualizar historial clínico',
        description: 'Permite a un médico modificar las notas del historial clínico.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'historial_clinico', type: 'string', example: 'El paciente presenta mejora tras el tratamiento...')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Historial actualizado correctamente'),
            new OA\Response(response: 400, description: 'Datos inválidos')
        ]
    )]
    public function update(int $id, Request $request, PacienteRepository $pacienteRepository, EntityManagerInterface $em): JsonResponse
    {
        $paciente = $pacienteRepository->find($id);

        if (!$paciente) {
            return new JsonResponse(['error' => 'Paciente no encontrado'], 404);
        }

        // 1. Obtenemos los datos del cuerpo de la petición (JSON)
        $data = json_decode($request->getContent(), true);

        // 2. Validamos que el campo 'historial_clinico' exista
        if (!isset($data['historial_clinico'])) {
            return new JsonResponse(['error' => 'Falta el campo historial_clinico'], 400);
        }

        // 3. Actualizamos la entidad
        $paciente->setHistorialClinico($data['historial_clinico']);

        // 4. Guardamos los cambios en la Base de Datos
        $em->flush();

        return new JsonResponse([
            'message' => 'Historial clínico actualizado con éxito',
            'nuevo_historial' => $paciente->getHistorialClinico()
        ], 200);
    }
}
