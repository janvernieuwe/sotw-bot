<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BikkelRepository")
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"points"})})
 */
class Bikkel
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", unique=true)
     */
    private $memberId;

    /**
     * @ORM\Column(type="integer")
     */
    private $points = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    private $lastUpdate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $displayName;

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
    public function getMemberId(): ?int
    {
        return $this->memberId;
    }

    /**
     * @param int $memberId
     *
     * @return Bikkel
     */
    public function setMemberId(int $memberId): self
    {
        $this->memberId = $memberId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPoints(): ?int
    {
        return $this->points;
    }

    /**
     * @param int $points
     *
     * @return Bikkel
     */
    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    /**
     * @param \DateTimeInterface $lastUpdate
     *
     * @return Bikkel
     */
    public function setLastUpdate(\DateTimeInterface $lastUpdate): self
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    /**
     * Returns the new points
     *
     * @return int
     */
    public function addPoint(): int
    {
        return ++$this->points;
    }

    /**
     * @return string|null
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * @param mixed $displayName
     *
     * @return Bikkel
     */
    public function setDisplayName($displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }
}
