<?php

declare(strict_types=1);

namespace App\Controller;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class QrCodeController extends AbstractController
{
    #[Route('/qr/tzanet', name: 'qr_tzanet_page', methods: ['GET'])]
    public function page(): Response
    {
        $targetUrl = $this->partnerLandingUrl();

        return $this->render('public/qr_tzanet.html.twig', [
            'targetUrl' => $targetUrl,
            'pageKey' => 'qr',
        ]);
    }

    #[Route('/qr/tzanet/image.png', name: 'qr_tzanet_image', methods: ['GET'])]
    public function image(): Response
    {
        $targetUrl = $this->partnerLandingUrl();

        $writer = new PngWriter();
        $result = $writer->write(
            new QrCode(
                data: $targetUrl,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 480,
                margin: 16,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
            ),
        );

        return new Response($result->getString(), Response::HTTP_OK, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'inline; filename="qr-tzanet.png"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function partnerLandingUrl(): string
    {
        return $this->generateUrl('partner_tzanet', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
