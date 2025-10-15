<?php

namespace App\Services;

use App\Models\Contract;
use Illuminate\Support\Facades\Storage;

class ContractPdfService
{
    /**
     * Generate a simple PDF placeholder for the signed contract and return the stored path.
     * In production, replace with real PDF generation.
     */
    public function generate(Contract $contract): string
    {
        $dir = 'contracts';
        $filename = 'contract-' . $contract->id . '.pdf';
        $path = $dir . '/' . $filename;

        $buyer = $contract->buyer?->name ?? ('Buyer #' . $contract->buyer_id);
        $seller = $contract->seller?->name ?? ('Seller #' . $contract->seller_id);
        $buyerVerified = $contract->buyer?->verification_status === 'verified' ? 'Verified' : 'Unverified';
        $sellerVerified = $contract->seller?->verification_status === 'verified' ? 'Verified' : 'Unverified';

        // Prefer Dompdf if available; fallback to a plain text placeholder
        if (class_exists(\Dompdf\Dompdf::class)) {
            $html = '<html><head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;font-size:12px} h1{font-size:18px} .meta{margin:10px 0} .badge{display:inline-block;padding:2px 6px;border-radius:4px;background:#eee;margin-left:6px;font-size:10px} .verified{background:#c6f6d5} .unverified{background:#fed7d7} .section{margin:12px 0}</style></head><body>' .
                '<h1>Contract Agreement</h1>' .
                '<div class="meta">Title: ' . e($contract->title) . '</div>' .
                '<div class="meta">Status: ' . e($contract->status) . '</div>' .
                '<div class="meta">Price: ' . number_format($contract->price_cents / 100, 2) . ' ' . e($contract->currency) . '</div>' .
                '<div class="section">' .
                'Buyer: ' . e($buyer) . ' <span class="badge ' . ($buyerVerified === 'Verified' ? 'verified' : 'unverified') . '">' . e($buyerVerified) . '</span><br/>' .
                'Seller: ' . e($seller) . ' <span class="badge ' . ($sellerVerified === 'Verified' ? 'verified' : 'unverified') . '">' . e($sellerVerified) . '</span>' .
                '</div>' .
                '<div class="section">Signed: ' . ($contract->buyer_accepted_at && $contract->seller_accepted_at ? 'Yes' : 'Partial') . '</div>' .
                '<div class="section"><strong>Disclaimer:</strong> This document is generated digitally and includes a verification badge indicator for each party. In the event of a dispute, the platform bears no responsibility if any party is unverified.</div>' .
                '</body></html>';

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->render();
            $output = $dompdf->output();
            Storage::put($path, $output);
            return Storage::path($path);
        }

        $content = "Contract: {$contract->title}\n" .
            "Status: {$contract->status}\n" .
            "Buyer: {$buyer} ({$buyerVerified})\n" .
            "Seller: {$seller} ({$sellerVerified})\n" .
            "Price: " . number_format($contract->price_cents / 100, 2) . " {$contract->currency}\n" .
            "Signed: " . ($contract->buyer_accepted_at && $contract->seller_accepted_at ? 'Yes' : 'Partial') . "\n" .
            "Disclaimer: This document is generated digitally and includes a verification badge indicator for each party. In the event of a dispute, the platform bears no responsibility if any party is unverified.";

        Storage::put($path, $content);
        return Storage::path($path);
    }
}