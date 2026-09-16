<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Contract;
use App\Form\Admin\ContractCreateType;
use App\Repository\ContractRepository;
use App\Repository\LeadRepository;
use App\Service\ContractManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COMMERCIAL')]
#[Route('/admin/contracts')]
final class ContractController extends AbstractController
{
    #[Route('', name: 'admin_contracts', methods: ['GET'])]
    public function index(ContractRepository $contractRepository): Response
    {
        return $this->render('admin/contracts/index.html.twig', [
            'contracts' => $contractRepository->findAllWithRelations(),
            'activeCount' => $contractRepository->countByStatus(),
        ]);
    }

    #[Route('/new', name: 'admin_contracts_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request,
        LeadRepository $leadRepository,
        ContractManager $contractManager,
    ): Response {
        $leads = $leadRepository->findWithoutContract();
        $contract = new Contract();
        $leadId = $request->query->getInt('lead');
        if ($leadId > 0) {
            foreach ($leads as $lead) {
                if ($lead->getId() === $leadId) {
                    $contract->setLead($lead);
                    break;
                }
            }
        }

        $form = $this->createForm(ContractCreateType::class, $contract, ['leads' => $leads]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $created = $contractManager->createFromLead(
                    $contract->getLead(),
                    $contract->getLoyerMensuel(),
                    $contract->getDureeMois(),
                    $contract->getDateSignature(),
                );
                $this->addFlash('success', 'Contrat créé'.($created->getCommission() ? ' avec commission TZANET.' : ' (sans commission).'));

                return $this->redirectToRoute('admin_contracts_show', ['id' => $created->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('admin/contracts/new.html.twig', [
            'form' => $form,
            'leads' => $leads,
        ]);
    }

    #[Route('/{id}', name: 'admin_contracts_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Contract $contract): Response
    {
        return $this->render('admin/contracts/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_contracts_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Contract $contract, ContractManager $contractManager): Response
    {
        if (!$this->isCsrfTokenValid('contract_delete_'.$contract->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $label = sprintf('#%d — %s', $contract->getId(), $contract->getLead()?->getNom() ?? 'contrat');
        $contractManager->delete($contract);
        $this->addFlash('success', sprintf('Contrat %s supprimé (commission liée incluse).', $label));

        return $this->redirectToRoute('admin_contracts');
    }
}
