<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Entity\ProjectTranslation;
use App\Entity\ProjectMember;
use App\Entity\ProjectLink;
use App\Entity\ProjectAttachment;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;
use Psr\Log\LoggerInterface;

// CORRECTION: Imports pour les exports
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/project')]
#[IsGranted('ROLE_ADMIN')]
class ProjectController extends AbstractController
{
    private const FLASH_SUCCESS = 'success';
    private const FLASH_ERROR = 'error';
    private const FLASH_WARNING = 'warning';
    private const FLASH_INFO = 'info';

    // CORRECTION: Injection de dépendances complète
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository,
        private SluggerInterface $slugger,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private Environment $twig,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'admin_project_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $locale = $request->getLocale() ?: 'fr';
        $searchTerm = $request->query->get('search');
        $filters = $request->query->all();

        if ($searchTerm) {
            $projects = $this->projectRepository->searchProjects($searchTerm, $locale);
        } elseif (!empty(array_filter($filters, fn($v) => $v !== '' && $v !== null))) {
            $projects = $this->projectRepository->filterProjects($filters, $locale);
        } else {
            $projects = $this->projectRepository->findAllWithTranslations($locale);
        }

        $stats = $this->projectRepository->getGlobalStats();
        $statusCounts = $this->projectRepository->countByStatus();
        $categoryCounts = $this->projectRepository->countByCategory();

        $csrfTokens = [];
        foreach ($projects as $project) {
            $csrfTokens[$project->getId()] = $this->csrfTokenManager->getToken('delete' . $project->getId())->getValue();
        }

