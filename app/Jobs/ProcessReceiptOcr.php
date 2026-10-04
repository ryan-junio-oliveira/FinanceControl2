<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Receipts\ReceiptManager;
use App\Receipts\ReceiptParser;
use App\Support\Audit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessReceiptOcr implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $attachmentId) {}

    public function handle(): void
    {
        $attachment = Attachment::find($this->attachmentId);
        if (! $attachment || ! Storage::disk('local')->exists($attachment->path)) {
            return;
        }
        Audit::silence(function () use ($attachment) {
            try {
                $text = ReceiptManager::reader()->read(Storage::disk('local')->path($attachment->path));
                $parsed = ReceiptParser::parse($text);
                $attachment->update([
                    'ocr_text' => mb_substr($text, 0, 8000),
                    'ocr_status' => 'done',
                    'ocr_data' => $parsed,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[ocr] falha', ['attachment' => $attachment->id, 'error' => $e->getMessage()]);
                $attachment->update(['ocr_status' => 'failed']);
            }
        });
    }
}
