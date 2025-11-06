<?php

namespace App\Controller;

use App\Repository\ProcedureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProcedureController extends AbstractController
{
    #[Route('/procedures', name: 'app_procedures')]
    public function index(Request $request, ProcedureRepository $procedureRepository): Response
    {
        // Récupération des filtres depuis la requête
        $filters = [
            'family' => $request->query->get('family'),
            'search' => $request->query->get('search'),
        ];

        // Récupération des projets avec filtres
        $procedures = $procedureRepository->findPublishedProceduresWithFilters($filters);
        
        // Récupération des catégories uniques pour le filtre
        $families = $procedureRepository->findUniqueFamilies();
        
        return $this->render('procedure/index.html.twig', [
            'families' => $families,
            'currentFilters' => $filters,
        ]);
    }
}