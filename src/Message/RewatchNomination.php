<?php

namespace App\Message;

use CharlotteDunois\Yasmin\Models\Message as YasminMessage;
use Jikan\Model\Anime;
use Symfony\Component\Validator\Constraints as Assert;
use function GuzzleHttp\Psr7\parse_query;

/**
 * Class SotwNomination
 * @package App\Entity
 */
class RewatchNomination extends Message
{
    /**
     * @var Anime
     */
    private $anime;

    /**
     * @param string $content
     * @return bool
     */
    public static function isContender(string $content): bool
    {
        return preg_match('/^https?:\/\/myanimelist\.net\/anime/', $content);
    }

    /**
     * @param YasminMessage $message
     * @return RewatchNomination
     */
    public static function fromYasmin(YasminMessage $message): RewatchNomination
    {
        return new self(parent::yasminToArray($message));
    }

    /**
     * @return int|null
     */
    public function getAnimeId(): ?int
    {
        $url = parse_url($this->message['content']);
        $url['query'] = $url['query'] ?? '';
        $params = parse_query($url['query']);
        if (isset($params['id'])) {
            return (int)$params['id'];
        }
        preg_match_all('/\/(\d+)\/?/', $this->message['content'], $matches);
        if (!isset($matches[1][0])) {
            return null;
        }

        return (int)$matches[1][0];
    }

    /**
     * @Assert\GreaterThanOrEqual(value="10", message="Te weinig afleveringen (minstens 10)")
     * @Assert\LessThanOrEqual(value="13", message="Te veel afleveringen (maximaal 13)")
     *
     * @return int|null
     */
    public function getEpisodeCount(): ?int
    {
        if (!$this->anime instanceof Anime) {
            return null;
        }

        return $this->anime->episodes;
    }

    /**
     * @return Anime
     */
    public function getAnime(): Anime
    {
        return $this->anime;
    }

    /**
     * @param Anime $anime
     */
    public function setAnime(Anime $anime): void
    {
        $this->anime = $anime;
    }

    /**
     * @Assert\IsFalse(message="Geen hentai!")
     * @return bool
     */
    public function isHentai(): bool
    {
        foreach ($this->anime->genre as $genre) {
            if ($genre['name'] === 'Hentai') {
                return true;
            }
        }

        return false;
    }

    /**
     * @Assert\IsTrue(message="Anime is te nieuw")
     * @return bool
     */
    public function isValidDate(): bool
    {
        $max = new \DateTime('-2 years');

        return !($this->getEndDate() > $max);
    }

    /**
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return new \DateTime($this->anime->aired['to']);
    }
}
