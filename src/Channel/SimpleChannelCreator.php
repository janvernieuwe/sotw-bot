<?php


namespace App\Channel;

use App\Context\CreateSimpleChannelContext;
use App\Entity\Reaction;
use App\Message\SimpleJoinableChannelMessage;
use CharlotteDunois\Yasmin\Models\CategoryChannel;
use CharlotteDunois\Yasmin\Models\Guild;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;

/**
 * Class SimpleChannelCreator
 *
 * @package App\Channel
 */
class SimpleChannelCreator
{
    /**
     * @var CreateSimpleChannelContext
     */
    private $context;

    /**
     * SimpleChannelCreator constructor.
     *
     * @param CreateSimpleChannelContext $context
     */
    public function __construct(CreateSimpleChannelContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param CreateSimpleChannelContext $context
     *
     * @internal param MessageReceivedEvent $event
     */
    public function create(CreateSimpleChannelContext $context): void
    {
        $this->createChannel($context->getGuild(), $context->getChannelName());
    }

    /**
     * @param Guild  $guild
     * @param string $name
     */
    protected function createChannel(Guild $guild, string $name): void
    {
        /** @var CategoryChannel $category */
        $category = $guild->channels->get($this->context->getParent());
        $permissions = $category->permissionOverwrites->all();
        $permissions = array_merge(
            $permissions,
            [
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
            ]
        );
        $guild->createChannel(
            [
                'name'                 => $name,
                'topic'                => $name,
                'permissionOverwrites' => $permissions,
                'parent'               => $this->context->getParent(),
                'nsfw'                 => false,
            ]
        )->done(
            function (TextChannel $channel) {
                $channel->setTopic($this->context->getDescription());
                $this->sendJoinMessage($channel);
            }
        );
    }

    /**
     * @param TextChannel $channel
     */
    public function sendJoinMessage(TextChannel $channel): void
    {
        $embed = SimpleJoinableChannelMessage::generateRichChannelMessage(
            (int)$channel->id,
            0,
            $this->context->getDescription()
        );
        $this->context->getChannel()
            ->send(SimpleJoinableChannelMessage::TEXT_MESSAGE, $embed)
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
        $message->react(Reaction::JOIN);
        $message->react(Reaction::LEAVE);
    }
}
