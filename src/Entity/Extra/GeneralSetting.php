<?php

namespace App\Entity\Extra;

use App\Repository\Extra\GeneralSettingRepository;
use App\Traits\EntityTrait;
use App\Traits\UserObjectTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GeneralSettingRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class GeneralSetting
{
    use EntityTrait;
    use UserObjectTrait;
    use SoftDeleteableEntity;

    #[Groups(['setting'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Groups(['setting'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private $fraisServiceTicket = 600;

    #[Groups(['setting'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private $delaiOptionStandard = 14;

    #[Groups(['setting'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private $dureeExpirationBillet = 0;


    #[Groups(['setting'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $reserveFinanciereInitiale = '10 000 000';

    #[Groups(['setting'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $cleApiSmsNotification;

    #[Groups(['setting'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private $fraisDossier = 50000;

    #[Groups(['setting'])]
    #[ORM\Column(type: 'float', nullable: true)]
    private $penaliteRetardJournaliere = 1.5;

    #[Groups(['setting'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private $delaiGracePenalite = 3;

    #[Groups(['setting'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private $dureeContratDefautMois = 36;

    #[Groups(['setting'])]
    #[ORM\Column(type: 'float', nullable: true)]
    private $apportInitialPourcentage = 20.0;

    public function getId(): ?int { return $this->id; }

    public function getFraisServiceTicket(): ?int { return $this->fraisServiceTicket; }
    public function setFraisServiceTicket(?int $fraisServiceTicket): self { $this->fraisServiceTicket = $fraisServiceTicket; return $this; }

    public function getDelaiOptionStandard(): ?int { return $this->delaiOptionStandard; }
    public function setDelaiOptionStandard(?int $delaiOptionStandard): self { $this->delaiOptionStandard = $delaiOptionStandard; return $this; }

    public function getDureeExpirationBillet(): ?int { return $this->dureeExpirationBillet; }
    public function setDureeExpirationBillet(?int $dureeExpirationBillet): self { $this->dureeExpirationBillet = $dureeExpirationBillet; return $this; }


    public function getReserveFinanciereInitiale(): ?string { return $this->reserveFinanciereInitiale; }
    public function setReserveFinanciereInitiale(?string $reserveFinanciereInitiale): self { $this->reserveFinanciereInitiale = $reserveFinanciereInitiale; return $this; }

    public function getCleApiSmsNotification(): ?string { return $this->cleApiSmsNotification; }
    public function setCleApiSmsNotification(?string $cleApiSmsNotification): self { $this->cleApiSmsNotification = $cleApiSmsNotification; return $this; }

    public function getFraisDossier(): ?int { return $this->fraisDossier; }
    public function setFraisDossier(?int $fraisDossier): self { $this->fraisDossier = $fraisDossier; return $this; }

    public function getPenaliteRetardJournaliere(): ?float { return $this->penaliteRetardJournaliere; }
    public function setPenaliteRetardJournaliere(?float $penaliteRetardJournaliere): self { $this->penaliteRetardJournaliere = $penaliteRetardJournaliere; return $this; }

    public function getDelaiGracePenalite(): ?int { return $this->delaiGracePenalite; }
    public function setDelaiGracePenalite(?int $delaiGracePenalite): self { $this->delaiGracePenalite = $delaiGracePenalite; return $this; }

    public function getDureeContratDefautMois(): ?int { return $this->dureeContratDefautMois; }
    public function setDureeContratDefautMois(?int $dureeContratDefautMois): self { $this->dureeContratDefautMois = $dureeContratDefautMois; return $this; }

    public function getApportInitialPourcentage(): ?float { return $this->apportInitialPourcentage; }
    public function setApportInitialPourcentage(?float $apportInitialPourcentage): self { $this->apportInitialPourcentage = $apportInitialPourcentage; return $this; }
}