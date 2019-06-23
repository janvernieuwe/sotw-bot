<?php

namespace App\Message;

use CharlotteDunois\Yasmin\Models\Message as YasminMessage;
use CharlotteDunois\Yasmin\Models\MessageAttachment;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class EmojiAttachment
 *
 * @package App\Message
 */
class YasminEmojiNominationAttachment
{
    /**
     * @var MessageAttachment
     */
    private $attachment;

    /**
     * @var string
     */
    private $name;

    /**
     * @var YasminMessage
     */
    private $message;

    /**
     * EmojiAttachment constructor.
     *
     * @param MessageAttachment $attachment
     * @param YasminMessage     $message
     */
    public function __construct(MessageAttachment $attachment, YasminMessage $message)
    {
        $this->attachment = $attachment;
        $this->name = $message->content;
        $this->message = $message;
    }

    /**
     * @Assert\IsTrue(message="Enkel afbeeldingen")
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->attachment->width !== null;
    }

    /**
     * @Assert\LessThanOrEqual(value="256000", message="Bestand is the groot (Max 256kb)")
     * @return int
     */
    public function getSize(): int
    {
        return $this->attachment->size;
    }

    /**
     * @Assert\IsTrue(message="Alleen gifs")
     * @return bool
     */
    public function isGif(): bool
    {
        return preg_match('/\.gif$/', $this->attachment->filename);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->attachment->url;
    }

    /**
     * @Assert\NotBlank(message="Je bericht is de emoji naam")
     * @Assert\Length(min="2", max="32", minMessage="Emoji naam moet minstens 2 characters lang zijn",
     *     maxMessage="Emoji mag maximum 32 characters lang zijn")
     * @return string
     */
    public function getName(): string
    {
        return preg_replace('/\W/', '', $this->name);
    }

    /**
     * @Assert\IsFalse(message="Er is al een custom emoji met deze naam")
     * @return bool
     */
    public function isConflicting(): bool
    {
        return $this->message->guild->emojis->keyBy('name')->get($this->name) !== null;
    }
}
