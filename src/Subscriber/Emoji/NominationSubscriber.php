<?php

namespace App\Subscriber\Emoji;

use App\Event\MessageReceivedEvent;
use App\Message\YasminEmojiNominationAttachment;
use App\Util\Util;
use CharlotteDunois\Yasmin\Models\Emoji;
use CharlotteDunois\Yasmin\Models\Message;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Display the current bikkel ranking
 * Class ValidateSubscriber
 *
 * @package App\Subscriber
 */
class NominationSubscriber implements EventSubscriberInterface
{
    private const ERROR_ATTACHMENT_COUNT = ':x: 1 afbeelding per bericht';
    private const DELETE_TIMEOUT = 5;

    /**
     * @var int
     */
    private $channelId;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * NominationSubscriber constructor.
     *
     * @param int                $emojiChannelId
     * @param ValidatorInterface $validator
     *
     * @internal param int $channelId
     */
    public function __construct(int $emojiChannelId, ValidatorInterface $validator)
    {
        $this->channelId = $emojiChannelId;
        $this->validator = $validator;
    }

    /**
     *
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
        /** @noinspection PhpUndefinedFieldInspection */
        if ($this->channelId !== (int)$message->channel->id) {
            return;
        }
        if ($event->isAdmin() && strpos($message->content, '!haamc') !== false) {
            return;
        }
        $event->stopPropagation();
        $io = $event->getIo();
        $io->writeln(__CLASS__.' dispatched');

        // Validate single attachment
        if ($message->attachments->count() !== 1) {
            $this->error($io, $message, self::ERROR_ATTACHMENT_COUNT);

            return;
        }
        // Validate attachment
        $attachment = new YasminEmojiNominationAttachment($message->attachments->first(), $message);
        $errors = $this->validator->validate($attachment);
        if ($errors->count()) {
            $this->error(
                $io,
                $message,
                "\n:x: ".Util::errorsToString($errors, "\n:x: ")
            );

            return;
        }
        $message->guild->createEmoji($attachment->getUrl(), $attachment->getName())->done(
            function (Emoji $emoji) use ($message, $io) {
                $message->channel->send(Util::emojiToString($emoji))->done(
                    function (Message $emojiPost) use ($message, $emoji, $io) {
                        $emojiPost->react(Util::emojiToString($emoji));
                        $message->delete();
                        $emoji->delete();
                        $io->success(sprintf('Emoji %s nominated', $emoji->name));
                    }
                );
            }
        );
    }

    /**
     * @param SymfonyStyle $io
     * @param Message      $message
     * @param string       $error
     */
    private function error(SymfonyStyle $io, Message $message, string $error): void
    {
        $io->error($error);
        $message->reply($error)->done(
            function (Message $err) use ($message) {
                $message->delete(self::DELETE_TIMEOUT);
                $err->delete(self::DELETE_TIMEOUT);
            }
        );
    }
}
