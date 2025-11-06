<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/settings')]
#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    #[Route('/', name: 'admin_settings_index', methods: ['GET'])]
    public function index(): Response
    {
        // Récupérer les statistiques du système
        $systemStats = $this->getSystemStats();
        
        return $this->render('admin/settings/index.html.twig', [
            'user' => $this->getUser(),
            'systemStats' => $systemStats,
        ]);
    }

    #[Route('/general', name: 'admin_settings_general', methods: ['GET', 'POST'])]
    public function general(): Response
    {
        return $this->render('admin/settings/general.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/data-sources', name: 'admin_settings_datasources', methods: ['GET', 'POST'])]
    public function dataSources(): Response
    {
        return $this->render('admin/settings/datasources.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/customizing', name: 'admin_settings_customizing', methods: ['GET', 'POST'])]
    public function customizing(): Response
    {
        return $this->render('admin/settings/customizing.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/files', name: 'admin_settings_files', methods: ['GET', 'POST'])]
    public function files(): Response
    {
        return $this->render('admin/settings/files.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/pages-cms', name: 'admin_settings_pages', methods: ['GET', 'POST'])]
    public function pagesCms(): Response
    {
        return $this->render('admin/settings/pages.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/modules', name: 'admin_settings_modules', methods: ['GET', 'POST'])]
    public function modules(): Response
    {
        return $this->render('admin/settings/modules.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/security', name: 'admin_settings_security', methods: ['GET', 'POST'])]
    public function security(): Response
    {
        return $this->render('admin/settings/security.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/backup', name: 'admin_settings_backup', methods: ['GET', 'POST'])]
    public function backup(): Response
    {
        return $this->render('admin/settings/backup.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/logs', name: 'admin_settings_logs', methods: ['GET'])]
    public function logs(): Response
    {
        return $this->render('admin/settings/logs.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    private function getSystemStats(): array
    {
        // Simuler des statistiques système
        return [
            'php_version' => PHP_VERSION,
            'symfony_version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
            'database_size' => '1.2 GB',
            'storage_used' => '3.8 GB',
            'storage_total' => '50 GB',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'disk_usage' => disk_free_space('/') ? disk_total_space('/') - disk_free_space('/') : 0,
            'disk_total' => disk_total_space('/') ?: 0,
            'last_backup' => new \DateTime('-2 days'),
            'total_users' => 147,
            'total_documents' => 1205,
            'total_procedures' => 89,
            'system_status' => 'healthy',
            'services_status' => [
                'database' => 'online',
                'cache' => 'online',
                'storage' => 'online',
                'mail' => 'online',
            ]
        ];
    }
}