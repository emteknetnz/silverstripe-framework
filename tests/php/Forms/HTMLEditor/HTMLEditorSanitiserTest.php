<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorSanitiser;
use SilverStripe\View\Parsers\HTMLValue;

class HTMLEditorSanitiserTest extends FunctionalTest
{

    public function testSanitisation()
    {
        // ensure configurable value matches what's used in this test
        $config = HTMLEditorConfig::get('htmleditorsanitisertest');
        $sanitiser = Injector::inst()->get(HTMLEditorSanitiser::class, false, [$config]);
        $defaultValue = $sanitiser->config('link_noopener_value');
        $sanitiser->config('link_noopener_value')->set('link_noopener_value', 'noopener, noreferrer');

        $tests = array(
            array(
                'p,strong',
                '<p>Leave Alone</p><div>Strip parent<strong>But keep children</strong> in order</div>',
                '<p>Leave Alone</p>Strip parent<strong>But keep children</strong> in order',
                'Non-whitelisted elements are stripped, but children are kept'
            ),
            array(
                'p,strong',
                '<div>A <strong>B <div>Nested elements are still filtered</div> C</strong> D</div>',
                'A <strong>B Nested elements are still filtered C</strong> D',
                'Non-whitelisted elements are stripped even when children of non-whitelisted elements'
            ),
            array(
                'p',
                '<p>Keep</p><script>Strip <strong>including children</strong></script>',
                '<p>Keep</p>',
                'Non-whitelisted script elements are totally stripped, including any children'
            ),
            array(
                'p[id]',
                '<p id="keep" bad="strip">Test</p>',
                '<p id="keep">Test</p>',
                'Non-whitelisted attributes are stripped'
            ),
            array(
                'p[default1=default1|default2=default2|force1:force1|force2:force2]',
                '<p default1="specific1" force1="specific1">Test</p>',
                '<p default1="specific1" force1="force1" default2="default2" force2="force2">Test</p>',
                'Default attributes are set when not present in input, forced attributes are always set'
            ),
            array(
                'a[href|target|rel]',
                '<a href="/test" target="_blank">Test</a>',
                '<a href="/test" target="_blank" rel="noopener, noreferrer">Test</a>',
                'noopener rel attribute is added when target attribute is set'
            ),
            array(
                'a[href|target|rel]',
                '<a href="/test" target="_top">Test</a>',
                '<a href="/test" target="_top" rel="noopener, noreferrer">Test</a>',
                'noopener rel attribute is added when target is _top instead of _blank'
            ),
            array(
                'a[href|target|rel]',
                '<a href="/test" rel="noopener, noreferrer">Test</a>',
                '<a href="/test">Test</a>',
                'noopener rel attribute is removed when target is not set'
            )
        );

        $config = HTMLEditorConfig::get('htmleditorsanitisertest');

        foreach ($tests as $test) {
            list($validElements, $input, $output, $desc) = $test;

            $config->setOptions(array('valid_elements' => $validElements));
            $sanitiser = new HtmlEditorSanitiser($config);

            $htmlValue = HTMLValue::create($input);
            $sanitiser->sanitise($htmlValue);

            $this->assertEquals($output, $htmlValue->getContent(), $desc);
        }

        // reset configurable value
        $sanitiser->config('link_noopener_value')->set('link_noopener_value', $defaultValue);
    }
}
