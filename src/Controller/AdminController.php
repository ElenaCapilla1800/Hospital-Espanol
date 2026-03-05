<?php

namespace App\Controller;

use App\Repository\RegistroAccesoRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CONTROLADOR ADMINISTRATIVO: AUDITORÍA Y SEGURIDAD
 * Blindado para ROLE_ADMIN. Gestiona la visualización y exportación de logs filtrados.
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    /**
     * VISTA GENERAL DE AUDITORÍA
     * Carga los logs aplicando filtros si existen.
     */
    #[Route('/logs', name: 'app_admin_logs', methods: ['GET'])]
    public function visorLogs(Request $peticion, RegistroAccesoRepository $repositorio): Response
    {
        // 1. Capturamos los filtros de la URL
        $usuario = $peticion->query->get('usuario');
        $fecha   = $peticion->query->get('fecha');
        $accion  = $peticion->query->get('accion');

        // 2. Aplicamos lógica de filtrado o carga general
        if ($usuario || $fecha || $accion) {
            $todosLosLogs = $repositorio->filtrarLogs($usuario, $fecha, $accion);
        } else {
            $todosLosLogs = $repositorio->findBy([], ['fecha' => 'DESC']);
        }

        // 3. Alertas de seguridad de las últimas 24h
        $accesosSospechosos = $repositorio->buscarAccesosSospechosos24h();

        return $this->render('admin/logs.html.twig', [
            'logs'            => $todosLosLogs,
            'sospechosos'     => $accesosSospechosos,
            'total_alertas'   => count($accesosSospechosos),
            'filtro_usuario'  => $usuario,
            'filtro_fecha'    => $fecha,
            'filtro_accion'   => $accion
        ]);
    }

    /**
     * EXPORTAR A PDF (FILTRADO)
     * Genera un PDF basado exactamente en la búsqueda actual del usuario.
     */
    #[Route('/informe/pdf', name: 'app_admin_reporte_pdf')]
    public function exportarPdf(Request $peticion, RegistroAccesoRepository $repositorio): Response
    {
        // 1. Obtenemos los filtros de la petición actual para que el PDF coincida con lo que se ve en pantalla
        $usuario = $peticion->query->get('usuario');
        $fecha   = $peticion->query->get('fecha');
        $accion  = $peticion->query->get('accion');

        // 2. Buscamos los datos aplicando esos filtros
        if ($usuario || $fecha || $accion) {
            $datos = $repositorio->filtrarLogs($usuario, $fecha, $accion);
        } else {
            // Si no hay filtros, por defecto exportamos los sospechosos de 24h o todo el log
            $datos = $repositorio->findBy([], ['fecha' => 'DESC']);
        }

        // 3. Configuración de PDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        $html = $this->renderView('admin/export/pdf.html.twig', [
            'alertas' => $datos,
            'fecha' => new \DateTime(),
            'titulo' => ($usuario || $fecha || $accion) ? "Informe Filtrado de Auditoría" : "Informe General de Auditoría"
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="informe_auditoria.pdf"'
        ]);
    }

    /**
     * EXPORTAR A EXCEL (FILTRADO)
     * Crea un Excel con los resultados de la búsqueda actual.
     */
    #[Route('/informe/excel', name: 'app_admin_reporte_excel')]
    public function exportarExcel(Request $peticion, RegistroAccesoRepository $repositorio): Response
    {
        // 1. Capturamos los filtros actuales
        $usuario = $peticion->query->get('usuario');
        $fecha   = $peticion->query->get('fecha');
        $accion  = $peticion->query->get('accion');

        // 2. Obtenemos datos según los filtros
        if ($usuario || $fecha || $accion) {
            $datos = $repositorio->filtrarLogs($usuario, $fecha, $accion);
        } else {
            $datos = $repositorio->findBy([], ['fecha' => 'DESC']);
        }

        // 3. Construcción del Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Auditoría Filtrada');

        // Cabeceras
        $sheet->setCellValue('A1', 'FECHA Y HORA');
        $sheet->setCellValue('B1', 'USUARIO (EMAIL)');
        $sheet->setCellValue('C1', 'ACCIÓN');
        $sheet->setCellValue('D1', 'DIRECCIÓN IP');
        $sheet->setCellValue('E1', 'ÉXITO');

        // Llenado de filas
        $fila = 2;
        foreach ($datos as $log) {
            $sheet->setCellValue('A' . $fila, $log->getFecha()->format('d/m/Y H:i:s'));
            $sheet->setCellValue('B' . $fila, $log->getUsuarioEmail());
            $sheet->setCellValue('C' . $fila, $log->getAccion());
            $sheet->setCellValue('D' . $fila, $log->getIp());
            $sheet->setCellValue('E' . $fila, $log->isExito() ? 'SÍ' : 'NO');
            $fila++;
        }

        // Auto-ajuste de columnas
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'export_excel');
        $writer->save($tempFile);

        return $this->file($tempFile, 'informe_auditoria.xlsx', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }
}
