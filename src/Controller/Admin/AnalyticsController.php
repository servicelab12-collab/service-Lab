<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\CommissionStatus;
use App\Enum\LeadSource;
use App\Enum\LeadStatus;
use App\Repository\CommissionRepository;
use App\Repository\ContractRepository;
use App\Repository\LeadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/analytics')]
final class AnalyticsController extends AbstractController
{
    #[Route('', name: 'admin_analytics', methods: ['GET'])]
    public function index(
        LeadRepository $leadRepository,
        ContractRepository $contractRepository,
        CommissionRepository $commissionRepository,
    ): Response {
        $byMonth = $leadRepository->countByMonth(6);
        $bySource = $leadRepository->countGroupedBySource();
        $byStatus = $leadRepository->countGroupedByStatus();
        $commissionMonth = $commissionRepository->sumByMonth(6);

        $statusLabels = [];
        $statusValues = [];
        foreach (LeadStatus::cases() as $status) {
            $statusLabels[] = $status->label();
            $statusValues[] = $byStatus[$status->value] ?? 0;
        }

        $monthLabels = array_map(
            static fn (string $ym): string => \DateTimeImmutable::createFromFormat('Y-m', $ym)?->format('M Y') ?? $ym,
            array_keys($byMonth),
        );

        return $this->render('admin/analytics/index.html.twig', [
            'stats' => [
                'leads' => $leadRepository->countBySource(LeadSource::TZANET),
                'tzanet' => $bySource[LeadSource::TZANET->value] ?? 0,
                'contracts' => $contractRepository->countByStatus(),
                'commissionDue' => number_format((float) $commissionRepository->sumByStatus(CommissionStatus::A_PAYER), 2, '.', ','),
                'commissionPaid' => number_format((float) $commissionRepository->sumByStatus(CommissionStatus::PAYEE), 2, '.', ','),
            ],
            'charts' => [
                'leadsMonth' => [
                    'labels' => array_values($monthLabels),
                    'values' => array_values($byMonth),
                ],
                'pipeline' => [
                    'labels' => $statusLabels,
                    'values' => $statusValues,
                ],
                'commissions' => [
                    'labels' => array_values($monthLabels),
                    'values' => array_values($commissionMonth),
                ],
            ],
        ]);
    }
}
