<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SotwWinnerRepository")
 */
class SotwWinner
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint")
     */
    private $memberId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $artist;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $anime;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $youtube;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="integer")
     */
    private $votes;

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
     * @return SotwWinner
     */
    public function setMemberId(int $memberId): self
    {
        $this->memberId = $memberId;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getArtist(): ?string
    {
        return $this->artist;
    }

    /**
     * @param string $artist
     * @return SotwWinner
     */
    public function setArtist(string $artist): self
    {
        $this->artist = $artist;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return SotwWinner
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getAnime(): ?string
    {
        return $this->anime;
    }

    /**
     * @param string $anime
     * @return SotwWinner
     */
    public function setAnime(string $anime): self
    {
        $this->anime = $anime;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getYoutube(): ?string
    {
        return $this->youtube;
    }

    /**
     * @param string $youtube
     * @return SotwWinner
     */
    public function setYoutube(string $youtube): self
    {
        $this->youtube = $youtube;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    /**
     * @param \DateTimeInterface $created
     * @return SotwWinner
     */
    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * @param mixed $votes
     * @return SotwWinner
     */
    public function setVotes($votes): self
    {
        $this->votes = $votes;

        return $this;
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
     * @return SotwWinner
     */
    public function setDisplayName($displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }
}
