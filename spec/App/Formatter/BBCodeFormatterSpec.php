<?php

namespace spec\App\Formatter;

use App\Message\SotwNomination;
use App\Formatter\BBCodeFormatter;
use PhpSpec\ObjectBehavior;

class BBCodeFormatterSpec extends ObjectBehavior
{
    function let(SotwNomination $nomination1, SotwNomination $nomination2, SotwNomination $nomination3)
    {
        $nomination1->getAuthorId()->willReturn(1);
        $nomination1->getAuthor()->willReturn('Author1');
        $nomination1->getTitle()->willReturn('title1');
        $nomination1->getArtist()->willReturn('artist1');
        $nomination1->getAnime()->willReturn('anime1');
        $nomination1->getMessageId()->willReturn('1');
        $nomination1->getYoutubeCode()->willReturn('AAAA');
        $nomination1->getYoutube()->willReturn('bla');
        $nomination1->getVotes()->willReturn(3);

        $nomination2->getAuthorId()->willReturn(2);
        $nomination2->getAuthor()->willReturn('Author2');
        $nomination2->getTitle()->willReturn('title2');
        $nomination2->getArtist()->willReturn('artist2');
        $nomination2->getAnime()->willReturn('anime2');
        $nomination2->getMessageId()->willReturn('2');
        $nomination2->getYoutubeCode()->willReturn('BBBB');
        $nomination2->getYoutube()->willReturn('Bla2');
        $nomination2->getVotes()->willReturn(1);

        $nomination3->getAuthorId()->willReturn(2);
        $nomination3->getAuthor()->willReturn('Author2');
        $nomination3->getTitle()->willReturn('title2');
        $nomination3->getArtist()->willReturn('artist2');
        $nomination3->getAnime()->willReturn('anime2');
        $nomination3->getMessageId()->willReturn('2');
        $nomination3->getYoutubeCode()->willReturn('BBBB');
        $nomination3->getYoutube()->willReturn('Bla2');
        $nomination3->getVotes()->willReturn(1);

        $this->beConstructedWith([$nomination1, $nomination2, $nomination3]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(BBCodeFormatter::class);
    }

    function it_formats_to_bb_code()
    {
        $message = '
Week %s: artist1 - title1 (anime1) door Author1 - bla
[spoiler]
[spoiler="3 Votes (Author1) : artist1 - title1 (anime1)"][yt]AAAA[/yt][/spoiler]
[spoiler="1 Votes (Author2) : artist2 - title2 (anime2)"][yt]BBBB[/yt][/spoiler]
[spoiler="1 Votes (Author2) : artist2 - title2 (anime2)"][yt]BBBB[/yt][/spoiler]
[/spoiler]
';
        $message = sprintf($message, date('W'));
        $this->createMessage()->shouldReturn($message);
    }
}
