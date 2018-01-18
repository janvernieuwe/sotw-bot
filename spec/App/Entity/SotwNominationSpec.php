<?php

namespace spec\App\Entity;

use App\Entity\SotwNomination;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SotwNominationSpec extends ObjectBehavior
{
    function let()
    {
        $message = [
            'content' => "artist: artist\ntitle: title\nanime: anime\nurl: https://www.youtube.com/watch?v=3cW8P2qUJgk",
            'author'  => ['id' => 12, 'username' => 'TestUser'],
            'id'      => 456,
        ];
        $this->beConstructedThrough('fromMessage', [$message]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(SotwNomination::class);
    }

    function it_should_detect_contenders()
    {
        $this::isContenter('blablabla')->shouldReturn(false);
        $this::isContenter('https://www.youtube.com')->shouldReturn(true);
    }

    function it_should_get_votes()
    {
        $this->getVotes()->shouldReturn(0);
    }

    function it_gets_the_youtube_code()
    {
        $this->getYoutubeCode()->shouldReturn('3cW8P2qUJgk');
    }

    function it_gets_the_youtube_link()
    {
        $this->getYoutube()->shouldReturn('https://www.youtube.com/watch?v=3cW8P2qUJgk');
    }

    function it_should_be_castable_to_string()
    {
        $this->__toString()->shouldReturn('artist - title (anime) door TestUser');
    }

    function it_should_get_the_artist()
    {
        $this->getArtist()->shouldReturn('artist');
    }

    function it_should_get_the_title()
    {
        $this->getTitle()->shouldReturn('title');
    }

    function it_should_get_the_anime()
    {
        $this->getAnime()->shouldReturn('anime');
    }

    function it_should_get_the_author()
    {
        $this->getAuthor()->shouldReturn('TestUser');
    }

    function it_should_get_the_author_id()
    {
        $this->getAuthorId()->shouldBe(12);
    }

    function it_should_get_the_message_id()
    {
        $this->getMessageId()->shouldBe(456);
    }

    function it_should_know_emoji()
    {
        $this->hasReaction('')->shouldBe(false);
    }
}
