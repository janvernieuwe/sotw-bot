<?php

namespace App\Subscriber\Rewatch;

use App\Channel\RewatchChannel;
use App\Event\MessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lets admins run symfony commands
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class RankingSubscriber implements EventSubscriberInterface
{
    const COMMAND = '!haamc rewatch ranking';

    /**
     * @var RewatchChannel
     */
    private $rewatch;

    /**
     * ValidateSubscriber constructor.
     *
     * @param RewatchChannel $rewatch
     */
    public function __construct(
        RewatchChannel $rewatch
    ) {
        $this->rewatch = $rewatch;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [];
        return [MessageReceivedEvent::NAME => 'onCommand'];
    }

    /**
     * @param MessageReceivedEvent $event
     */
    public function onCommand(MessageReceivedEvent $event): void
    {
        $message = $event->getMessage();
        if (strpos($message->content, self::COMMAND) !== 0) {
            return;
        }
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');
        $event->stopPropagation();

        $nominations = $this->rewatch->getValidNominations();
        $nominationCount = count($nominations);
        if (!$nominationCount) {
            $message->reply('Er zijn nog geen nominaties!');
            $io->writeln('No nominations yet', $nominationCount);

            return;
        }
        $output = ['De huidige rewatch ranking is'];
        foreach ($nominations as $i => $nomination) {
            $anime = $nomination->getAnime();
            $output[] = sprintf(
                ":tv: %s) **%s** (**%s** votes) door **%s**\neps: *%s* | score: *%s* | aired: *%s*",
                $i + 1,
                $anime->title,
                $nomination->getVotes(),
                $nomination->getAuthor(),
                $anime->episodes,
                $anime->score,
                $anime->aired_string
            );
        }
        $message->channel->send(implode(PHP_EOL, $output));
        $io->success('Ranking displayed');
    }
}
