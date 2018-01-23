<?php

namespace spec\App\Discord;

use App\Discord\SongOfTheWeek;
use App\Entity\SotwNomination;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use RestCord\DiscordClient;
use RestCord\Interfaces\Channel;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $channel->getChannelMessages(Argument::any())->willReturn([]);
        $discord->channel = $channel;
        $this->beConstructedWith($discord, $validator, 1, 2);
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
        $channel->createMessage(Argument::type('array'))->shouldBeCalled();
        $channel->createReaction(Argument::type('array'))->shouldBeCalled();
        $discord->channel = $channel;
        $this->beConstructedWith($discord, $validator, 1, 2);
        $nomination->getArtist()->willReturn('artist');
        $nomination->getTitle()->willReturn('artist');
        $nomination->getAnime()->willReturn('artist');
        $nomination->getAuthorId()->willReturn(1);
        $nomination->hasReaction('🥇')->willReturn(false);
        $nomination->getMessageId()->willReturn(42);
        $this->announceWinner($nomination);
    }

    function it_creates_a_winning_message(SotwNomination $nomination)
    {
        $nomination->getArtist()->willReturn('artist');
        $nomination->getTitle()->willReturn('title');
        $nomination->getAnime()->willReturn('anime');
        $nomination->getAuthorId()->willReturn(1234);
        $this->createWinningMessage($nomination)->shouldMatch('/artist/');
        $this->createWinningMessage($nomination)->shouldMatch('/title/');
        $this->createWinningMessage($nomination)->shouldMatch('/anime/');
        $this->createWinningMessage($nomination)->shouldMatch('/1234/');
    }

    function it_creates_an_open_nominations_message()
    {
        $message = <<<MESSAGE
:musical_note: :musical_note: Bij deze zijn de nominaties voor week %s geopend! :musical_note: :musical_note:

Nomineer volgens onderstaande template (copieer en plak deze, en zet er dan de gegevens in):
```
artist: 
title: 
anime:  
url: 
```
MESSAGE;

        $this->createOpenNominationsMessage()->shouldBe(sprintf($message, date('W') + 1));
    }

    function it_creates_close_nominations_message()
    {
        $this->createCloseNominationsMessage()->shouldBe('Laat het stemmen beginnen! :checkered_flag:');
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
}
