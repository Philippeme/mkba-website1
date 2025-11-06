<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProjectRepository $projectRepository): Response
    {
        $projects = $projectRepository->findHomePageProjects();
        
        return $this->render('home/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/project/{id}', name: 'app_project_show', requirements: ['id' => '\d+'])]
    public function showProject(int $id, ProjectRepository $projectRepository): Response
    {
        $project = $projectRepository->find($id);
        
        if (!$project || !$project->isPublished()) {
            throw $this->createNotFoundException('Le projet demandé n\'existe pas.');
        }
        
        // Récupération du projet précédent et suivant
        $previousProject = $projectRepository->findPreviousProject($project);
        $nextProject = $projectRepository->findNextProject($project);
        
        // Récupération des projets similaires
        $similarProjects = $projectRepository->findSimilarProjects($project, 3);
        
        return $this->render('project/show.html.twig', [
            'project' => $project,
            'previousProject' => $previousProject,
            'nextProject' => $nextProject,
            'similarProjects' => $similarProjects,
        ]);
    }
}