<?php

namespace App\Subscriber\Emoji;

use App\Channel\Channel;
use App\Event\MessageReceivedEvent;
use App\Message\YasminEmojiNominationAttachment;
use App\Util\Util;
use CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface;
use CharlotteDunois\Yasmin\Interfaces\TextChannelInterface;
use CharlotteDunois\Yasmin\Models\Emoji;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\TextChannel;
use CharlotteDunois\Yasmin\Utils\Collection;
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
    private const COOLDOWN = 15;
    /**
     * @var MessageReceivedEvent
     */
    private $event;

    /**
     * @var int
     */
    private $channelId;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var int
     */
    private $roleId;

    /**
     * NominationSubscriber constructor.
     *
     * @param int $emojiChannelId
     * @param ValidatorInterface $validator
     * @param int $roleId
     */
    public function __construct(int $emojiChannelId, ValidatorInterface $validator, int $roleId)
    {
        $this->channelId = $emojiChannelId;
        $this->validator = $validator;
        $this->roleId = $roleId;
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
        $this->event = $event;
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
        $io->writeln(__CLASS__ . ' dispatched');

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
                "\n:x: " . Util::errorsToString($errors, "\n:x: ")
            );

            return;
        }
        /** @var GuildChannelInterface $channel */
        $channel = $message->guild->channels->get($this->channelId);
        $channel->overwritePermissions(
            $this->roleId,
            0,
            Channel::ROLE_SEND_MESSAGES,
            'Processing emoji'
        );
        /** @var TextChannelInterface $emojiChannel */
        $emojiChannel = $message->client->channels->get($this->channelId);
        $message->guild
            ->createEmoji($attachment->getUrl(), $attachment->getName())
            ->done(
                function (Emoji $emoji) use ($message, $io, $emojiChannel) {
                    $message->channel->send(Util::emojiToString($emoji))->done(
                        function (Message $emojiPost) use ($message, $emoji, $io, $emojiChannel) {
                            $emojiPost->react(Util::emojiToString($emoji));
                            $message->delete();
                            $emoji->delete();
                            $io->success(sprintf('Emoji %s nominated', $emoji->name));
                            /*
                            $emojiChannel->fetchMessages(['limit' => 100])
                                ->done(\Closure::fromCallable([$this, 'countMessages']));
                            */
                        }
                    );
                }
            );
    }

    /**
     * @param SymfonyStyle $io
     * @param Message $message
     * @param string $error
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

    /**
     * @param Collection $messages
     */
    private function countMessages(Collection $messages): void
    {
        return;

        $io = $this->event->getIo();
        $count = $messages->count();
        $message = $this->event->getMessage();

        /** @var TextChannel $emojiChannel */
        $emojiChannel = $message->client->channels->get($this->channelId);

        if ($count < 100) {
            // Send a lock message and unlock channel + delete it when cooldown is done
            $io->writeln(sprintf('Not closing yet, %s nominations', $count));
            $emojiChannel->send(sprintf('Volgende nominatie in %s seconden.', self::COOLDOWN))
                ->done(
                    function (Message $lockMessage) use ($emojiChannel) {
                        $lockMessage->client->getLoop()->addTimer(
                            self::COOLDOWN,
                            function () use ($emojiChannel, $lockMessage) {
                                $emojiChannel->overwritePermissions(
                                    $this->roleId,
                                    Channel::ROLE_SEND_MESSAGES,
                                    0,
                                    'emoji cooldown finished'
                                );
                                $lockMessage->delete();
                            }
                        );
                    }
                );


            return;
        }
        $emojiChannel->send('Laat het stemmen beginnen');
        $emojiChannel->overwritePermissions(
            $this->roleId,
            0,
            Channel::ROLE_SEND_MESSAGES,
            'Closed nominations'
        );
        $io->success('Closed nominations');
    }
}
