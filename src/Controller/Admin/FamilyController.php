<?php

namespace App\Controller\Admin;

use App\Entity\Family;
use App\Form\FamilyType;
use App\Repository\FamilyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/family')]
class FamilyController extends AbstractController
{
    #[Route('/', name: 'admin_family_index', methods: ['GET'])]
    public function index(FamilyRepository $familyRepository): Response
    {
        $families = $familyRepository->findBy([], ['displayOrder' => 'ASC', 'createdAt' => 'DESC']);
        
        return $this->render('admin/family/index.html.twig', [
            'families' => $families,
        ]);
    }

    #[Route('/new', name: 'admin_family_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $family = new Family();
        $form = $this->createForm(FamilyType::class, $family);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle Bootstrap icon selection
                $iconBootstrap = $form->get('iconBootstrap')->getData();
                if ($iconBootstrap) {
                    $family->setIcon($iconBootstrap);
                }

                // Handle custom icon upload (overrides Bootstrap selection)
                $iconFile = $form->get('iconFile')->getData();
                if ($iconFile) {
                    $originalFilename = pathinfo($iconFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$iconFile->guessExtension();

                    $uploadsDirectory = $this->getParameter('kernel.project_dir').'/public/uploads/families';
                    if (!is_dir($uploadsDirectory)) {
                        mkdir($uploadsDirectory, 0755, true);
                    }
                    
                    $iconFile->move($uploadsDirectory, $newFilename);
                    $family->setIcon($newFilename);
                }

                $entityManager->persist($family);
                $entityManager->flush();

                $this->addFlash('success', 'Family has been created successfully.');
                return $this->redirectToRoute('admin_family_index', [], Response::HTTP_SEE_OTHER);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating family: ' . $e->getMessage());
            }
        }

        return $this->render('admin/family/new.html.twig', [
            'family' => $family,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_family_show', methods: ['GET'])]
    public function show(Family $family): Response
    {
        return $this->render('admin/family/show.html.twig', [
            'family' => $family,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_family_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Family $family, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(FamilyType::class, $family);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle icon removal
                $removeIcon = $form->get('removeIcon')->getData();
                if ($removeIcon) {
                    if ($family->getIcon() && !$this->isBootstrapIcon($family->getIcon())) {
                        $oldIconPath = $this->getParameter('kernel.project_dir').'/public/uploads/families/'.$family->getIcon();
                        if (file_exists($oldIconPath)) {
                            unlink($oldIconPath);
                        }
                    }
                    $family->setIcon(null);
                } else {
                    // Handle Bootstrap icon selection
                    $iconBootstrap = $form->get('iconBootstrap')->getData();
                    if ($iconBootstrap) {
                        // Delete old custom icon if exists
                        if ($family->getIcon() && !$this->isBootstrapIcon($family->getIcon())) {
                            $oldIconPath = $this->getParameter('kernel.project_dir').'/public/uploads/families/'.$family->getIcon();
                            if (file_exists($oldIconPath)) {
                                unlink($oldIconPath);
                            }
                        }
                        $family->setIcon($iconBootstrap);
                    }

                    // Handle custom icon upload (overrides Bootstrap selection)
                    $iconFile = $form->get('iconFile')->getData();
                    if ($iconFile) {
                        // Delete old icon if exists
                        if ($family->getIcon() && !$this->isBootstrapIcon($family->getIcon())) {
                            $oldIconPath = $this->getParameter('kernel.project_dir').'/public/uploads/families/'.$family->getIcon();
                            if (file_exists($oldIconPath)) {
                                unlink($oldIconPath);
                            }
                        }

                        $originalFilename = pathinfo($iconFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$iconFile->guessExtension();

                        $uploadsDirectory = $this->getParameter('kernel.project_dir').'/public/uploads/families';
                        if (!is_dir($uploadsDirectory)) {
                            mkdir($uploadsDirectory, 0755, true);
                        }
                        
                        $iconFile->move($uploadsDirectory, $newFilename);
                        $family->setIcon($newFilename);
                    }
                }

                $entityManager->flush();

                $this->addFlash('success', 'Family has been updated successfully.');
                return $this->redirectToRoute('admin_family_index', [], Response::HTTP_SEE_OTHER);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating family: ' . $e->getMessage());
            }
        }

        return $this->render('admin/family/edit.html.twig', [
            'family' => $family,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_family_delete', methods: ['POST'])]
    public function delete(Request $request, Family $family, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$family->getId(), $request->request->get('_token'))) {
            try {
                // Check if family has associated procedures
                if ($family->getProcedures()->count() > 0) {
                    $this->addFlash('error', 'Cannot delete family with associated procedures.');
                    return $this->redirectToRoute('admin_family_index', [], Response::HTTP_SEE_OTHER);
                }

                // Delete custom icon file if exists
                if ($family->getIcon() && !$this->isBootstrapIcon($family->getIcon())) {
                    $iconPath = $this->getParameter('kernel.project_dir').'/public/uploads/families/'.$family->getIcon();
                    if (file_exists($iconPath)) {
                        unlink($iconPath);
                    }
                }

                // Soft delete by setting isActive to false
                $family->setIsActive(false);
                $entityManager->flush();

                $this->addFlash('success', 'Family has been deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting family: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_family_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-status', name: 'admin_family_toggle_status', methods: ['POST'])]
    public function toggleStatus(Family $family, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $family->setIsActive(!$family->isActive());
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'status' => $family->isActive(),
                'message' => 'Status updated successfully.'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    private function isBootstrapIcon(string $icon): bool
    {
        return str_starts_with($icon, 'bi-');
    }
}