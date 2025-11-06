<?php

namespace App\Controller;

use App\Repository\FamilyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FamilyController extends AbstractController
{
    #[Route('/families', name: 'app_families')]
    public function index(Request $request, FamilyRepository $familyRepository): Response
    {
        // Récupération des filtres depuis la requête
        $filters = [
            'procedure' => $request->query->get('procedure'),
            'search' => $request->query->get('search'),
        ];

        // Récupération des projets avec filtres
        $families = $familyRepository->findPublishedFamiliesWithFilters($filters);
        
        // Récupération des catégories uniques pour le filtre
        $procedures = $procedurefamilyRepository->findUniqueProcedures();
        
        return $this->render('family/index.html.twig', [
            'procedures' => $procedures,
            'currentFilters' => $filters,
        ]);
    }
}