<?php

namespace spec\App\Channel;

use App\Channel\SongOfTheWeek;
use App\Message\SotwNomination;
use GuzzleHttp\Command\Result;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use RestCord\DiscordClient;
use RestCord\Interfaces\Channel;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SongOfTheWeekSpec
 * @package spec\App\Channel
 * @mixin SongOfTheWeek
 */
class SongOfTheWeekSpec extends ObjectBehavior
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    function let(DiscordClient $discord, ValidatorInterface $validator, Channel $channel)
    {
        $this->validator = $validator;
        $this->channel = $channel;
        $channel->getChannelMessages(Argument::any())->willReturn(new Result([]));
        $discord->channel = $channel;
        $this->beConstructedWith($discord, 1, $validator, 2);
        $this->setTest(true);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(SongOfTheWeek::class);
    }

    function it_detects_open_nominations_message()
    {
        $message = $this->createOpenNominationsMessage();
        $this->isOpenNominationsMessage($message)->shouldBe(true);
    }

    function it_retrieves_messages()
    {
        $this->getLastNominations()->shouldBeArray();
    }

    function it_announces_winners(
        DiscordClient $discord,
        ValidatorInterface $validator,
        Channel $channel,
        SotwNomination $nomination
    ) {
        $this->channel->createMessage(Argument::type('array'))->shouldBeCalled();
        $nomination->getArtist()->willReturn('artist');
        $nomination->getTitle()->willReturn('title');
        $nomination->getAnime()->willReturn('anime');
        $nomination->getAuthorId()->willReturn(1234);
        $nomination->hasReaction('🥇')->willReturn(false);
        $nomination->getMessageId()->willReturn(42);
        $this->announceWinner($nomination);
    }

    function it_creates_an_open_nominations_message()
    {
        $message = <<<MESSAGE
:musical_note: :musical_note: Bij deze zijn de nominaties voor week %s geopend! :musical_note: :musical_note:

Nomineer volgens onderstaande template (kopieer en plak deze, en zet er dan de gegevens in):
```
artist: 
title: 
anime:  
url: 
```
MESSAGE;

        $this->createOpenNominationsMessage()->shouldBe(sprintf($message, date('W') + 1));
    }

    public function it_adds_reactions(SotwNomination $nomination)
    {
        $nomination->hasReaction('')->shouldBeCalled();
        $nomination->getMessageId()->willReturn(1);
        $this->channel->createReaction(Argument::any())->shouldBeCalled();
        $this->addReaction($nomination, '');
    }

    public function it_removes_reactions(SotwNomination $nomination)
    {
        $nomination->hasReaction('')->willReturn(true);
        $nomination->hasReaction('')->shouldBeCalled();
        $nomination->getMessageId()->willReturn(1);
        $this->channel->deleteOwnReaction(Argument::any())->shouldBeCalled();
        $this->removeReaction($nomination, '');
    }

    public function it_validates_nominees(SotwNomination $nomination, ConstraintViolationList $list)
    {
        $this->validator->validate(Argument::any())->willReturn($list);
        $this->validator->validate(Argument::any())->shouldBeCalled();
        $this->validateNominees([$nomination]);
    }

    public function is_validates_a_nominee(SotwNomination $nomination, ConstraintViolationList $list)
    {
        $this->validator->validate(Argument::any())->willReturn($list);
        $this->validator->validate(Argument::any())->shouldBeCalled();
        $this->validate($nomination);
    }

    public function it_has_a_short_is_valid(SotwNomination $nomination, ConstraintViolationList $list)
    {
        $this->validator->validate(Argument::any())->willReturn($list);
        $this->isValid($nomination)->shouldReturn(true);
    }

    public function it_adds_medals(
        SotwNomination $first,
        SotwNomination $secondA,
        SotwNomination $secondB,
        SotwNomination $thirdA,
        SotwNomination $thirdB,
        SotwNomination $filler
    ) {
        $first->getVotes()->willReturn(5);
        $first->getMessageId()->willReturn(1);
        $first->hasReaction(Argument::any())->willReturn(false);
        $secondA->getVotes()->willReturn(4);
        $secondA->getMessageId()->willReturn(2);
        $secondA->hasReaction(Argument::any())->willReturn(false);
        $secondB->getVotes()->willReturn(4);
        $secondB->getMessageId()->willReturn(3);
        $secondB->hasReaction(Argument::any())->willReturn(false);
        $thirdA->getVotes()->willReturn(3);
        $thirdA->getMessageId()->willReturn(4);
        $thirdA->hasReaction(Argument::any())->willReturn(false);
        $thirdB->getVotes()->willReturn(3);
        $thirdB->getMessageId()->willReturn(5);
        $thirdB->hasReaction(Argument::any())->willReturn(false);
        $filler->getMessageId()->willReturn(6);
        $filler->getVotes()->willReturn(1);
        $filler->hasReaction(Argument::any())->willReturn(false);

        $nominations = [
            $first,
            $secondA,
            $secondB,
            $thirdA,
            $thirdB,
            $filler,
            $filler,
            $filler,
            $filler,
            $filler,
        ];

        $this->channel->createReaction(
            ["channel.id" => 1, "message.id" => 1, "emoji" => SongOfTheWeek::EMOJI_FIRST_PLACE]
        )->shouldBeCalled();
        $this->channel->createReaction(
            ["channel.id" => 1, "message.id" => 2, "emoji" => SongOfTheWeek::EMOJI_SECOND_PLACE]
        )->shouldBeCalled();
        $this->channel->createReaction(
            ["channel.id" => 1, "message.id" => 3, "emoji" => SongOfTheWeek::EMOJI_SECOND_PLACE]
        )->shouldBeCalled();
        $this->channel->createReaction(
            ["channel.id" => 1, "message.id" => 4, "emoji" => SongOfTheWeek::EMOJI_THIRD_PLACE]
        )->shouldBeCalled();
        $this->channel->createReaction(
            ["channel.id" => 1, "message.id" => 5, "emoji" => SongOfTheWeek::EMOJI_THIRD_PLACE]
        )->shouldBeCalled();

        $this->addMedals($nominations);

    }
}
