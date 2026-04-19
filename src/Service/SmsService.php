<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SmsService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $twilioSid,
        private string $twilioToken,
        private string $twilioFrom
    ) {}

    public function send(string $to, string $message): bool
    {
        // Normaliser le numéro tunisien → +216XXXXXXXX
        $to = $this->normalizePhone($to);

        try {
            $response = $this->httpClient->request(
                'POST',
                "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Messages.json",
                [
                    'auth_basic' => [$this->twilioSid, $this->twilioToken],
                    'body' => [
                        'From' => $this->twilioFrom,
                        'To'   => $to,
                        'Body' => $message,
                    ],
                ]
            );

            // Important: HttpClient est asynchrone par défaut. 
            // Appeler getStatusCode() force l'attente de la réponse.
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info("SMS envoyé avec succès à $to (Status: $statusCode)");
                return true;
            }

            $content = $response->getContent(false);
            $this->logger->error("Échec Twilio pour $to (Status: $statusCode) : $content");
            return false;

        } catch (\Exception $e) {
            $this->logger->error("Erreur SMS à $to : " . $e->getMessage());
            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        // Si 8 chiffres → numéro tunisien local
        if (strlen($phone) === 8) {
            return '+216' . $phone;
        }

        // Si déjà avec indicatif
        if (strlen($phone) > 8) {
            return '+' . ltrim($phone, '+');
        }

        return $phone;
    }
}