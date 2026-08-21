<?php

namespace App\Command;

use App\Entity\Business\Company;
use App\Entity\Business\Station;
use App\Entity\Business\Agent;
use App\Entity\Business\Passenger;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:update-uuids',
    description: 'Génère et met à jour les UUID manquants pour les compagnies, gares, agents et passagers.'
)]
class UpdateEntityUuidsCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this->setDescription('Génère et met à jour les UUID manquants pour les compagnies, gares, agents et passagers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Début de la mise à jour des UUID manquants...</info>');

        // 1. Update Companies
        $companies = $this->em->getRepository(Company::class)->findAll();
        $companyCount = 0;
        foreach ($companies as $company) {
            if (!$company->getUuid()) {
                $company->setUuid(Uuid::uuid4()->toString());
                $this->em->persist($company);
                $companyCount++;
            }
        }

        // 2. Update Stations
        $stations = $this->em->getRepository(Station::class)->findAll();
        $stationCount = 0;
        foreach ($stations as $station) {
            if (!$station->getUuid()) {
                $station->setUuid(Uuid::uuid4()->toString());
                $this->em->persist($station);
                $stationCount++;
            }
        }

        // 3. Update Agents
        $agents = $this->em->getRepository(Agent::class)->findAll();
        $agentCount = 0;
        foreach ($agents as $agent) {
            $updated = false;
            if (!$agent->getUuid()) {
                $agent->setUuid(Uuid::uuid4()->toString());
                $updated = true;
            }
            if ($agent->getStationAssigned() || $agent->getIsActivated() || $agent->getCompany()) {
                if ($agent->getStatus() !== 'APPROVED') {
                    $agent->setStatus('APPROVED');
                    $agent->setIsActivated(true);
                    $updated = true;
                }
            }
            if ($updated) {
                $this->em->persist($agent);
                $agentCount++;
            }
        }

        // 4. Update Passengers
        $passengers = $this->em->getRepository(Passenger::class)->findAll();
        $passengerCount = 0;
        foreach ($passengers as $passenger) {
            if (!$passenger->getUuid()) {
                $passenger->setUuid(Uuid::uuid4()->toString());
                $this->em->persist($passenger);
                $passengerCount++;
            }
        }

        // 5. Clean up mock notifications
        $notifs = $this->em->getRepository(\App\Entity\Extra\Notification::class)->findAll();
        $notifDeletedCount = 0;
        foreach ($notifs as $n) {
            if (str_contains($n->getMessage(), 'TKT-10293') || str_contains($n->getTitle(), 'Bienvenue sur Agent')) {
                $this->em->remove($n);
                $notifDeletedCount++;
            }
        }

        $this->em->flush();

        $output->writeln("<info>Mise à jour terminée avec succès :</info>");
        $output->writeln(" - Compagnies mises à jour : <comment>$companyCount</comment>");
        $output->writeln(" - Gares mises à jour : <comment>$stationCount</comment>");
        $output->writeln(" - Agents mis à jour : <comment>$agentCount</comment>");
        $output->writeln(" - Passagers mis à jour : <comment>$passengerCount</comment>");

        return Command::SUCCESS;
    }
}
