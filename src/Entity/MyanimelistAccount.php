<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MyanimelistAccountRepository")
 */
class MyanimelistAccount
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", unique=true, type="bigint")
     */
    private $discordId;

    /**
     * @ORM\Column(type="string", length=255, unique=true, type="guid")
     */
    private $MalNickname;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getDiscordId(): ?int
    {
        return $this->discordId;
    }

    /**
     * @param int $discordId
     * @return MyanimelistAccount
     */
    public function setDiscordId(int $discordId): self
    {
        $this->discordId = $discordId;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getMalNickname(): ?string
    {
        return $this->MalNickname;
    }

    /**
     * @param string $MalNickname
     * @return MyanimelistAccount
     */
    public function setMalNickname(string $MalNickname): self
    {
        $this->MalNickname = $MalNickname;

        return $this;
    }
}
