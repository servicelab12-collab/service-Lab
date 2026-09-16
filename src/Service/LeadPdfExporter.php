<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Lead;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class LeadPdfExporter
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function generate(Lead $lead): string
    {
        return $this->renderPdf('admin/leads/pdf.html.twig', [
            'lead' => $lead,
            'contract' => $lead->getContract(),
            'commission' => $lead->getContract()?->getCommission(),
            'generatedAt' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * @param list<Lead> $leads
     */
    public function generateAll(array $leads): string
    {
        return $this->renderPdf('admin/leads/pdf_all.html.twig', [
            'leads' => $leads,
            'generatedAt' => new \DateTimeImmutable(),
        ]);
    }

    public function filename(Lead $lead): string
    {
        $safeRef = preg_replace('/[^A-Za-z0-9_-]+/', '-', $lead->getReference()) ?: 'lead';

        return sprintf('fiche-client-%s.pdf', $safeRef);
    }

    public function filenameAll(): string
    {
        return sprintf('export-leads-tzanet-%s.pdf', (new \DateTimeImmutable())->format('Y-m-d'));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderPdf(string $template, array $context): string
    {
        $html = $this->twig->render($template, $context);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output() ?? '';
    }
}
