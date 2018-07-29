<?php

namespace App\Subscriber\Cots;

use App\Channel\Channel;
use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use CharlotteDunois\Yasmin\Models\Permissions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class StartSubscriber implements EventSubscriberInterface
{
    public const COMMAND = '!haamc cots start';

    /**
     * @var string
     */
    private $season;

    /**
     * @var int
     */
    private $cotsChannelId;

    /**
     * @var int
     */
    private $roleId;

    /**
     * ValidateSubscriber constructor.
     *
     * @param string    $season
     * @param int       $cotsChannelId
     * @param int       $roleId
     */
    public function __construct(
        string $season,
        int $cotsChannelId,
        int $roleId
    ) {
        $this->season = $season;
        $this->cotsChannelId = $cotsChannelId;
        $this->roleId = $roleId;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        if (!$event->isAdmin() || strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        /** @var GuildChannelInterface $guildChannel */
        $guildChannel = $message->guild->channels->get($this->cotsChannelId);
        $permissions = new Permissions();
        $permissions->add(Channel::ROLE_VIEW_MESSAGES);
        $permissions->add(Channel::ROLE_SEND_MESSAGES);
        $guildChannel->overwritePermissions(
            $this->roleId,
            $permissions,
            0,
            'Opened Cots nominaions'
        );
        /** @var TextChannelInterface $cotsChannel */
        $cotsChannel = $message->client->channels->get($this->cotsChannelId);
        $cotsChannel->send(sprintf('Bij deze zijn de nominaties voor season  %s geopend!', $this->season));
        $io->success('Opened nominations');
    }
}
