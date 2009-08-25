<?php

if (isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER)) {
    print "This script must be run from the command line\n";
    exit();
}

define('INSTALLDIR', realpath(dirname(__FILE__) . '/..'));
define('LACONICA', true);

require_once INSTALLDIR . '/lib/common.php';

class URLDetectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     *
     */
    public function testProduction($content, $expected)
    {
        $rendered = common_render_text($content);
        $this->assertEquals($expected, $rendered);
    }

    static public function provider()
    {
        return array(
                     array('example',
                           'example'),
                     array('http://example',
                           '<a href="http://example/" rel="external">http://example</a>'),
                     array('http://example/',
                           '<a href="http://example/" rel="external">http://example/</a>'),
                     array('http://example/path',
                           '<a href="http://example/path" rel="external">http://example/path</a>'),
                     array('http://example.com',
                           '<a href="http://example.com/" rel="external">http://example.com</a>'),
                     array('https://example.com',
                           '<a href="https://example.com/" rel="external">https://example.com</a>'),
                     array('ftp://example.com',
                           '<a href="ftp://example.com/" rel="external">ftp://example.com</a>'),
                     array('ftps://example.com',
                           '<a href="ftps://example.com/" rel="external">ftps://example.com</a>'),
                     array('http://user@example.com',
                           '<a href="http://user@example.com/" rel="external">http://user@example.com</a>'),
                     array('http://user:pass@example.com',
                           '<a href="http://user:pass@example.com/" rel="external">http://user:pass@example.com</a>'),
                     array('http://example.com:8080',
                           '<a href="http://example.com:8080/" rel="external">http://example.com:8080</a>'),
                     array('http://www.example.com',
                           '<a href="http://www.example.com/" rel="external">http://www.example.com</a>'),
                     array('http://example.com/',
                           '<a href="http://example.com/" rel="external">http://example.com/</a>'),
                     array('http://example.com/path',
                           '<a href="http://example.com/path" rel="external">http://example.com/path</a>'),
                     array('http://example.com/path.html',
                           '<a href="http://example.com/path.html" rel="external">http://example.com/path.html</a>'),
                     array('http://example.com/path.html#fragment',
                           '<a href="http://example.com/path.html#fragment" rel="external">http://example.com/path.html#fragment</a>'),
                     array('http://example.com/path.php?foo=bar&bar=foo',
                           '<a href="http://example.com/path.php?foo=bar&amp;bar=foo" rel="external">http://example.com/path.php?foo=bar&amp;bar=foo</a>'),
                     array('http://müllärör.de',
                           '<a href="http://müllärör.de" rel="external">http://müllärör.de</a>'),
                     array('http://ﺱﺲﺷ.com',
                           '<a href="http://ﺱﺲﺷ.com" rel="external">http://ﺱﺲﺷ.com</a>'),
                     array('http://сделаткартинки.com',
                           '<a href="http://сделаткартинки.com" rel="external">http://сделаткартинки.com</a>'),
                     array('http://tūdaliņ.lv',
                           '<a href="http://tūdaliņ.lv" rel="external">http://tūdaliņ.lv</a>'),
                     array('http://brændendekærlighed.com',
                           '<a href="http://brændendekærlighed.com" rel="external">http://brændendekærlighed.com</a>'),
                     array('http://あーるいん.com',
                           '<a href="http://あーるいん.com" rel="external">http://あーるいん.com</a>'),
                     array('http://예비교사.com',
                           '<a href="http://예비교사.com" rel="external">http://예비교사.com</a>'),
                     array('http://example.com.',
                           '<a href="http://example.com" rel="external">http://example.com</a>.'),
                     array('http://example.com?',
                           '<a href="http://example.com" rel="external">http://example.com</a>?'),
                     array('http://example.com!',
                           '<a href="http://example.com" rel="external">http://example.com</a>!'),
                     array('http://example.com,',
                           '<a href="http://example.com" rel="external">http://example.com</a>,'),
                     array('http://example.com;',
                           '<a href="http://example.com" rel="external">http://example.com</a>;'),
                     array('http://example.com:',
                           '<a href="http://example.com" rel="external">http://example.com</a>:'),
                     array('\'http://example.com\'',
                           '\'<a href="http://example.com" rel="external">http://example.com</a>\''),
                     array('"http://example.com"',
                           '"<a href="http://example.com" rel="external">http://example.com</a>"'),
                     array('http://example.com',
                           '<a href="http://example.com" rel="external">http://example.com</a>'),
                     array('(http://example.com)',
                           '(<a href="http://example.com" rel="external">http://example.com</a>)'),
                     array('[http://example.com]',
                           '[<a href="http://example.com" rel="external">http://example.com</a>]'),
                     array('<http://example.com>',
                           '<<a href="http://example.com" rel="external">http://example.com</a>>'),
                     array('http://example.com/path/(foo)/bar',
                           '<a href="http://example.com/path/(foo)/bar" rel="external">http://example.com/path/(foo)/bar</a>'),
                     array('http://example.com/path/[foo]/bar',
                           '<a href="http://example.com/path/[foo]/bar" rel="external">http://example.com/path/[foo]/bar</a>'),
                     array('http://example.com/path/foo/(bar)',
                           '<a href="http://example.com/path/foo/(bar)" rel="external">http://example.com/path/foo/(bar)</a>'),
                     array('http://example.com/path/foo/[bar]',
                           '<a href="http://example.com/path/foo/[bar]" rel="external">http://example.com/path/foo/[bar]</a>'),
                     array('Hey, check out my cool site http://example.com okay?',
                           'Hey, check out my cool site <a href="http://example.com" rel="external">http://example.com</a> okay?'),
                     array('What about parens (e.g. http://example.com/path/foo/(bar))?',
                           'What about parens (e.g. <a href="http://example.com/path/foo/(bar)" rel="external">http://example.com/path/foo/(bar)</a>)?'),
                     array('What about parens (e.g. http://example.com/path/foo/(bar)?',
                           'What about parens (e.g. <a href="http://example.com/path/foo/(bar)" rel="external">http://example.com/path/foo/(bar)</a>?'),
                     array('What about parens (e.g. http://example.com/path/foo/(bar).)?',
                           'What about parens (e.g. <a href="http://example.com/path/foo/(bar)" rel="external">http://example.com/path/foo/(bar)</a>.)?'),
                     array('What about parens (e.g. http://example.com/path/(foo,bar)?',
                           'What about parens (e.g. <a href="http://example.com/path/(foo,bar)" rel="external">http://example.com/path/(foo,bar)</a>?'),
                     array('Unbalanced too (e.g. http://example.com/path/((((foo)/bar)?',
                           'Unbalanced too (e.g. <a href="http://example.com/path/((((foo)/bar)" rel="external">http://example.com/path/((((foo)/bar)</a>?'),
                     array('Unbalanced too (e.g. http://example.com/path/(foo))))/bar)?',
                           'Unbalanced too (e.g. <a href="http://example.com/path/(foo))))/bar" rel="external">http://example.com/path/(foo))))/bar</a>)?'),
                     array('Unbalanced too (e.g. http://example.com/path/foo/((((bar)?',
                           'Unbalanced too (e.g. <a href="http://example.com/path/foo/((((bar)" rel="external">http://example.com/path/foo/((((bar)</a>?'),
                     array('Unbalanced too (e.g. http://example.com/path/foo/(bar))))?',
                           'Unbalanced too (e.g. <a href="http://example.com/path/foo/(bar)" rel="external">http://example.com/path/foo/(bar)</a>)))?'),
                     array('example.com',
                           '<a href="http://example.com" rel="external">example.com</a>'),
                     array('example.org',
                           '<a href="http://example.org" rel="external">example.org</a>'),
                     array('example.co.uk',
                           '<a href="http://example.co.uk" rel="external">example.co.uk</a>'),
                     array('www.example.co.uk',
                           '<a href="http://www.example.co.uk" rel="external">www.example.co.uk</a>'),
                     array('farm1.images.example.co.uk',
                           '<a href="http://farm1.images.example.co.uk" rel="external">farm1.images.example.co.uk</a>'),
                     array('example.museum',
                           '<a href="http://example.museum" rel="external">example.museum</a>'),
                     array('example.travel',
                           '<a href="http://example.travel" rel="external">example.travel</a>'),
                     array('example.com.',
                           '<a href="http://example.com" rel="external">example.com</a>.'),
                     array('example.com?',
                           '<a href="http://example.com" rel="external">example.com</a>?'),
                     array('example.com!',
                           '<a href="http://example.com" rel="external">example.com</a>!'),
                     array('example.com,',
                           '<a href="http://example.com" rel="external">example.com</a>,'),
                     array('example.com;',
                           '<a href="http://example.com" rel="external">example.com</a>;'),
                     array('example.com:',
                           '<a href="http://example.com" rel="external">example.com</a>:'),
                     array('\'example.com\'',
                           '\'<a href="http://example.com" rel="external">example.com</a>\''),
                     array('"example.com"',
                           '"<a href="http://example.com" rel="external">example.com</a>"'),
                     array('example.com',
                           '<a href="http://example.com" rel="external">example.com</a>'),
                     array('(example.com)',
                           '(<a href="http://example.com" rel="external">example.com</a>)'),
                     array('[example.com]',
                           '[<a href="http://example.com" rel="external">example.com</a>]'),
                     array('<example.com>',
                           '<<a href="http://example.com" rel="external">example.com</a>>'),
                     array('Hey, check out my cool site example.com okay?',
                           'Hey, check out my cool site <a href="http://example.com" rel="external">example.com</a> okay?'),
                     array('Hey, check out my cool site example.com.I made it.',
                           'Hey, check out my cool site <a href="http://example.com" rel="external">example.com</a>.I made it.'),
                     array('Hey, check out my cool site example.com.Funny thing...',
                           'Hey, check out my cool site <a href="http://example.com" rel="external">example.com</a>.Funny thing...'),
                     array('Hey, check out my cool site example.com.You will love it.',
                           'Hey, check out my cool site <a href="http://example.com" rel="external">example.com</a>.You will love it.'),
                     array('What about parens (e.g. example.com/path/foo/(bar))?',
                           'What about parens (e.g. <a href="http://example.com/path/foo/(bar)" rel="external">example.com/path/foo/(bar)</a>)?'),
                     array('What about parens (e.g. example.com/path/foo/(bar)?',
                           'What about parens (e.g. <a href="http://example.com/path/foo/(bar)" rel="external">example.com/path/foo/(bar)</a>?'),
                     array('What about parens (e.g. example.com/path/foo/(bar).)?',
                           'What about parens (e.g. <a href="http://example.com/path/foo/(bar)" rel="external">example.com/path/foo/(bar)</a>.)?'),
                     array('What about parens (e.g. example.com/path/(foo,bar)?',
                           'What about parens (e.g. <a href="http://example.com/path/(foo,bar)" rel="external">example.com/path/(foo,bar)</a>?'),
                     array('file.ext',
                           'file.ext'),
                     array('file.html',
                           'file.html'),
                     array('file.php',
                           'file.php')
                     );
    }
}

