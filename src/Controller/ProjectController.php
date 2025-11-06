<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends AbstractController
{
    #[Route('/projects', name: 'app_projects')]
    public function index(Request $request, ProjectRepository $projectRepository): Response
    {
        // Récupération des filtres depuis la requête
        $filters = [
            'category' => $request->query->get('category'),
            'year' => $request->query->get('year'),
            'search' => $request->query->get('search'),
        ];

        // Récupération des projets avec filtres
        $projects = $projectRepository->findPublishedProjectsWithFilters($filters);
        
        // Récupération des catégories uniques pour le filtre
        $categories = $projectRepository->findUniqueCategories();
        
        // Récupération des années uniques pour le filtre
        $years = $projectRepository->findUniqueYears();
        
        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'categories' => $categories,
            'years' => $years,
            'currentFilters' => $filters,
        ]);
    }
}