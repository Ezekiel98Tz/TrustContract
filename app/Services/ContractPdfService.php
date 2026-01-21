<?php

namespace App\Services;

use App\Models\Contract;
use Illuminate\Support\Facades\Storage;

class ContractPdfService
{
    /**
     * Generate a PDF for the signed contract and return the stored relative path.
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
            $amount = number_format($contract->price_cents / 100, 2);
            $html = '<html><head><meta charset="utf-8"><style>
                body{font-family:Arial,sans-serif;font-size:12px;color:#111}
                h1{font-size:20px;margin:0 0 8px}
                h2{font-size:14px;margin:16px 0 6px;border-bottom:1px solid #ddd;padding-bottom:4px}
                .meta{margin:8px 0}
                .badge{display:inline-block;padding:2px 6px;border-radius:4px;background:#eee;margin-left:6px;font-size:10px}
                .verified{background:#c6f6d5}
                .unverified{background:#fed7d7}
                .section{margin:12px 0}
                .box{border:1px solid #ddd;padding:8px;border-radius:6px;margin-top:6px}
                ul{padding-left:16px}
                .small{font-size:10px;color:#666}
            </style></head><body>' .
                '<h1>Contract Agreement</h1>' .
                '<div class="meta"><strong>Contract ID:</strong> ' . e((string)$contract->id) . '</div>' .
                '<div class="meta"><strong>Title:</strong> ' . e($contract->title) . '</div>' .
                '<div class="meta"><strong>Status:</strong> ' . e($contract->status) . '</div>' .
                '<div class="meta"><strong>Created:</strong> ' . e((string)$contract->created_at) . '</div>' .
                '<div class="meta"><strong>Amount:</strong> ' . $amount . ' ' . e($contract->currency) . '</div>' .
                '<div class="section">' .
                '<h2>Parties</h2>' .
                '<div class="box"><strong>Buyer:</strong> ' . e($buyer) . ' <span class="badge ' . ($buyerVerified === 'Verified' ? 'verified' : 'unverified') . '">' . e($buyerVerified) . '</span><br/>' .
                '<strong>Seller:</strong> ' . e($seller) . ' <span class="badge ' . ($sellerVerified === 'Verified' ? 'verified' : 'unverified') . '">' . e($sellerVerified) . '</span></div>' .
                '</div>' .
                '<div class="section"><h2>Terms</h2><div class="box">' . nl2br(e((string)$contract->description)) . '</div></div>' .
                '<div class="section"><h2>Signatures</h2><div class="box"><ul>' .
                    ($contract->signatures->count() ? implode('', $contract->signatures->map(function($s) {
                        $who = $s->user?->name ?? ('User #'.$s->user_id);
                        return '<li><strong>'.e($who).'</strong> — '.e((string)$s->signed_at).'</li>';
                    })->toArray()) : '<li>No signatures recorded.</li>') .
                '</ul></div></div>' .
                '<div class="section small"><strong>Disclaimer:</strong> This document is generated digitally and includes verification badges. Ensure both parties are verified for increased trust. Platform provides tools but does not guarantee outcomes of agreements.</div>' .
                '</body></html>';

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            $output = $dompdf->output();
            Storage::put($path, $output);
            return $path;
        }

        $content = "Contract Agreement\n" .
            "Contract ID: {$contract->id}\n" .
            "Title: {$contract->title}\n" .
            "Status: {$contract->status}\n" .
            "Created: {$contract->created_at}\n" .
            "Buyer: {$buyer} ({$buyerVerified})\n" .
            "Seller: {$seller} ({$sellerVerified})\n" .
            "Amount: " . number_format($contract->price_cents / 100, 2) . " {$contract->currency}\n" .
            "Terms: " . trim((string)$contract->description) . "\n" .
            "Signatures: " . ($contract->signatures->count() ? implode(', ', $contract->signatures->map(function($s){ return ($s->user?->name ?? ('User #'.$s->user_id)).' @ '.$s->signed_at; })->toArray()) : 'None') . "\n" .
            "Disclaimer: This document is generated digitally. For a formatted PDF, install dompdf/dompdf.";

        Storage::put($path, $content);
        return $path;
    }
}
