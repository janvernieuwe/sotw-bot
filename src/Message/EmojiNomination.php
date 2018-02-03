<?php

namespace App\Message;

use Symfony\Component\Validator\Constraints as Assert;

class EmojiNomination extends Message
{

    /**
     * @var array
     */
    private $imageSize;

    /**
     * @return bool
     */
    public function isGuildNomination(): bool
    {
        return (bool)\count($this->message['embeds']);
    }

    /**
     * @return bool
     */
    public function isUserNomination(): bool
    {
        return (bool)\count($this->message['attachments']);
    }

    /**
     * @Assert\Choice(choices={"png"}, message="Invalid image type, only PNG allowed")
     * @return string
     */
    public function getFileExtension(): string
    {
        $info = pathinfo($this->getUrl());

        return $info['extension'];
    }

    /**
     * @return array
     */
    private function getImageSize(): array
    {
        if ($this->imageSize === null) {
            $this->imageSize = getimagesize($this->getUrl());
        }
        if (!\is_array($this->imageSize)) {
            throw new \InvalidArgumentException('Invalid image size');
        }

        return $this->imageSize;
    }

    /**
     * @Assert\EqualTo(value=128, message="The image should have a height of 128 px")
     * @return int
     */
    public function getImageHeight(): int
    {
        return $this->getImageSize()[1];
    }

    /**
     * @Assert\EqualTo(value=128, message="The image should have a width of 128 px")
     * @return int
     */
    public function getImageWidth(): int
    {
        return $this->getImageSize()[0];
    }

    /**
     * @Assert\Type(type="string", message="Missing image url")
     * @Assert\NotBlank(message="Empty image url")
     * @return string
     */
    public function getUrl(): string
    {
        if ($this->isUserNomination()) {
            return $this->message['attachments'][0]['url'];
        }
        if ($this->isGuildNomination()) {
            return $this->message['embeds'][0]['image']['url'];
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isContender(): bool
    {
        return $this->isGuildNomination() || $this->isUserNomination();
    }

    /**
     * @Assert\Type(type="string", message="Invalid name")
     * @Assert\NotBlank(message="Empty name")
     * @return string
     */
    public function getName(): string
    {
        if ($this->isUserNomination()) {
            $info = pathinfo($this->getUrl());

            return $info['filename'];
        }

        if ($this->isGuildNomination()) {
            return $this->message['content'];
        }

        return '';
    }
}
