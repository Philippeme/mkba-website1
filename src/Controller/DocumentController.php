<?php

namespace App\Controller;

use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentController extends AbstractController
{
    #[Route('/documents', name: 'app_documents')]
    public function index(Request $request, DocumentRepository $documentRepository): Response
    {
        // Récupération des filtres depuis la requête
        $filters = [
            'procedure' => $request->query->get('procedure'),
            'search' => $request->query->get('search'),
        ];

        // Récupération des projets avec filtres
        $documents = $documentRepository->findPublishedDocumentsWithFilters($filters);
        
        // Récupération des catégories uniques pour le filtre
        $procedures = $proceduredocumentRepository->findUniqueProcedures();
        
        return $this->render('document/index.html.twig', [
            'procedures' => $procedures,
            'currentFilters' => $filters,
        ]);
    }
}