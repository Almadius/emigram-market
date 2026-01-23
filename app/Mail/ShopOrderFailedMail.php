<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domains\Order\DTOs\OrderDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email уведомление об ошибке создания заказа в магазине
 */
final class ShopOrderFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly OrderDTO $order,
        private readonly string $error,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ошибка создания заказа в магазине',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop-order-failed',
            with: [
                'order' => $this->order,
                'error' => $this->error,
                'orderId' => $this->order->getId(),
                'shopDomain' => $this->order->getShopDomain(),
                'total' => $this->order->getTotal(),
                'currency' => $this->order->getCurrency(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
