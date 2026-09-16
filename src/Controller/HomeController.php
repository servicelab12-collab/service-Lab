<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\LeadCanal;
use App\Enum\LeadSource;
use App\Form\PublicLeadType;
use App\Repository\OfferRepository;
use App\Service\LeadManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(OfferRepository $offerRepository): Response
    {
        return $this->render('public/home.html.twig', [
            'pageKey' => 'home',
            'offer' => $offerRepository->findActiveOffer(),
        ]);
    }

    #[Route('/partner/tzanet', name: 'partner_tzanet', methods: ['GET', 'POST'])]
    public function partnerTzanet(
        Request $request,
        LeadManager $leadManager,
        OfferRepository $offerRepository,
    ): Response {
        $form = $this->createForm(PublicLeadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{nom: string, telephone: string, email: string, machineRecherchee: string} $data */
            $data = $form->getData();

            $lead = $leadManager->createLead(
                nom: $data['nom'],
                telephone: $data['telephone'],
                email: $data['email'],
                machineRecherchee: $data['machineRecherchee'],
                source: LeadSource::TZANET,
                canal: LeadCanal::TZANET,
                codePromo: 'TZANET',
            );

            $request->getSession()->set('last_lead_reference', $lead->getReference());
            $request->getSession()->set('last_lead_source', LeadSource::TZANET->value);
            $request->getSession()->set('last_lead_canal', LeadCanal::TZANET->value);

            return $this->redirectToRoute('app_lead_success');
        }

        return $this->render('public/partner_tzanet.html.twig', [
            'form' => $form,
            'source' => LeadSource::TZANET,
            'canal' => LeadCanal::TZANET,
            'offer' => $offerRepository->findActiveOffer(),
            'pageKey' => 'partner',
        ]);
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function contact(OfferRepository $offerRepository): Response
    {
        return $this->render('public/contact.html.twig', [
            'pageKey' => 'contact',
            'offer' => $offerRepository->findActiveOffer(),
        ]);
    }

    #[Route('/a-propos', name: 'app_about', methods: ['GET'])]
    public function about(OfferRepository $offerRepository): Response
    {
        return $this->render('public/about.html.twig', [
            'pageKey' => 'about',
            'offer' => $offerRepository->findActiveOffer(),
        ]);
    }

    #[Route('/demande/merci', name: 'app_lead_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $reference = $request->getSession()->get('last_lead_reference');
        $source = $request->getSession()->get('last_lead_source');
        $canal = $request->getSession()->get('last_lead_canal');

        if (!$reference) {
            return $this->redirectToRoute('partner_tzanet');
        }

        return $this->render('public/success.html.twig', [
            'reference' => $reference,
            'source' => $source,
            'canal' => $canal,
            'pageKey' => 'success',
        ]);
    }
}
