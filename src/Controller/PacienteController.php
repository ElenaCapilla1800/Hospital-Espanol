<?php

namespace App\Controller;

use App\Entity\Paciente;
use App\Form\PacienteType;
use App\Repository\PacienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CONTROLADOR WEB DE PACIENTES
 * * Este controlador maneja las vistas HTML (Twig).
 * Está protegido: Solo usuarios con ROLE_MEDICO o superior pueden acceder.
 */
#[Route('/paciente')]
#[IsGranted('ROLE_MEDICO')]
class PacienteController extends AbstractController
{
    /**
     * 1. LISTADO GENERAL
     * Muestra la tabla con todos los pacientes registrados.
     */
    #[Route('/', name: 'app_paciente_listado', methods: ['GET'])]
    public function listado(PacienteRepository $repositorio): Response
    {
        return $this->render('paciente/index.html.twig', [
            'pacientes' => $repositorio->findAll(),
        ]);
    }

    /**
     * 2. REGISTRO DE NUEVO PACIENTE
     * Crea un objeto vacío, le vincula el formulario y lo guarda en la BD.
     */
    #[Route('/nuevo', name: 'app_paciente_nuevo', methods: ['GET', 'POST'])]
    public function nuevo(Request $peticion, EntityManagerInterface $manejadorBaseDatos): Response
    {
        $paciente = new Paciente();
        $formulario = $this->createForm(PacienteType::class, $paciente);

        // Procesamos la información que viene del navegador
        $formulario->handleRequest($peticion);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            // Decimos a Doctrine que queremos guardar este objeto
            $manejadorBaseDatos->persist($paciente);
            // Ejecutamos la sentencia SQL definitiva
            $manejadorBaseDatos->flush();

            $this->addFlash('success', '¡Paciente ' . $paciente->getNombre() . ' registrado con éxito!');
            return $this->redirectToRoute('app_paciente_listado');
        }

        return $this->render('paciente/nuevo.html.twig', [
            'paciente' => $paciente,
            'formulario_paciente' => $formulario->createView(),
            'modo_edicion' => false
        ]);
    }

    /**
     * 3. FICHA DETALLADA (AUDITORÍA MEDIANTE VOTER)
     * Cuando usamos 'PACIENTE_VER', el Voter registra automáticamente
     * quién ha mirado este historial médico.
     */
    #[Route('/{id}', name: 'app_paciente_ver', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function ver(Paciente $paciente): Response
    {
        // El Voter intercepta esta llamada:
        // 1. Verifica si el médico tiene permiso.
        // 2. Crea un registro en la tabla 'RegistroAcceso' (Auditoría).
        $this->denyAccessUnlessGranted('PACIENTE_VER', $paciente);

        return $this->render('paciente/ver.html.twig', [
            'paciente' => $paciente,
        ]);
    }

    /**
     * 4. EDICIÓN DE DATOS (CON VOTER)
     * Similar al registro, pero sobre un paciente que ya existe en la BD.
     */
    #[Route('/{id}/editar', name: 'app_paciente_editar', methods: ['GET', 'POST'])]
    public function editar(Request $peticion, Paciente $paciente, EntityManagerInterface $manejadorBaseDatos): Response
    {
        // El Voter registra que el médico ha entrado en modo edición
        $this->denyAccessUnlessGranted('PACIENTE_EDITAR', $paciente);

        $formulario = $this->createForm(PacienteType::class, $paciente);
        $formulario->handleRequest($peticion);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            // Solo necesitamos flush(), porque el objeto ya está 'persistido'
            $manejadorBaseDatos->flush();
            $this->addFlash('success', 'Datos del paciente actualizados.');

            return $this->redirectToRoute('app_paciente_ver', ['id' => $paciente->getId()]);
        }

        return $this->render('paciente/nuevo.html.twig', [
            'paciente' => $paciente,
            'formulario_paciente' => $formulario->createView(),
            'modo_edicion' => true // Ayuda a Twig a cambiar el título de la página
        ]);
    }

    /**
     * 5. ELIMINAR PACIENTE (SEGURIDAD CRÍTICA)
     * Solo permite borrar mediante POST y validando un Token CSRF
     * para evitar que alguien borre pacientes mediante enlaces falsos.
     */
    #[Route('/{id}/eliminar', name: 'app_paciente_eliminar', methods: ['POST'])]
    public function eliminar(Request $peticion, Paciente $paciente, EntityManagerInterface $manejadorBaseDatos): Response
    {
        // Verificamos el Token de seguridad enviado desde el formulario de borrado
        $token = $peticion->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$paciente->getId(), $token)) {

            $nombreCompleto = $paciente->getNombre() . ' ' . $paciente->getApellidos();

            $manejadorBaseDatos->remove($paciente);
            $manejadorBaseDatos->flush();

            $this->addFlash('success', "El paciente $nombreCompleto ha sido eliminado definitivamente.");
        } else {
            $this->addFlash('error', 'Token de seguridad no válido. No se pudo eliminar.');
        }

        return $this->redirectToRoute('app_paciente_listado');
    }
}
