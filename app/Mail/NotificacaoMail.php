<?php

namespace App\Mail;

use App\Models\Notificacao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacaoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Notificacao $notificacao) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '['.config('sdgt.orgao.sigla').'] '.$this->notificacao->titulo);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notificacao');
    }
}
