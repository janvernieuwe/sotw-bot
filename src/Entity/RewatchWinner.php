<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RewatchWinnerRepository")
 */
class RewatchWinner
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
     * @ORM\Column(type="integer", unique=true)
     */
    private $animeId;

    /**
     * @ORM\Column(type="integer")
     */
    private $votes;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $displayName;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="integer")
     */
    private $episodes;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $aired;

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
     * @return RewatchWinner
     */
    public function setMemberId(int $memberId): self
    {
        $this->memberId = $memberId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getAnimeId(): ?int
    {
        return $this->animeId;
    }

    /**
     * @param int $animeId
     *
     * @return RewatchWinner
     */
    public function setAnimeId(int $animeId): self
    {
        $this->animeId = $animeId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getVotes(): ?int
    {
        return $this->votes;
    }

    /**
     * @param int $votes
     *
     * @return RewatchWinner
     */
    public function setVotes(int $votes): self
    {
        $this->votes = $votes;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     *
     * @return RewatchWinner
     */
    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

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
     *
     * @return RewatchWinner
     */
    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return RewatchWinner
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return int
     */
    public function getEpisodes(): int
    {
        return $this->episodes;
    }

    /**
     * @param mixed $episodes
     *
     * @return RewatchWinner
     */
    public function setEpisodes(int $episodes): self
    {
        $this->episodes = $episodes;

        return $this;
    }

    /**
     * @return string
     */
    public function getAired(): string
    {
        return $this->aired;
    }

    /**
     * @param mixed $aired
     *
     * @return RewatchWinner
     */
    public function setAired(string $aired): self
    {
        $this->aired = $aired;

        return $this;
    }
}
