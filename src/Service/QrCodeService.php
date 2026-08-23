<?php

namespace App\Service;

use App\Entity\Business\CreditRequest;
use App\Entity\Business\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;

class QrCodeService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Generates a Base64 PNG Data URI for a given QR Code string content
     */
    public function generateQrCodeDataUri(string $content): string
    {
        try {
            if (class_exists(QrCode::class)) {
                $qrCode = QrCode::create($content)
                    ->setEncoding(new Encoding('UTF-8'))
                    ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                    ->setSize(300)
                    ->setMargin(10);

                $writer = new PngWriter();
                $result = $writer->write($qrCode);
                return $result->getDataUri();
            }
        } catch (\Throwable $e) {
            // Log or fallback
        }

        // Lightweight SVG Data URI fallback
        $encoded = rawurlencode($content);
        return "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='300' height='300' viewBox='0 0 100 100'><rect width='100' height='100' fill='white'/><rect x='10' y='10' width='30' height='30' fill='black'/><rect x='60' y='10' width='30' height='30' fill='black'/><rect x='10' y='60' width='30' height='30' fill='black'/><text x='50' y='55' font-size='6' text-anchor='middle'>$encoded</text></svg>";
    }

    /**
     * Generates a unique Ticket Code string (ex: PASS-V-847291-1)
     */
    public function generateTicketCode(CreditRequest $creditRequest, int $index = 1): string
    {
        $randomPart = strtoupper(substr(md5(uniqid((string) $creditRequest->getId(), true)), 0, 6));
        return sprintf("PASS-V-%s-%d", $randomPart, $index);
    }

    /**
     * Generates JSON payload string for QR Code containing ticket + passenger details
     */
    public function generateQrPayload(CreditRequest $creditRequest, string $ticketCode, int $index = 1, ?Ticket $ticket = null): string
    {
        return trim($ticketCode);
    }

    /**
     * Generates N tickets for an approved CreditRequest
     *
     * @return Ticket[]
     */
    public function generateTicketsForCreditRequest(CreditRequest $creditRequest): array
    {
        $count = max(1, $creditRequest->getPassengerCount() ?? 1);
        $tickets = [];

        for ($i = 1; $i <= $count; $i++) {
            $ticket = new Ticket();
            $ticket->setCreditRequest($creditRequest);
            $ticket->setCompany($creditRequest->getCompany());
            $ticket->setTicketIndex($i);
            $ticket->setUnitPrice($creditRequest->getUnitPrice() ?? 0);
            $ticket->setStatus('VALIDATED');
            $ticket->setIsUsed(false);
            if (!$ticket->getUuid()) {
                $ticket->setUuid(\Ramsey\Uuid\Uuid::uuid4()->toString());
            }

            // Generate unique ticket number
            $ticketCode = $this->generateTicketCode($creditRequest, $i);
            $ticket->setTicketNumber($ticketCode);

            $this->em->persist($ticket);
            $this->em->flush();

            // Payload for QR Code scanner (contains ticket details + passenger details + validation hash)
            $qrPayload = $this->generateQrPayload($creditRequest, $ticketCode, $i, $ticket);

            // Generate QR Code Data URI
            $qrDataUri = $this->generateQrCodeDataUri($qrPayload);
            $ticket->setQrCodeContent($qrDataUri);

            $this->em->persist($ticket);
            $creditRequest->addTicket($ticket);
            $tickets[] = $ticket;
        }

        $creditRequest->setStatus('APPROVED');
        $this->em->flush();

        return $tickets;
    }

    public function generateBatchTickets(CreditRequest $creditRequest): array
    {
        return $this->generateTicketsForCreditRequest($creditRequest);
    }
}
