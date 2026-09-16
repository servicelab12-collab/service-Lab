<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\CommissionStatus;
use App\Enum\LeadCanal;
use App\Enum\LeadSource;
use App\Enum\LeadStatus;
use App\Repository\CommissionRepository;
use App\Repository\ContractRepository;
use App\Repository\LeadRepository;
use App\Repository\OfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COMMERCIAL')]
#[Route('/admin')]
final class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        LeadRepository $leadRepository,
        CommissionRepository $commissionRepository,
        ContractRepository $contractRepository,
        OfferRepository $offerRepository,
    ): Response {
        $total = $leadRepository->countBySource(LeadSource::TZANET);
        $new = $leadRepository->countByStatus(LeadStatus::NEW);
        $qualified = $leadRepository->countByStatus(LeadStatus::CONTACTED)
            + $leadRepository->countByStatus(LeadStatus::NEED_ANALYSIS)
            + $leadRepository->countByStatus(LeadStatus::OFFER_SENT);
        $signed = $leadRepository->countByStatus(LeadStatus::CONTRACT_SIGNED)
            + $leadRepository->countByStatus(LeadStatus::ACTIVE_CLIENT);
        $conversion = $total > 0 ? round(($signed / $total) * 100) : 0;
        $byMonth = $leadRepository->countByMonth(6);
        $byCanal = $leadRepository->countGroupedByCanal();

        $monthLabels = array_map(
            static fn (string $ym): string => \DateTimeImmutable::createFromFormat('Y-m', $ym)?->format('M') ?? $ym,
            array_keys($byMonth),
        );

        return $this->render('admin/dashboard/index.html.twig', [
            'user' => $this->getUser(),
            'stats' => [
                'total' => $leadRepository->countBySource(LeadSource::TZANET),
                'tzanet' => $leadRepository->countBySource(LeadSource::TZANET),
                'new' => $new,
                'qualified' => $qualified,
                'signed' => $signed,
                'contracts' => $contractRepository->countByStatus(),
                'commissionDue' => number_format((float) $commissionRepository->sumByStatus(CommissionStatus::A_PAYER), 2, '.', ','),
                'commissionPaid' => number_format((float) $commissionRepository->sumByStatus(CommissionStatus::PAYEE), 2, '.', ','),
                'conversion' => $conversion,
            ],
            'bridge' => [
                'tzanet' => $byCanal[LeadCanal::TZANET->value] ?? 0,
            ],
            'activeOffer' => $offerRepository->findActiveOffer(),
            'latest' => $leadRepository->findLatest(8, LeadSource::TZANET),
            'charts' => [
                'leadsMonth' => [
                    'labels' => array_values($monthLabels),
                    'values' => array_values($byMonth),
                ],
            ],
        ]);
    }
}
