<?php

namespace NovaVideoField\Tests;

use NovaVideoField\Video;

class VideoFieldTest extends TestCase
{
    /** @test */
    public function returns_correct_component_name()
    {
        $htmlField = Video::make('Foo');

        $this->assertEquals('video-field', $htmlField->component());
    }
}
