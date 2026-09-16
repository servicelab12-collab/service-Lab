<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Lead;
use App\Enum\LeadSource;
use App\Enum\LeadStatus;
use App\Repository\LeadRepository;
use App\Service\LeadManager;
use App\Service\LeadPdfExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COMMERCIAL')]
#[Route('/admin/leads')]
final class LeadController extends AbstractController
{
    #[Route('', name: 'admin_leads', methods: ['GET'])]
    public function index(Request $request, LeadRepository $leadRepository): Response
    {
        $sourceParam = $request->query->getString('source');
        $statusParam = $request->query->getString('status');
        $q = $request->query->getString('q');

        // Parcours public = TZANET uniquement
        $source = LeadSource::TZANET;
        if ($sourceParam !== '' && LeadSource::tryFrom($sourceParam) === LeadSource::TZANET) {
            $source = LeadSource::TZANET;
        }
        $status = $statusParam !== '' ? LeadStatus::tryFrom($statusParam) : null;

        return $this->render('admin/leads/index.html.twig', [
            'leads' => $leadRepository->search($source, $status, $q !== '' ? $q : null),
            'sources' => [LeadSource::TZANET],
            'statuses' => LeadStatus::cases(),
            'filters' => [
                'source' => LeadSource::TZANET->value,
                'status' => $statusParam,
                'q' => $q,
            ],
            'counts' => $leadRepository->countGroupedByStatus(),
        ]);
    }

    #[Route('/pdf', name: 'admin_leads_pdf_all', methods: ['GET'])]
    public function pdfAll(Request $request, LeadRepository $leadRepository, LeadPdfExporter $pdfExporter): Response
    {
        $statusParam = $request->query->getString('status');
        $q = $request->query->getString('q');
        $status = $statusParam !== '' ? LeadStatus::tryFrom($statusParam) : null;

        $leads = $leadRepository->search(LeadSource::TZANET, $status, $q !== '' ? $q : null);
        $content = $pdfExporter->generateAll($leads);

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $pdfExporter->filenameAll()),
        ]);
    }

    #[Route('/{id}/pdf', name: 'admin_leads_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(Lead $lead, LeadPdfExporter $pdfExporter): Response
    {
        $content = $pdfExporter->generate($lead);

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $pdfExporter->filename($lead)),
        ]);
    }

    #[Route('/{id}', name: 'admin_leads_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Lead $lead): Response
    {
        return $this->render('admin/leads/show.html.twig', [
            'lead' => $lead,
            'statuses' => LeadStatus::cases(),
        ]);
    }

    #[Route('/{id}/status', name: 'admin_leads_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateStatus(Request $request, Lead $lead, LeadManager $leadManager): Response
    {
        if (!$this->isCsrfTokenValid('lead_status_'.$lead->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $status = LeadStatus::tryFrom($request->request->getString('status'));
        if ($status === null) {
            $this->addFlash('error', 'Statut invalide.');

            return $this->redirectToRoute('admin_leads_show', ['id' => $lead->getId()]);
        }

        $leadManager->updateStatus($lead, $status);
        $this->addFlash(
            'success',
            $status === LeadStatus::CONTRACT_CANCELLED
                ? 'Contrat annulé — statut mis à jour.'
                : 'Statut du lead mis à jour.',
        );

        return $this->redirectToRoute('admin_leads_show', ['id' => $lead->getId()]);
    }
}
