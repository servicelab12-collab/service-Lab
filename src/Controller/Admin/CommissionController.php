<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Commission;
use App\Enum\CommissionStatus;
use App\Repository\CommissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COMMERCIAL')]
#[Route('/admin/commissions')]
final class CommissionController extends AbstractController
{
    #[Route('', name: 'admin_commissions', methods: ['GET'])]
    public function index(Request $request, CommissionRepository $commissionRepository): Response
    {
        $statusParam = $request->query->getString('status');
        $status = $statusParam !== '' ? CommissionStatus::tryFrom($statusParam) : null;
        $commissions = $commissionRepository->findAllWithRelations($status);

        $retourDue = 0.0;
        $totalTzanet = 0.0;
        foreach ($commissions as $commission) {
            $retourDue += (float) $commission->getMontantRetour();
            $totalTzanet += (float) $commission->getTotalTzanet();
        }

        return $this->render('admin/commissions/index.html.twig', [
            'commissions' => $commissions,
            'filters' => ['status' => $statusParam],
            'due' => $commissionRepository->sumByStatus(CommissionStatus::A_PAYER),
            'paid' => $commissionRepository->sumByStatus(CommissionStatus::PAYEE),
            'retourTotal' => number_format($retourDue, 2, '.', ''),
            'totalTzanet' => number_format($totalTzanet, 2, '.', ''),
        ]);
    }

    #[Route('/{id}/montant', name: 'admin_commissions_montant', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateMontant(Request $request, Commission $commission, EntityManagerInterface $em, CommissionRepository $commissionRepository): Response
    {
        if (!$this->isCsrfTokenValid('commission_montant_'.$commission->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $montant = $request->request->getString('montant_signature');
        if ($montant === '' || !is_numeric($montant) || (float) $montant < 0) {
            $this->addFlash('error', 'Commission de référencement invalide.');
        } else {
            $commission->setMontantSignature($montant);
            $em->flush();

            $due = number_format((float) $commissionRepository->sumByStatus(CommissionStatus::A_PAYER), 2, '.', ',');
            $this->addFlash('success', sprintf(
                'Commission référencement mise à jour : $%s · Total TZANET = $%s · À payer : $%s',
                $commission->getMontantSignature(),
                $commission->getTotalTzanet(),
                $due,
            ));
        }

        $redirect = $request->request->getString('_redirect');
        if ($redirect !== '') {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('admin_commissions');
    }

    #[Route('/{id}/achats', name: 'admin_commissions_achats', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateAchats(Request $request, Commission $commission, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('commission_achats_'.$commission->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $achats = $request->request->getString('achats_annuel');
        if ($achats === '' || !is_numeric($achats) || (float) $achats < 0) {
            $this->addFlash('error', 'Montant d’achats annuels invalide.');
        } else {
            $commission->setAchatsAnnuel($achats);
            $em->flush();
            $this->addFlash('success', sprintf(
                'Achats annuels mis à jour. Retour %s %% = $%s · Total TZANET = $%s',
                $commission->getRetourAnnuel(),
                $commission->getMontantRetour(),
                $commission->getTotalTzanet(),
            ));
        }

        $redirect = $request->request->getString('_redirect');
        if ($redirect !== '') {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('admin_commissions');
    }

    #[Route('/{id}/pay', name: 'admin_commissions_pay', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function markPaid(Request $request, Commission $commission, EntityManagerInterface $em, CommissionRepository $commissionRepository): Response
    {
        if (!$this->isCsrfTokenValid('commission_pay_'.$commission->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($commission->getStatut() === CommissionStatus::PAYEE) {
            $this->addFlash('error', 'Cette commission est déjà payée.');
        } else {
            $montant = $commission->getMontantSignature();
            $commission->markAsPaid();
            $em->flush();

            $due = number_format((float) $commissionRepository->sumByStatus(CommissionStatus::A_PAYER), 2, '.', ',');
            $paid = number_format((float) $commissionRepository->sumByStatus(CommissionStatus::PAYEE), 2, '.', ',');

            $this->addFlash('success', sprintf(
                'Commission référencement $%s payée — retirée des commissions à payer. À payer : $%s · Payé : $%s',
                $montant,
                $due,
                $paid,
            ));
        }

        return $this->redirectToRoute('admin_commissions');
    }
}
