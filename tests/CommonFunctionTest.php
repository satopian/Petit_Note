<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CommonFunctionTest extends TestCase
{
    /**
     * @covers t
     */
    public function testT(): void
    {
        $actual = t("foo\tbar\tbaz");

        $this->assertEquals('foobarbaz', $actual);
    }

    /**
     * @covers s
     */
    public function testStripTags(): void
    {
        $actual = s('<p>foo<font class="#ffffff">bar<span>baz</span></font></p>');

        $this->assertEquals('foobarbaz', $actual);
    }

    /**
     * @covers h
     */
    public function testHtmlspecialchars(): void
    {
        $actual = h('&t="100"&s=\'200\'');

        $this->assertEquals('&amp;t=&quot;100&quot;&amp;s=&#039;200&#039;', $actual);
    }

    /**
     * @dataProvider commentProvider
     * @covers com
     * @covers auto_link
     * @covers md_link
     */
    public function testComment(bool $useAutolink, string $text, string $expected): void
    {
        global $use_autolink;
        $use_autolink = $useAutolink;

        $actual = com($text);

        $this->assertEquals($expected, $actual);
    }

    public function commentProvider(): array
    {
        return [
            [true, '', ''],
            [false, '', ''],
            [
                false,
                "foo\nbar\nbaz",
                "foo<br>\nbar<br>\nbaz"
            ],
            [
                true,
                "foo\nbar\nbaz",
                "foo<br>\nbar<br>\nbaz"
            ],
            [
                // markdown
                false,
                "[foo](https://foo.com)\nbar\nbaz",
                "[foo](https://foo.com)<br>\nbar<br>\nbaz"
            ],
            [
                // markdown
                true,
                "[foo](https://foo.com)\nbar\nbaz",
                "<a href=\"https://foo.com\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">foo</a><br>\nbar<br>\nbaz"
            ],
            [
                // normal URL
                false,
                "<a href=\"https://foo.com\">foo</a>\nbar\nbaz",
                "<a href=\"https://foo.com\">foo</a><br>\nbar<br>\nbaz"
            ],
            [
                // normal URL
                true,
                "<a href=\"https://foo.com\">foo</a>\nbar\nbaz",
                "<a href=\"https://foo.com\">foo</a><br>\nbar<br>\nbaz"
            ],
            [
                // URL without <a tag
                false,
                "https://foo.com\nbar\nbaz",
                "https://foo.com<br>\nbar<br>\nbaz"
            ],
            [
                // URL has no <a tag
                true,
                "https://foo.com\nbar\nbaz",
                "<a href=\"https://foo.com\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">https://foo.com</a><br>\nbar<br>\nbaz"
            ],
            [
                // one of the two URLs has no <a tag
                true,
                "<a href=\"https://foo.com\">https://foo.com</a>\nhttps://bar.com\nbaz",
                "<a href=\"https://foo.com\">https://foo.com</a><br>\nhttps://bar.com<br>\nbaz"
            ],
        ];
    }
}
