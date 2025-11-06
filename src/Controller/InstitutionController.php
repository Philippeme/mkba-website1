<?php

namespace App\Controller;

use App\Repository\InstitutionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InstitutionController extends AbstractController
{
    #[Route('/institutions', name: 'app_institutions')]
    public function index(Request $request, InstitutionRepository $institutionRepository): Response
    {
        // Récupération des filtres depuis la requête
        $filters = [
            'department' => $request->query->get('department'),
            'search' => $request->query->get('search'),
        ];

        // Récupération des projets avec filtres
        $institutions = $institutionRepository->findPublishedInstitutionsWithFilters($filters);
        
        // Récupération des catégories uniques pour le filtre
        $departements = $institutionRepository->findUniqueDepartments();
        
        return $this->render('institution/index.html.twig', [
            'departements' => $departements,
            'currentFilters' => $filters,
        ]);
    }
}