<?php

namespace App\Discord;

use App\Entity\SotwContender;
use RestCord\DiscordClient;

class SongOfTheWeek
{
    public const ROLE_SEND_MESSAGES = 0x00000800;

    /**
     * @var int
     */
    private $channelId;
    /**
     * @var DiscordClient
     */
    private $discord;

    /**
     * @var string
     */
    private $role;

    public function __construct(DiscordClient $discord, string $channelId, string $role)
    {

        $this->channelId = (int)$channelId;
        $this->discord = $discord;
        $this->role = $role;
    }

    /**
     * @param int $limit
     * @return SotwContender[]
     */
    public function getLastNominations(int $limit = 10): array
    {
        $messages = $this->discord->channel->getChannelMessages(
            [
                'channel.id' => $this->channelId,
                'limit'      => $limit + 10,
            ]
        );

        $contenders = [];
        foreach ($messages as $message) {
            // Stop parsing when we arrive at the nomination msg
            if (preg_match('/^Bij deze zijn de nominaties voor week/', $message['content'])) {
                break;
            }
            if (SotwContender::isContenter($message['content'])) {
                $contenders[] = new SotwContender($message);
            }
        }
        $contenders = \array_slice($contenders, 0, $limit);
        uasort(
            $contenders,
            function (SotwContender $a, SotwContender $b) {
                return $a->getVotes() < $b->getVotes();
            }
        );

        return array_values($contenders);
    }

    public function announceWinner(SotwContender $nomination): void
    {
        $this->discord->channel->createMessage(
            [
                'channel.id' => $this->channelId,
                'content'    => $this->createWinningMessage($nomination),
            ]
        );
    }

    private function createWinningMessage(SotwContender $nomination): string
    {
        return sprintf(
            "\nDe winnaar van week %s is: %s - %s (%s) door <@!%s>\n",
            (int)date('W'),
            $nomination->getArtist(),
            $nomination->getTitle(),
            $nomination->getAnime(),
            $nomination->getAuthorId()
        );
    }

    public function openNominations(): void
    {
        $this->discord->channel->createMessage(
            [
                'channel.id' => $this->channelId,
                'content'    => $this->createOpenNominationsMessage(),
            ]
        );
        $this->discord->channel->editChannelPermissions(
            [
                'channel.id'   => $this->channelId,
                'overwrite.id' => $this->role,
                'allow'        => self::ROLE_SEND_MESSAGES,
                'type'         => 'role',
            ]
        );
    }

    public function closeNominations(): void
    {
        $this->discord->channel->createMessage(
            [
                'channel.id' => $this->channelId,
                'content'    => $this->createCloseNominationsMessage(),
            ]
        );
        $this->discord->channel->editChannelPermissions(
            [
                'channel.id'   => $this->channelId,
                'overwrite.id' => $this->role,
                'deny'         => self::ROLE_SEND_MESSAGES,
                'type'         => 'role',
            ]
        );
    }

    /**
     * @return string
     */
    private function createOpenNominationsMessage(): string
    {
        return sprintf('Bij deze zijn de nominaties voor week %s geopend!', date('W') + 1);
    }

    private function createCloseNominationsMessage(): string
    {
        return 'Laat het stemmen beginnen!';
    }

    public function addReaction(SotwContender $contender): void
    {
        $this->discord->channel->createReaction(
            [
                'channel.id' => $this->channelId,
                'message.id' => $contender->getMessageId(),
                'emoji'      => '🔼',
            ]
        );
    }
}
