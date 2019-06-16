<?php

namespace App\Subscriber;

use App\Event\MessageReceivedEvent;
use CharlotteDunois\Yasmin\Models\GuildBan;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExportSubscriber
 *
 * @package App\Subscriber
 */
class BanExportSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc banexport';

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

        $message->guild->fetchBans()
            ->done(
                function ($bans) use ($io, $message) {
                    if (!$fp = fopen('php://memory', 'wb')) {
                        $io->error('Cannot open fp');
                    }
                    fputcsv(
                        $fp,
                        [
                            'count',
                            'username',
                            'userid',
                            'reason'
                        ]
                    );
                    /** @var GuildBan $ban */
                    foreach ($bans as $ban) {
                        $count = 0;
                        fputcsv(
                            $fp,
                            [
                                ++$count,
                                $ban->user->username,
                                $ban->user->id,
                                $ban->reason
                            ]
                        );
                    }
                    rewind($fp);
                    $contents = stream_get_contents($fp);
                    $message->reply(
                        'Here is your export ',
                        ['files' => [['name' => 'bans' . date(DATE_ATOM) . '.csv', 'data' => $contents]]]
                    );
                }
            );

        $io->success('Exported bans');
    }
}
