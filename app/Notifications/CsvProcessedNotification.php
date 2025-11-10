<?php

namespace App\Notifications;

use App\Models\Upload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CsvProcessedNotification extends Notification
{
    use Queueable;

    /**
     * @var \App\Models\Upload
     */
    public Upload $upload;

    /**
     * Create a new notification instance.
     */
    public function __construct(Upload $upload)
    {
        $this->upload = $upload;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Send both email and database notification
        return [
            // 'database',
            'mail'
        ];
    }

    // Database representation
    public function toArray($notifiable)
    {
        return [
            'upload_id' => $this->upload->id,
            'filename' => $this->upload->filename,
            'status' => $this->upload->status,
            'message' => "CSV file '{$this->upload->filename}' processed successfully."
        ];
    }

    // Email representation
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("CSV Processed: {$this->upload->filename}")
            ->line("Your CSV file '{$this->upload->filename}' has been processed successfully.")
            ->line("Status: {$this->upload->status}")
           // ->action('View Uploads', url('/uploads')) // optional link
            ->line('Login to the application to review it!');
    }
}
