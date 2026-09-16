<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Offer;
use App\Form\Admin\OfferType;
use App\Repository\OfferRepository;
use App\Service\OfferManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/offers')]
final class OfferController extends AbstractController
{
    #[Route('', name: 'admin_offers', methods: ['GET'])]
    public function index(OfferRepository $offerRepository): Response
    {
        return $this->render('admin/offers/index.html.twig', [
            'offers' => $offerRepository->findBy([], ['dateDebut' => 'DESC', 'id' => 'DESC']),
            'activeOffer' => $offerRepository->findActiveOffer(),
        ]);
    }

    #[Route('/new', name: 'admin_offers_new', methods: ['GET', 'POST'])]
    public function new(Request $request, OfferManager $offerManager): Response
    {
        $offer = $offerManager->createDefaults();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $offerManager->save($offer);
                $this->addFlash(
                    'success',
                    $offer->isActive()
                        ? 'Nouvelle offre créée et activée. Les offres précédentes ont été désactivées.'
                        : 'Offre créée (inactive).',
                );

                return $this->redirectToRoute('admin_offers');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('admin/offers/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvelle offre',
            'is_new' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_offers_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Offer $offer, OfferManager $offerManager): Response
    {
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $offerManager->save($offer);
                $this->addFlash(
                    'success',
                    $offer->isActive()
                        ? 'Offre mise à jour et active. Les autres offres ont été désactivées.'
                        : 'Offre mise à jour (désactivée).',
                );

                return $this->redirectToRoute('admin_offers');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('admin/offers/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier l’offre #'.$offer->getId(),
            'offer' => $offer,
            'is_new' => false,
        ]);
    }
}
