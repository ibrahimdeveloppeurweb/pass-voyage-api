<?php

namespace App\Command;

use App\Entity\Business\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:tickets:expire',
    description: 'Vérifie et met à jour le statut des billets dont la date d\'expiration est dépassée.'
)]
class ExpireTicketsCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setDescription('Vérifie et met à jour le statut des billets dont la date d\'expiration est dépassée.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTime();

        $tickets = $this->em->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->where('t.expirationDate < :now')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('now', $now)
            ->setParameter('statuses', ['VALIDATED'])
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($tickets as $ticket) {
            $ticket->setStatus('EXPIRED');
            $this->em->persist($ticket);
            $count++;
        }

        if ($count > 0) {
            $this->em->flush();
            $output->writeln("<info>$count billet(s) ont été mis à jour au statut EXPIRED.</info>");
        } else {
            $output->writeln('<info>Aucun billet expiré trouvé.</info>');
        }

        return Command::SUCCESS;
    }
}
