<?php

namespace App\Manager\Extra;

use App\Entity\Extra\GeneralSetting;
use App\Entity\Extra\GeneralSettingHistory;
use App\Repository\Extra\GeneralSettingRepository;
use App\Repository\Extra\GeneralSettingHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class GeneralSettingManager
{
    private $em;
    private $repository;
    private $historyRepository;

    public function __construct(
        EntityManagerInterface $em,
        GeneralSettingRepository $repository,
        GeneralSettingHistoryRepository $historyRepository
        )
    {
        $this->em = $em;
        $this->repository = $repository;
        $this->historyRepository = $historyRepository;
    }

    /**
     * Either create or update the global general settings
     */
    public function updateOrCreate(object $data, $user = null): GeneralSetting
    {
        $settings = $this->repository->findAll();
        $setting = count($settings) > 0 ? $settings[0] : new GeneralSetting();

        // Capture previous values for history
        $previousValues = [
            'fraisServiceTicket' => $setting->getFraisServiceTicket(),
            'delaiOptionStandard' => $setting->getDelaiOptionStandard(),
            'reserveFinanciereInitiale' => $setting->getReserveFinanciereInitiale(),
            'cleApiSmsNotification' => $setting->getCleApiSmsNotification(),
            'fraisDossier' => $setting->getFraisDossier(),
            'penaliteRetardJournaliere' => $setting->getPenaliteRetardJournaliere(),
            'delaiGracePenalite' => $setting->getDelaiGracePenalite(),
            'dureeContratDefautMois' => $setting->getDureeContratDefautMois(),
            'apportInitialPourcentage' => $setting->getApportInitialPourcentage(),
        ];

        if (isset($data->fraisServiceTicket)) {
            $setting->setFraisServiceTicket((int)$data->fraisServiceTicket);
        }

        if (isset($data->delaiOptionStandard)) {
            $setting->setDelaiOptionStandard((int)$data->delaiOptionStandard);
        }

        if (isset($data->reserveFinanciereInitiale)) {
            $setting->setReserveFinanciereInitiale((string)$data->reserveFinanciereInitiale);
        }

        if (isset($data->cleApiSmsNotification)) {
            $setting->setCleApiSmsNotification((string)$data->cleApiSmsNotification);
        }

        if (isset($data->fraisDossier)) {
            $setting->setFraisDossier((int)$data->fraisDossier);
        }

        if (isset($data->penaliteRetardJournaliere)) {
            $setting->setPenaliteRetardJournaliere((float)$data->penaliteRetardJournaliere);
        }

        if (isset($data->delaiGracePenalite)) {
            $setting->setDelaiGracePenalite((int)$data->delaiGracePenalite);
        }

        if (isset($data->dureeContratDefautMois)) {
            $setting->setDureeContratDefautMois((int)$data->dureeContratDefautMois);
        }

        if (isset($data->apportInitialPourcentage)) {
            $setting->setApportInitialPourcentage((float)$data->apportInitialPourcentage);
        }

        $this->em->persist($setting);

        // Record History
        $history = new GeneralSettingHistory();
        if ($user) {
            $history->setCreateBy($user);
            $name = trim(($user->getNom() ?? '') . ' ' . ($user->getPrenom() ?? ''));
            $history->setUpdatedBy(!empty($name) ? $name : ($user->getUsername() ?? 'Administrateur'));
        }
        $history->setDescription($data->reason ?? 'Mise à jour des paramètres');
        $history->setPreviousValues($previousValues);
        $history->setNewValues([
            'fraisServiceTicket' => $setting->getFraisServiceTicket(),
            'delaiOptionStandard' => $setting->getDelaiOptionStandard(),
            'reserveFinanciereInitiale' => $setting->getReserveFinanciereInitiale(),
            'cleApiSmsNotification' => $setting->getCleApiSmsNotification(),
            'fraisDossier' => $setting->getFraisDossier(),
            'penaliteRetardJournaliere' => $setting->getPenaliteRetardJournaliere(),
            'delaiGracePenalite' => $setting->getDelaiGracePenalite(),
            'dureeContratDefautMois' => $setting->getDureeContratDefautMois(),
            'apportInitialPourcentage' => $setting->getApportInitialPourcentage(),
        ]);

        $this->em->persist($history);
        $this->em->flush();

        return $setting;
    }

    public function getSettings(): GeneralSetting
    {
        $settings = $this->repository->findAll();
        if (count($settings) > 0) {
            return $settings[0];
        }

        $setting = new GeneralSetting();
        $this->em->persist($setting);
        $this->em->flush();

        return $setting;
    }

    public function getHistory(): array
    {
        return $this->historyRepository->findBy([], ['createdAt' => 'DESC']);
    }
}