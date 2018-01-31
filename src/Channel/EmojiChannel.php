<?php

namespace App\Channel;

use App\Message\EmojiNomination;

class EmojiChannel extends Channel
{
    /**
     * @param int $limit
     * @return EmojiNomination[]
     */
    public function getNominations($limit = 100): array
    {
        $messages = parent::getMessages($limit)->toArray();
        $messages = array_map(
            function ($msg) {
                return new EmojiNomination((array)$msg);
            },
            $messages
        );
        $messages = array_filter(
            $messages,
            function (EmojiNomination $e) {
                return $e->isContender();
            }
        );

        return $messages;
    }
}
