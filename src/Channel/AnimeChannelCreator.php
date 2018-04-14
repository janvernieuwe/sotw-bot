<?php


namespace App\Channel;

use App\Context\CreateAnimeChannelContext;
use App\Message\JoinableChannelMessage;
use CharlotteDunois\Yasmin\Models\Guild;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;

/**
 * Class AnimeChannelCreator
 * @package App\Channel
 */
class AnimeChannelCreator
{
    /**
     * @var CreateAnimeChannelContext
     */
    private $context;

    /**
     * @param CreateAnimeChannelContext $context
     * @internal param MessageReceivedEvent $event
     */
    public function create(CreateAnimeChannelContext $context): void
    {
        $this->context = $context;
        $this->createChannel($context->getGuild(), $context->getChannelName());
    }

    /**
     * @param Guild $guild
     * @param string $name
     */
    protected function createChannel(Guild $guild, string $name): void
    {
        $guild->createChannel(
            [
                'name'                 => $name,
                'topic'                => $name,
                'permissionOverwrites' => [
                    [
                        'id'   => $this->context->getEveryoneRole(),
                        'deny' => Channel::ROLE_VIEW_MESSAGES,
                        'type' => 'role',
                    ],
                    [
                        'id'    => $this->context->getClient()->user->id,
                        'allow' => Channel::ROLE_VIEW_MESSAGES,
                        'type'  => 'member',
                    ],
                ],
                'parent'               => $this->context->getParent(),
                'nsfw'                 => false,
            ]
        )->done(
            function (TextChannel $channel) {
                $channel->setTopic(
                    sprintf('%s || %s', $this->context->getAnime()->title, $this->context->getAnime()->link_canonical)
                );
                /** @var Message $announcement */
                $channel->send(
                    sprintf(
                        "%s Hoi iedereen! In dit channel kijken we naar **%s**.\n%s",
                        JoinableChannelMessage::TEXT_MESSAGE,
                        $this->context->getAnime()->title,
                        $this->context->getAnime()->link_canonical
                    )
                )->then(
                    function (Message $announcement) {
                        $announcement->pin();
                    }
                );

                $this->sendJoinMessage($channel);
            }
        );
    }

    /**
     * @param TextChannel $channel
     */
    protected function sendJoinMessage(TextChannel $channel): void
    {
        $embed = JoinableChannelMessage::generateRichChannelMessage(
            $this->context->getAnime(),
            (int)$channel->id,
            $this->context->getAnime()->link_canonical.'?c='.$channel->id
        );
        $this->context->getChannel()
            ->send(JoinableChannelMessage::TEXT_MESSAGE, $embed)
            ->done(
                function (Message $message) {
                    $this->addReactions($message);
                }
            );
    }

    /**
     * @param Message $message
     */
    protected function addReactions(Message $message): void
    {
        $message->react(JoinableChannelMessage::JOIN_REACTION);
        $message->react(JoinableChannelMessage::LEAVE_REACTION);
    }
}