        return $this->render('admin/project/index.html.twig', [
            'projects' => $projects,
            'stats' => $stats,
            'statusCounts' => $statusCounts,
            'categoryCounts' => $categoryCounts,
            'currentLocale' => $locale,
            'csrfTokens' => $csrfTokens
        ]);
    }

    #[Route('/new', name: 'admin_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $project = new Project();
        $currentLocale = $request->get('locale', $request->getLocale() ?: 'fr');
        
        $form = $this->createForm(ProjectType::class, $project, [
            'current_locale' => $currentLocale
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du code
            if (!$this->projectRepository->isCodeUnique($project->getCode())) {
                $this->addFlash(self::FLASH_ERROR, 'Ce code projet existe déjà. Veuillez choisir un autre code.');
                return $this->render('admin/project/new.html.twig', [
                    'project' => $project,
                    'form' => $form->createView(),
                    'currentLocale' => $currentLocale
                ]);
            }

            // Traitement de la traduction pour la langue courante
            $translationData = $request->request->all('translation');
            if (!empty($translationData['name'])) {
                $this->saveTranslationForLocale($project, $currentLocale, $translationData);
            }

            // Traitement des relations
            $this->processProjectRelations($project, $request);

            // CORRECTION: Traitement robuste des fichiers uploadés
            try {
                $this->processFileUploads($project, $request);
            } catch (\Exception $e) {
                $this->logError('Erreur upload fichiers création projet', $e);
                $this->addFlash(self::FLASH_WARNING, 'Projet créé mais certains fichiers n\'ont pas pu être uploadés: ' . $e->getMessage());
            }

            try {
                $this->entityManager->persist($project);
                $this->entityManager->flush();

                $this->addFlash(self::FLASH_SUCCESS, sprintf(
                    'Projet "%s" créé avec succès en %s.',
                    $project->getCode(),
                    $currentLocale === 'fr' ? 'français' : 'anglais'
                ));
                
                return $this->redirectToRoute('admin_project_show', [
                    'id' => $project->getId(),
                    'locale' => $currentLocale
                ]);
                
            } catch (\Exception $e) {
                $this->addFlash(self::FLASH_ERROR, 'Erreur lors de la création du projet. Veuillez réessayer.');
                $this->logError('Erreur création projet', $e);
            }
        }

        return $this->render('admin/project/new.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'currentLocale' => $currentLocale
        ]);
    }

    #[Route('/{id}', name: 'admin_project_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Project $project, Request $request): Response
    {
        $locale = $request->query->get('locale', $request->getLocale() ?: 'fr');
        $project = $this->projectRepository->findOneWithAllRelations($project->getId());

        $availableTranslations = $this->getAvailableTranslations($project);
        $supportedLocales = ['fr', 'en'];

        return $this->render('admin/project/show.html.twig', [
            'project' => $project,
            'currentLocale' => $locale,
            'availableTranslations' => $availableTranslations,
            'supportedLocales' => $supportedLocales
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_project_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Project $project): Response
    {
        $currentLocale = $request->get('locale', $request->getLocale() ?: 'fr');
        $project = $this->projectRepository->findOneWithAllRelations($project->getId());
        
        $form = $this->createForm(ProjectType::class, $project, [
            'current_locale' => $currentLocale
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du code (exclure le projet actuel)
            if (!$this->projectRepository->isCodeUnique($project->getCode(), $project->getId())) {
                $this->addFlash(self::FLASH_ERROR, 'Ce code projet existe déjà. Veuillez choisir un autre code.');
                return $this->render('admin/project/edit.html.twig', [
                    'project' => $project,
                    'form' => $form->createView(),
                    'currentLocale' => $currentLocale
                ]);
            }

            // Traitement de la traduction pour la langue courante
            $translationData = $request->request->all('translation');
            if (!empty($translationData['name'])) {
                $this->saveTranslationForLocale($project, $currentLocale, $translationData);
            }

            // Traitement des relations
            $this->processProjectRelations($project, $request);

            // CORRECTION: Traitement robuste des fichiers uploadés et supprimés
            try {
                $this->processFileUploads($project, $request);
                $this->processRemovedAttachments($project, $request);
            } catch (\Exception $e) {
                $this->logError('Erreur upload fichiers modification projet', $e);
                $this->addFlash(self::FLASH_WARNING, 'Projet modifié mais certains fichiers n\'ont pas pu être traités: ' . $e->getMessage());
            }

            try {
                $this->entityManager->flush();
                
                $this->addFlash(self::FLASH_SUCCESS, sprintf(
                    'Projet "%s" modifié avec succès en %s.',
                    $project->getCode(),
                    $currentLocale === 'fr' ? 'français' : 'anglais'
                ));

                return $this->redirectToRoute('admin_project_show', [
                    'id' => $project->getId(),
                    'locale' => $currentLocale
                ]);
                
            } catch (\Exception $e) {
                $this->addFlash(self::FLASH_ERROR, 'Erreur lors de la modification du projet. Veuillez réessayer.');
                $this->logError('Erreur modification projet', $e);
            }
        }

        return $this->render('admin/project/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
            'currentLocale' => $currentLocale,
            'availableTranslations' => $this->getAvailableTranslations($project)
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_project_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Project $project): Response
    {
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            try {
                $projectCode = $project->getCode();
                $project->setIsActive(false);
                $project->setUpdatedAt(new \DateTime());
                
                $this->entityManager->flush();

                $this->addFlash(self::FLASH_SUCCESS, sprintf('Projet "%s" supprimé avec succès.', $projectCode));
            } catch (\Exception $e) {
                $this->addFlash(self::FLASH_ERROR, 'Erreur lors de la suppression du projet. Veuillez réessayer.');
                $this->logError('Erreur suppression projet', $e);
            }
        } else {
            $this->addFlash(self::FLASH_ERROR, 'Token de sécurité invalide. Opération non autorisée.');
        }

        return $this->redirectToRoute('admin_project_index');
    }

    #[Route('/get-translation', name: 'admin_project_get_translation', methods: ['GET'])]
    public function getTranslation(Request $request): JsonResponse
    {
        $projectId = $request->query->get('project_id');
        $locale = $request->query->get('locale');

        if (!$projectId || !$locale) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Paramètres manquants'
            ], 400);
        }

        try {
            $project = $this->projectRepository->findOneWithAllRelations($projectId);
            if (!$project) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Projet non trouvé'
                ], 404);
            }

            $translation = $project->getTranslation($locale);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'name' => $translation ? $translation->getName() : '',
                    'description' => $translation ? $translation->getDescription() : '',
                    'exists' => $translation !== null && !empty($translation->getName()),
                    'members' => $project->getMembers()->map(fn($m) => [
                        'name' => $m->getName(),
                        'email' => $m->getEmail(),
                        'role' => $m->getRole()
                    ])->toArray(),
                    'links' => $project->getLinks()->map(fn($l) => [
                        'title' => $l->getTitle(),
                        'url' => $l->getUrl(),
                        'type' => $l->getType()
                    ])->toArray()
                ]
            ]);
        } catch (\Exception $e) {
            $this->logError('Erreur récupération traduction', $e);
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la traduction'
            ], 500);
        }
    }

    #[Route('/bulk-delete', name: 'admin_project_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $request->request->get('ids', []);
        
        if (empty($ids)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Aucun projet sélectionné'
            ]);
        }

        try {
            $count = $this->projectRepository->softDeleteByIds($ids);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d projet(s) supprimé(s) avec succès', $count)
            ]);
        } catch (\Exception $e) {
            $this->logError('Erreur suppression groupée', $e);
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression groupée'
            ]);
        }
    }

    /**
     * Export Excel avec personnalisation complète
     */
    #[Route('/export-excel', name: 'admin_project_export_excel', methods: ['POST'])]
    public function exportExcel(Request $request): Response
    {
        try {
            $locale = $request->getLocale() ?: 'fr';
            $selectedIds = $request->request->all('ids') ?: [];
            
            // Récupérer les projets à exporter
            if (!empty($selectedIds)) {
                $projects = $this->projectRepository->findForExport($selectedIds, $locale);
                $filename = 'projets_selection_' . date('Y-m-d_H-i-s') . '.xlsx';
            } else {
                $projects = $this->projectRepository->findAllWithTranslations($locale);
                $filename = 'projets_complet_' . date('Y-m-d_H-i-s') . '.xlsx';
            }

            // Créer le classeur Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Projets MK BA');

            // Définir les en-têtes
            $headers = [
                'A1' => 'Code Projet',
                'B1' => 'Nom du Projet',
                'C1' => 'Catégorie', 
                'D1' => 'Priorité',
                'E1' => 'Statut',
                'F1' => 'Responsable',
                'G1' => 'Département',
                'H1' => 'Date de début',
                'I1' => 'Date de fin',
                'J1' => 'Budget (€)',
                'K1' => 'Créé le',
                'L1' => 'Modifié le'
            ];

            // Appliquer les en-têtes
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            // Style des en-têtes
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0071BC']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];

            $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

            // Remplir les données
            $row = 2;
            foreach ($projects as $project) {
                $sheet->setCellValue('A' . $row, $project->getCode());
                $sheet->setCellValue('B' . $row, $project->getName($locale) ?: 'N/A');
                $sheet->setCellValue('C' . $row, $project->getCategoryLabel());
                $sheet->setCellValue('D' . $row, $project->getPriorityLabel());
                $sheet->setCellValue('E' . $row, $project->getStatusLabel());
                $sheet->setCellValue('F' . $row, $project->getResponsible() ?: 'N/A');
                $sheet->setCellValue('G' . $row, $project->getDepartment() ?: 'N/A');
                $sheet->setCellValue('H' . $row, $project->getStartDate() ? $project->getStartDate()->format('d/m/Y') : 'N/A');
                $sheet->setCellValue('I' . $row, $project->getEndDate() ? $project->getEndDate()->format('d/m/Y') : 'N/A');
                $sheet->setCellValue('J' . $row, $project->getBudget() ? number_format($project->getBudget(), 2, ',', ' ') : 'N/A');
                $sheet->setCellValue('K' . $row, $project->getCreatedAt()->format('d/m/Y H:i'));
                $sheet->setCellValue('L' . $row, $project->getUpdatedAt() ? $project->getUpdatedAt()->format('d/m/Y H:i') : '');
                $row++;
            }

            // Style des données
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ];

            $sheet->getStyle('A2:L' . ($row - 1))->applyFromArray($dataStyle);

            // Ajuster la largeur des colonnes
            foreach (range('A', 'L') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Ajouter des informations supplémentaires
            $infoRow = $row + 2;
            $sheet->setCellValue('A' . $infoRow, 'Généré le : ' . date('d/m/Y H:i:s'));
            $sheet->setCellValue('A' . ($infoRow + 1), 'Total : ' . count($projects) . ' projet(s)');
            $sheet->setCellValue('A' . ($infoRow + 2), 'Langue : ' . strtoupper($locale));

            // Style des informations
            $infoStyle = [
                'font' => [
                    'italic' => true,
                    'size' => 10,
                    'color' => ['rgb' => '666666']
                ]
            ];
            $sheet->getStyle('A' . $infoRow . ':A' . ($infoRow + 2))->applyFromArray($infoStyle);

            // Créer la réponse
            $writer = new Xlsx($spreadsheet);
            
            $response = new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;

        } catch (\Exception $e) {
            $this->logError('Erreur export Excel', $e);
            $this->addFlash(self::FLASH_ERROR, 'Erreur lors de l\'export Excel. Veuillez réessayer.');
            return $this->redirectToRoute('admin_project_index');
        }
    }

    /**
     * CORRECTION MAJEURE: Export PDF avec gestion d'erreurs robuste
     */
    #[Route('/export-pdf', name: 'admin_project_export_pdf', methods: ['POST'])]
    public function exportPdf(Request $request): Response
    {
        try {
            // ÉTAPE 1: Validation des paramètres d'entrée
            $locale = $request->getLocale() ?: 'fr';
            $selectedIds = $request->request->all('ids') ?: [];
            
            $this->logDebug('Export PDF initié', [
                'locale' => $locale,
                'selectedIds' => $selectedIds,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            // ÉTAPE 2: Récupération et validation des données
            if (!empty($selectedIds)) {
                $projects = $this->projectRepository->findForExport($selectedIds, $locale);
                $filename = 'projets_selection_' . date('Y-m-d_H-i-s') . '.pdf';
                $title = 'Projets MK BA - Sélection';
            } else {
                $projects = $this->projectRepository->findAllWithTranslations($locale);
                $filename = 'projets_complet_' . date('Y-m-d_H-i-s') . '.pdf';
                $title = 'Projets MK BA - Liste complète';
            }

            if (empty($projects)) {
                $this->addFlash('warning', 'Aucun projet trouvé pour l\'export PDF.');
                return $this->redirectToRoute('admin_project_index');
            }

            $this->logDebug('Données récupérées', [
                'projectCount' => count($projects),
                'locale' => $locale
            ]);

            // ÉTAPE 3: Vérification de l'existence du template
            $templatePath = 'admin/project/pdf_template.html.twig';
            
            // CORRECTION: Utilisation correcte du service Twig injecté
            if (!$this->twig->getLoader()->exists($templatePath)) {
                throw new \Exception("Template PDF non trouvé : {$templatePath}");
            }

            // ÉTAPE 4: Génération du contenu HTML avec gestion d'erreurs Twig
            try {
                $html = $this->renderView($templatePath, [
                    'projects' => $projects,
                    'currentLocale' => $locale,
                    'title' => $title,
                    'exportDate' => new \DateTime(),
                    'totalProjects' => count($projects),
                    'isSelection' => !empty($selectedIds)
                ]);

                $this->logDebug('Template rendu avec succès', [
                    'htmlLength' => strlen($html),
                    'templatePath' => $templatePath
                ]);

            } catch (\Twig\Error\Error $e) {
                throw new \Exception("Erreur dans le template Twig: " . $e->getMessage(), 0, $e);
            }

            // ÉTAPE 5: Configuration DomPDF avec validation
            if (!class_exists('\Dompdf\Dompdf')) {
                throw new \Exception("DomPDF n'est pas installé. Exécutez: composer require dompdf/dompdf");
            }

            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', false); // Sécurité renforcée
            $options->set('isHtml5ParserEnabled', true);
            $options->set('debugPng', false);
            $options->set('debugKeepTemp', false);
            $options->set('debugCss', false);
            
            // Configuration mémoire pour éviter les erreurs
            $options->set('enable_php', false);
            $options->set('chroot', realpath($this->getParameter('kernel.project_dir')));

            $this->logDebug('Options DomPDF configurées');

            // ÉTAPE 6: Génération PDF avec gestion mémoire
            $originalMemoryLimit = ini_get('memory_limit');
            ini_set('memory_limit', '512M'); // Augmentation temporaire

            try {
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                
                $this->logDebug('HTML chargé dans DomPDF, début du rendu...');
                
                $dompdf->render();
                
                $this->logDebug('PDF généré avec succès', [
                    'filename' => $filename,
                    'memoryUsage' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB'
                ]);

            } catch (\Exception $e) {
                throw new \Exception("Erreur lors de la génération PDF: " . $e->getMessage(), 0, $e);
            } finally {
                // Restaurer la limite mémoire
                ini_set('memory_limit', $originalMemoryLimit);
            }

            // ÉTAPE 7: Création de la réponse HTTP avec téléchargement forcé
            $pdfOutput = $dompdf->output();
            
            if (empty($pdfOutput)) {
                throw new \Exception("Le PDF généré est vide");
            }

            // CORRECTION: Headers optimisés pour forcer le téléchargement sans ouverture automatique
            $response = new Response($pdfOutput);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Content-Length', strlen($pdfOutput));
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Accept-Ranges', 'bytes');

            $this->logDebug('Export PDF terminé avec succès', [
                'filename' => $filename,
                'size' => strlen($pdfOutput) . ' bytes'
            ]);

            return $response;

        } catch (\Exception $e) {
            // CORRECTION: Logging détaillé avec méthode appropriée
            $this->logDetailedError('Erreur export PDF détaillée', $e, [
                'locale' => $locale ?? 'unknown',
                'selectedIds' => $selectedIds ?? [],
                'memoryUsage' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB',
                'phpVersion' => PHP_VERSION,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            // Message d'erreur spécifique selon le type d'erreur
            if (strpos($e->getMessage(), 'Template') !== false) {
                $errorMessage = 'Erreur dans le template PDF. Veuillez contacter l\'administrateur.';
            } elseif (strpos($e->getMessage(), 'DomPDF') !== false) {
                $errorMessage = 'Erreur de configuration PDF. Veuillez contacter l\'administrateur.';
            } elseif (strpos($e->getMessage(), 'memory') !== false) {
                $errorMessage = 'Erreur de mémoire lors de la génération PDF. Essayez avec moins de projets.';
            } else {
                $errorMessage = 'Erreur lors de l\'export PDF: ' . $e->getMessage();
            }

            $this->addFlash('error', $errorMessage);
            return $this->redirectToRoute('admin_project_index');
        }
    }

    
     /**
     * Logging amélioré pour le debugging
     */
    private function logDebug(string $message, array $context = []): void
    {
        if ($this->getParameter('kernel.environment') === 'dev') {
            $this->logger->debug('[PDF_DEBUG] ' . $message, $context);
        }
    }

    /**
     * Export DataTables - toutes données (conservé pour compatibilité)
     */
    #[Route('/export-datatable', name: 'admin_project_export_datatable', methods: ['POST'])]
    public function exportDataTable(Request $request): JsonResponse
    {
        try {
            $locale = $request->getLocale() ?: 'fr';
            $projects = $this->projectRepository->findAllWithTranslations($locale);
            
            $data = [];
            foreach ($projects as $project) {
                $data[] = [
                    'code' => $project->getCode(),
                    'name' => $project->getName($locale) ?: 'N/A',
                    'category' => $project->getCategoryLabel(),
                    'priority' => $project->getPriorityLabel(),
                    'status' => $project->getStatusLabel(),
                    'responsible' => $project->getResponsible() ?: 'N/A',
                    'department' => $project->getDepartment() ?: 'N/A',
                    'start_date' => $project->getStartDate() ? $project->getStartDate()->format('d/m/Y') : 'N/A',
                    'end_date' => $project->getEndDate() ? $project->getEndDate()->format('d/m/Y') : 'N/A',
                    'budget' => $project->getBudget() ? $project->getBudget() . '€' : 'N/A',
                    'created_at' => $project->getCreatedAt()->format('d/m/Y H:i')
                ];
            }

            return new JsonResponse([
                'success' => true,
                'data' => $data,
                'filename' => 'projets_' . date('Y-m-d_H-i-s')
            ]);
        } catch (\Exception $e) {
            $this->logError('Erreur export DataTables', $e);
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'export'
            ]);
        }
    }

    /**
     * Export DataTables - sélection (conservé pour compatibilité)
     */
    #[Route('/export-datatable-selection', name: 'admin_project_export_datatable_selection', methods: ['POST'])]
    public function exportDataTableSelection(Request $request): JsonResponse
    {
        try {
            $ids = json_decode($request->request->get('ids', '[]'), true);
            $locale = $request->getLocale() ?: 'fr';
            
            if (empty($ids)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Aucun projet sélectionné'
                ]);
            }

            $projects = $this->projectRepository->findForExport($ids, $locale);
            
            $data = [];
            foreach ($projects as $project) {
                $data[] = [
                    'code' => $project->getCode(),
                    'name' => $project->getName($locale) ?: 'N/A',
                    'category' => $project->getCategoryLabel(),
                    'priority' => $project->getPriorityLabel(),
                    'status' => $project->getStatusLabel(),
                    'responsible' => $project->getResponsible() ?: 'N/A',
                    'department' => $project->getDepartment() ?: 'N/A',
                    'start_date' => $project->getStartDate() ? $project->getStartDate()->format('d/m/Y') : 'N/A',
                    'end_date' => $project->getEndDate() ? $project->getEndDate()->format('d/m/Y') : 'N/A',
                    'budget' => $project->getBudget() ? $project->getBudget() . '€' : 'N/A',
                    'created_at' => $project->getCreatedAt()->format('d/m/Y H:i')
                ];
            }

            return new JsonResponse([
                'success' => true,
                'data' => $data,
                'filename' => 'projets_selection_' . date('Y-m-d_H-i-s')
            ]);
        } catch (\Exception $e) {
            $this->logError('Erreur export sélection DataTables', $e);
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'export de la sélection'
            ]);
        }
    }

    /**
     * CORRECTION MAJEURE: Validation robuste des fichiers uploadés
     */
    private function validateFile($file): array
    {
        $errors = [];
        
        if (!$file || !$file->isValid()) {
            $errors[] = 'Fichier invalide';
            return $errors;
        }

        // CORRECTION: Vérification de l'existence du fichier temporaire avant getSize()
        try {
            $filePath = $file->getPathname();
            if (!file_exists($filePath) || !is_readable($filePath)) {
                $errors[] = 'Le fichier temporaire n\'est plus accessible';
                return $errors;
            }

            // Vérification de la taille avec gestion d'erreur
            $fileSize = $file->getSize();
            if ($fileSize === false || $fileSize === null) {
                // Fallback : utiliser filesize() directement
                $fileSize = @filesize($filePath);
                if ($fileSize === false) {
                    $errors[] = 'Impossible de déterminer la taille du fichier';
                    return $errors;
                }
            }

            // Vérification de la taille (max 10MB)
            if ($fileSize > 10 * 1024 * 1024) {
                $errors[] = 'Le fichier dépasse la taille maximale de 10MB';
            }

        } catch (\Exception $e) {
            $errors[] = 'Erreur lors de la lecture du fichier: ' . $e->getMessage();
            return $errors;
        }

        // Vérification de l'extension
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'jpg', 'jpeg', 'png'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Format de fichier non autorisé';
        }

        return $errors;
    }

    /**
     * Sauvegarder une traduction pour une locale spécifique
     */
    private function saveTranslationForLocale(Project $project, string $locale, array $translationData): void
    {
        $existingTranslation = $project->getTranslation($locale);
        
        if ($existingTranslation) {
            $existingTranslation->setName($translationData['name'])
                              ->setDescription($translationData['description'] ?? '');
        } else {
            $translation = new ProjectTranslation();
            $translation->setProject($project)
                      ->setLocale($locale)
                      ->setName($translationData['name'])
                      ->setDescription($translationData['description'] ?? '');
            
            $project->addTranslation($translation);
        }
    }

    /**
     * Traiter les relations du projet (membres et liens)
     */
    private function processProjectRelations(Project $project, Request $request): void
    {
        $this->processMembers($project, $request);
        $this->processLinks($project, $request);
    }

    /**
     * Traiter les membres du projet
     */
    private function processMembers(Project $project, Request $request): void
    {
        // Supprimer tous les membres existants
        foreach ($project->getMembers() as $member) {
            $project->removeMember($member);
            $this->entityManager->remove($member);
        }
        
        // Ajouter les nouveaux membres
        $membersData = $request->request->all('members');
        if (is_array($membersData)) {
            foreach ($membersData as $memberData) {
                if (!empty($memberData['name'])) {
                    $member = new ProjectMember();
                    $member->setProject($project)
                          ->setName($memberData['name'])
                          ->setEmail($memberData['email'] ?? null)
                          ->setRole($memberData['role'] ?? null);
                    
                    $project->addMember($member);
                }
            }
        }
    }

    /**
     * Traiter les liens du projet
     */
    private function processLinks(Project $project, Request $request): void
    {
        // Supprimer tous les liens existants
        foreach ($project->getLinks() as $link) {
            $project->removeLink($link);
            $this->entityManager->remove($link);
        }
        
        // Ajouter les nouveaux liens
        $linksData = $request->request->all('links');
        if (is_array($linksData)) {
            foreach ($linksData as $linkData) {
                if (!empty($linkData['title']) && !empty($linkData['url'])) {
                    $link = new ProjectLink();
                    $link->setProject($project)
                         ->setTitle($linkData['title'])
                         ->setUrl($linkData['url'])
                         ->setType($linkData['type'] ?? null);
                    
                    $project->addLink($link);
                }
            }
        }
    }

    /**
     * CORRECTION MAJEURE: Traitement robuste des fichiers uploadés
     */
    private function processFileUploads(Project $project, Request $request): void
    {
        $uploadedFiles = $request->files->get('files', []);
        
        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        // CORRECTION: Filtrage strict des fichiers valides
        $validFiles = array_filter($uploadedFiles, function($file) {
            return $file !== null && $file->isValid() && $file->getError() === UPLOAD_ERR_OK;
        });

        if (!empty($validFiles)) {
            foreach ($validFiles as $file) {
                try {
                    $errors = $this->validateFile($file);
                    if (!empty($errors)) {
                        $this->addFlash(self::FLASH_WARNING, sprintf(
                            'Fichier "%s" ignoré : %s',
                            $file->getClientOriginalName(),
                            implode(', ', $errors)
                        ));
                        continue;
                    }

                    $this->handleSingleFileUpload($project, $file);
                    
                } catch (\Exception $e) {
                    $this->logError('Erreur traitement fichier individuel', $e);
                    $this->addFlash(self::FLASH_WARNING, sprintf(
                        'Erreur lors du traitement du fichier "%s": %s',
                        $file->getClientOriginalName(),
                        $e->getMessage()
                    ));
                }
            }
        }
    }

    /**
     * CORRECTION MAJEURE: Gestion robuste d'un seul fichier
     */
    private function handleSingleFileUpload(Project $project, $file): void
    {
        try {
            $uploadDir = $this->getParameter('projects_directory');
            
            // CORRECTION: Validation du répertoire de destination
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new \Exception("Impossible de créer le répertoire d'upload: {$uploadDir}");
                }
            }

            if (!is_writable($uploadDir)) {
                throw new \Exception("Le répertoire d'upload n'est pas accessible en écriture: {$uploadDir}");
            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $extension = $file->getClientOriginalExtension();
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

            // CORRECTION: Vérification de l'existence et de la taille avant move()
            $sourceFile = $file->getPathname();
            if (!file_exists($sourceFile)) {
                throw new \Exception("Le fichier source n'existe plus");
            }

            // CORRECTION: Utilisation sécurisée de getSize() avec fallback
            $fileSize = null;
            try {
                $fileSize = $file->getSize();
                if ($fileSize === false || $fileSize === null) {
                    $fileSize = @filesize($sourceFile);
                }
            } catch (\Exception $e) {
                $fileSize = @filesize($sourceFile);
            }

            if ($fileSize === false || $fileSize === null) {
                throw new \Exception("Impossible de déterminer la taille du fichier");
            }

            // Déplacement du fichier
            $file->move($uploadDir, $newFilename);

            // Vérification que le fichier a bien été déplacé
            $targetFile = $uploadDir . '/' . $newFilename;
            if (!file_exists($targetFile)) {
                throw new \Exception("Le fichier n'a pas pu être déplacé vers sa destination");
            }

            // Création de l'entité attachment
            $attachment = new ProjectAttachment();
            $attachment->setProject($project)
                     ->setFileName($newFilename)
                     ->setOriginalName($file->getClientOriginalName())
                     ->setMimeType($file->getClientMimeType() ?: $this->getMimeTypeFromExtension($extension))
                     ->setFileSize($fileSize);

            $project->addAttachment($attachment);

            $this->addFlash(self::FLASH_INFO, sprintf('Fichier "%s" ajouté avec succès.', $file->getClientOriginalName()));

        } catch (FileException $e) {
            throw new \Exception(sprintf('Erreur FileException lors de l\'upload du fichier "%s": %s', $file->getClientOriginalName(), $e->getMessage()));
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Erreur lors de l\'upload du fichier "%s": %s', $file->getClientOriginalName(), $e->getMessage()));
        }
    }

    /**
     * Traiter les fichiers supprimés
     */
    private function processRemovedAttachments(Project $project, Request $request): void
    {
        $removedAttachments = $request->request->get('removed_attachments');
        if ($removedAttachments) {
            $removedIds = json_decode($removedAttachments, true);
            if (is_array($removedIds)) {
                foreach ($removedIds as $attachmentId) {
                    $attachment = $this->entityManager->getRepository(ProjectAttachment::class)->find($attachmentId);
                    if ($attachment && $attachment->getProject() === $project) {
                        // Supprimer le fichier physique
                        $uploadDir = $this->getParameter('projects_directory');
                        $filePath = $uploadDir . '/' . $attachment->getFileName();
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        
                        $project->removeAttachment($attachment);
                        $this->entityManager->remove($attachment);
                    }
                }
            }
        }
    }

    /**
     * Déterminer le type MIME depuis l'extension
     */
    private function getMimeTypeFromExtension(string $extension): string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Obtenir les traductions disponibles pour un projet
     */
    private function getAvailableTranslations(Project $project): array
    {
        $availableTranslations = [];
        $supportedLocales = ['fr', 'en'];
        
        foreach ($supportedLocales as $locale) {
            $translation = $project->getTranslation($locale);
            $availableTranslations[$locale] = [
                'exists' => $translation !== null && !empty($translation->getName()),
                'translation' => $translation
            ];
        }

        return $availableTranslations;
    }

    /**
     * CORRECTION: Logger centralisé avec service injecté
     */
    private function logError(string $context, \Exception $e): void
    {
        $this->logger->error(sprintf('[%s] %s: %s in %s:%d', 
            $context, 
            $e->getMessage(), 
            $e->getFile(), 
            $e->getLine(),
            date('Y-m-d H:i:s')
        ), [
            'exception' => $e,
            'context' => $context
        ]);
    }

    /**
     * NOUVEAU: Logger détaillé pour erreurs complexes avec contexte personnalisé
     */
    private function logDetailedError(string $context, \Exception $e, array $additionalContext = []): void
    {
        $errorData = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'exception' => $e,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Fusionner avec le contexte additionnel
        $errorData = array_merge($errorData, $additionalContext);

        $this->logger->error(sprintf('[%s] %s', $context, $e->getMessage()), $errorData);
    }
}