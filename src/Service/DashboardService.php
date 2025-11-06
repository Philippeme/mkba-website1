<?php

namespace App\Service;

use App\Repository\FamilyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FamilyRepository $familyRepository,
        private UserRepository $userRepository
    ) {}

    public function getDashboardStats(): array
    {
        return [
            'totalFamilies' => $this->familyRepository->count(['isActive' => true]),
            'totalProcedures' => $this->getProcedureCount(),
            'totalUsers' => $this->userRepository->count(['isActive' => true]),
            'pendingRequests' => $this->getPendingRequestsCount(),
            'totalRequests' => $this->getTotalRequestsCount(),
            'activeEntities' => $this->getActiveEntitiesCount(),
            'totalDocuments' => $this->getTotalDocumentsCount(),
            'systemHealth' => $this->getSystemHealthStatus(),
        ];
    }

    public function getRecentActivities(int $limit = 10): array
    {
        // Simulated activities - replace with real data when entities are created
        return [
            [
                'id' => 1,
                'type' => 'family_created',
                'title' => 'New Family Created',
                'description' => 'Family "Police & Justice" was created',
                'user' => 'Admin User',
                'timestamp' => new \DateTime('-2 hours'),
                'icon' => 'bi-plus-circle',
                'color' => 'success'
            ],
            [
                'id' => 2,
                'type' => 'procedure_updated',
                'title' => 'Procedure Updated',
                'description' => 'Birth Certificate procedure was modified',
                'user' => 'Manager User',
                'timestamp' => new \DateTime('-5 hours'),
                'icon' => 'bi-pencil',
                'color' => 'warning'
            ],
            [
                'id' => 3,
                'type' => 'user_registered',
                'title' => 'New User Registered',
                'description' => 'John Doe registered in the system',
                'user' => 'System',
                'timestamp' => new \DateTime('-1 day'),
                'icon' => 'bi-person-plus',
                'color' => 'info'
            ],
            [
                'id' => 4,
                'type' => 'request_submitted',
                'title' => 'New Request Submitted',
                'description' => 'ID Card request #1234 submitted',
                'user' => 'Citizen User',
                'timestamp' => new \DateTime('-2 days'),
                'icon' => 'bi-file-earmark-text',
                'color' => 'primary'
            ],
        ];
    }

    public function getChartData(): array
    {
        return [
            'requestsPerMonth' => $this->getRequestsPerMonth(),
            'proceduresByFamily' => $this->getProceduresByFamily(),
            'userRegistrations' => $this->getUserRegistrationsData(),
            'systemUsage' => $this->getSystemUsageData(),
        ];
    }

    public function getAnalyticsData(): array
    {
        return [
            'averageProcessingTime' => '3.2 days',
            'satisfactionRate' => 87.5,
            'completionRate' => 94.2,
            'popularProcedures' => $this->getPopularProcedures(),
        ];
    }

    private function getProcedureCount(): int
    {
        // Replace with actual procedure count when entity is created
        return 25;
    }

    private function getPendingRequestsCount(): int
    {
        // Replace with actual pending requests count
        return 8;
    }

    private function getTotalRequestsCount(): int
    {
        // Replace with actual total requests count
        return 156;
    }

    private function getActiveEntitiesCount(): int
    {
        // Replace with actual entities count
        return 12;
    }

    private function getTotalDocumentsCount(): int
    {
        // Replace with actual documents count
        return 234;
    }

    private function getSystemHealthStatus(): string
    {
        return 'healthy'; // healthy, warning, critical
    }

    private function getRequestsPerMonth(): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [65, 78, 90, 81, 96, 87],
        ];
    }

    private function getProceduresByFamily(): array
    {
        $families = $this->familyRepository->findBy(['isActive' => true], ['displayOrder' => 'ASC']);
        $data = [];

        foreach ($families as $family) {
            $data[] = [
                'name' => $family->getFname(),
                'count' => $family->getProcedures()->count(),
                'color' => $this->generateColorForFamily($family->getId()),
            ];
        }

        return $data;
    }

    private function getUserRegistrationsData(): array
    {
        return [
            'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            'data' => [12, 18, 24, 15],
        ];
    }

    private function getSystemUsageData(): array
    {
        return [
            'cpu' => 45,
            'memory' => 62,
            'storage' => 38,
            'network' => 23,
        ];
    }

    private function getPopularProcedures(): array
    {
        return [
            ['name' => 'Birth Certificate', 'requests' => 45],
            ['name' => 'ID Card Renewal', 'requests' => 38],
            ['name' => 'Passport Application', 'requests' => 32],
            ['name' => 'Marriage Certificate', 'requests' => 28],
        ];
    }

    private function generateColorForFamily(int $id): string
    {
        $colors = ['#6d5192', '#8e32e9', '#489ef4', '#21bf06', '#ff4757', '#ffa502'];
        return $colors[$id % count($colors)];
    }
}