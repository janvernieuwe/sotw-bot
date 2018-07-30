<?php

namespace App\Message;

use App\Channel\Channel;
use App\Exception\InvalidChannelException;
use App\Util\Util;
use CharlotteDunois\Yasmin\Models\TextChannel;

/**
 * Class JoinableChannelMessage
 *
 * @package App\Message
 */
class SimpleJoinableChannelMessage
{
    public const TEXT_MESSAGE = '';

    /**
     * @var \CharlotteDunois\Yasmin\Models\Message
     */
    private $message;

    /**
     * JoinableChannelMessage constructor.
     *
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     */
    public function __construct(\CharlotteDunois\Yasmin\Models\Message $message)
    {
        $this->message = $message;
    }

    /**
     * @param \CharlotteDunois\Yasmin\Models\Message $message
     *
     * @return bool
     */
    public static function isJoinChannelMessage(\CharlotteDunois\Yasmin\Models\Message $message): bool
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $message = new self($message);

        return $message->getFieldValue('description') !== null;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getFieldValue(string $key)
    {
        return Channel::getFieldValue($this->message, $key);
    }

    /**
     * @return TextChannel
     * @throws InvalidChannelException
     */
    public function getChannelFromMessage(): TextChannel
    {
        $channel = $this->message->guild->channels->get($this->getChannelId());
        if ($channel === null) {
            throw new InvalidChannelException('Channel not found');
        }

        return $channel;
    }

    /**
     * @return int|null
     */
    public function getChannelId(): ?int
    {
        return Channel::getChannelId($this->message);
    }

    /**
     * @return TextChannel
     */
    public function getChannel(): TextChannel
    {
        return $this->message->guild->channels->get($this->getChannelId());
    }

    /**
     * Update the the channel message
     *
     * @param int $count
     * @param int $channelId
     */
    public function updateWatchers(int $channelId, int $count): void
    {
        $embed = static::generateRichChannelMessage($channelId, $count, $this->getChannelTopic());
        $this->message->edit(self::TEXT_MESSAGE, $embed);
    }

    /**
     * @param int    $channelId
     * @param int    $subsciberCount
     * @param string $message
     *
     * @return array
     */
    public static function generateRichChannelMessage(int $channelId, int $subsciberCount, string $message): array
    {
        return [
            'embed' => [
                'footer' => [
                    'text' => 'Druk op de reactions om te joinen / leaven',
                ],
                'fields' => [
                    [
                        'name'   => 'description',
                        'value'  => $message,
                        'inline' => false,
                    ],
                    [
                        'name'   => Channel::CHANNEL_KEY,
                        'value'  => Util::channelLink($channelId),
                        'inline' => true,
                    ],
                    [
                        'name'   => 'members',
                        'value'  => $subsciberCount,
                        'inline' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function getChannelTopic(): string
    {
        return $this->getChannel()->topic;
    }
}
