<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MedicoController extends AbstractController
{
    #[Route('/medico', name: 'app_medico')]
    public function index(): Response
    {
        return $this->render('medico/index.html.twig', [
            'controller_name' => 'MedicoController',
        ]);
    }
}
