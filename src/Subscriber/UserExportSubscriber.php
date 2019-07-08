<?php

namespace App\Subscriber;

use App\Command\CommandParser;
use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\GuildMember;
use CharlotteDunois\Yasmin\Models\PermissionOverwrite;
use CharlotteDunois\Yasmin\Models\TextChannel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExportSubscriber
 *
 * @package App\Subscriber
 */
class UserExportSubscriber implements EventSubscriberInterface
{
    public const ROLE_VIEW_MESSAGES = 0x00000400;
    const COMMAND = '!haamc userexport';

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
        if (strpos($message->content, self::COMMAND) !== 0 || !$event->isAdmin()) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__ . ' dispatched');
        $event->stopPropagation();
        $guild = $message->guild;

        $command = new CommandParser($message);
        $channelId = $command->parseArgument('channel') ?? $message->channel->getId();
        /** @var TextChannel $channel */
        $channel = $message->client->channels->get($channelId);
        if (!$fp = fopen('php://memory', 'wb')) {
            $io->error('Cannot open fp');
            return;
        }
        fputcsv($fp, ['username', 'id']);
        $overwrites = $channel->permissionOverwrites->all();
        /** @var PermissionOverwrite $overwrite */
        foreach ($overwrites as $overwrite) {
            if ($overwrite->type !== 'member') {
                continue;
            }
            if (!$overwrite->allow->has(self::ROLE_VIEW_MESSAGES)) {
                continue;
            }
            if ($guild->me->id === $overwrite->id) {
                continue;
            }
            /** @var GuildMember $guildMember */
            $guildMember = $guild->members->get($overwrite->id);
            fputcsv(
                $fp,
                [
                    $guildMember->user->username,
                    $guildMember->user->id,
                ]
            );
        }
        rewind($fp);
        $contents = stream_get_contents($fp);
        $message->reply(
            'Here is your export ',
            ['files' => [['name' => $channel->name . '_users_export.csv', 'data' => $contents]]]
        );

        $io->success('Exported channel users: ' . $channel->name);
    }
}
