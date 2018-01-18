<?php

namespace spec\App\Formatter;

use App\Formatter\BBCodeFormatter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BBCodeFormatterSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith([]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(BBCodeFormatter::class);
    }
}
