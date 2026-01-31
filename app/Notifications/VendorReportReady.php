<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class VendorReportReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Vendor $vendor,
        public string $reportType,
        public string $filePath
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reportName = ucfirst($this->reportType) . ' Report';
        $downloadUrl = Storage::disk('private')->temporaryUrl($this->filePath, now()->addHours(24));

        return (new MailMessage)
            ->subject("📊 Your {$reportName} is Ready")
            ->greeting("Hello, {$this->vendor->business_name}!")
            ->line("Your requested {$reportName} has been generated and is ready for download.")
            ->line('The download link will expire in 24 hours.')
            ->action('Download Report', $downloadUrl)
            ->line('You can also access your reports from the Vendor Dashboard.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'vendor_id' => $this->vendor->id,
            'report_type' => $this->reportType,
            'file_path' => $this->filePath,
        ];
    }
}
