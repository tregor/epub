<?php

namespace tregor\epub;

use Exception;
use SimpleXMLElement;
use XMLWriter;
use ZipArchive;


class EPubBook
{
    protected string $title;
    protected string $author;
    protected string $language;
    protected array $chapters = [];

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function getChapters(): array
    {
        return $this->chapters;
    }

    public function addChapter(string $title, string $content, int $order): void
    {
        $chapter = new EPubChapter($title, $content, $order);
        $this->chapters[] = $chapter;
    }

    public function removeChapter(int $order): void
    {
        foreach ($this->chapters as $key => $chapter) {
            if ($chapter->getOrder() === $order) {
                unset($this->chapters[$key]);
                break;
            }
        }
    }

    public function addChapterAfter(int $order, string $title, string $content, int $newOrder): void
    {
        $newChapter = new EPubChapter($title, $content, $newOrder);

        foreach ($this->chapters as $key => $chapter) {
            if ($chapter->getOrder() === $order) {
                array_splice($this->chapters, $key + 1, 0, [$newChapter]);
                break;
            }
        }
    }

    public function getChapterContent(int $order): ?string
    {
        foreach ($this->chapters as $chapter) {
            if ($chapter->getOrder() === $order) {
                return $chapter->getContent();
            }
        }

        return null;
    }

    public function setChapterContent(int $order, string $content): void
    {
        foreach ($this->chapters as $chapter) {
            if ($chapter->getOrder() === $order) {
                $chapter->setContent($content);
                break;
            }
        }
    }

    public function export(string $filename): string
    {
        $zip = new ZipArchive();

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            exit("Cannot open <$filename>\n");
        }

        $zip->addFromString("mimetype", "application/epub+zip");

        $zip->addEmptyDir("META-INF");
        $zip->addFromString("META-INF/container.xml", $this->getContainerXml());

        $zip->addEmptyDir("OPS");
        $zip->addEmptyDir("OPS/css");
        $zip->addFromString("OPS/css/style.css", $this->getCss());

        foreach ($this->chapters as $chapter) {
            $zip->addFromString("OPS/{$chapter->getTitle()}.xml", $this->getChapterXml($chapter));
        }

        $zip->addEmptyDir("OPS/images");

        $zip->addFromString("OPS/content.opf", $this->getContentOpf());
        $zip->addFromString("OPS/toc.ncx", $this->getTocNcx());

        $zip->close();

        return $filename;
    }

    public static function open(string $filename): ?EPubBook
    {
        $zip = new ZipArchive();

        if ($zip->open($filename) !== TRUE) {
            return null;
        }

        $book = new EPubBook('');
        $book->parseContentOpf($zip);
        $book->parseChapters($zip);

        $zip->close();

        return $book;
    }

    /**
     * @throws Exception
     */
    protected function parseContentOpf(ZipArchive $zip)
    {
        $contentOpf = $zip->getFromName('OPS/content.opf');
        $xml = new SimpleXMLElement($contentOpf);

        $metadata = $xml->metadata;
        $this->title = (string)$metadata->xpath('//dc:title')[0];
        $this->author = (string)$metadata->xpath('//dc:creator')[0];
        $this->language = (string)$metadata->xpath('//dc:language')[0];
    }

    /**
     * @throws Exception
     */
    protected function parseChapters(ZipArchive $zip): void
    {
        $chapterFiles = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);

            if (strpos($fileName, 'OPS/') === 0 && strpos($fileName, '.xml') !== false) {
                $chapterFiles[] = $fileName;
            }
        }

        foreach ($chapterFiles as $chapterFile) {
            $chapterContent = $zip->getFromName($chapterFile);
            $xml = new SimpleXMLElement($chapterContent);

            $chapterTitle = (string)$xml->xpath('//h3[@class="subtitle"]')[0];
            $chapterContent = (string)$xml->xpath('//p[@class="ps1"]')[0];
            $chapterOrder = (int)preg_replace('/\D/', '', $xml->xpath('//h2[@class="bordered-title"]')[0]);

            $this->chapters[] = new EPubChapter($chapterTitle, $chapterContent, $chapterOrder);
        }
    }

    protected function getContainerXml(): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('container');
        $xml->writeAttribute('version', '1.0');
        $xml->writeAttribute('xmlns', 'urn:oasis:names:tc:opendocument:xmlns:container');
        $xml->startElement('rootfiles');
        $xml->startElement('rootfile');
        $xml->writeAttribute('full-path', 'OPS/content.opf');
        $xml->writeAttribute('media-type', 'application/oebps-package+xml');
        $xml->endDocument();

        return $xml->outputMemory();
    }

    protected function getCss(): string
    {
        return <<<CSS
p{margin:0;text-indent:0}p + p{text-indent:2.00rem}h1 + p,h2 + p,h3 + p,h4 + p,h5 + p,h6 + p{text-indent:0}.separator + p{text-indent:0}.separator + div > p:first-child{text-indent:0}.br + p{text-indent:0}.br + div > p:first-child{text-indent:0}.attribution{margin:0;text-indent:0;text-align:right;font-size:1.00rem}blockquote p + p{text-indent:1.25rem}blockquote p{margin:0;text-indent:0}blockquote{margin:0}.body{margin:0;text-indent:2.00rem;font-size:1.00rem}.bordered-title{margin:0 0 1.11rem;text-indent:0;text-align:center;font-size:1.33rem;font-weight:normal}caption{margin:0;text-indent:0;text-align:center;font-size:0.83rem;caption-side:bottom}.caption{margin:0;text-indent:0;text-align:center;font-size:0.83rem}.centered-text{margin:0;text-indent:0;line-height:1.1em;text-align:center}.chapter-number{margin:0 0 2.22rem;text-indent:0;text-align:center;font-size:1.67rem;font-weight:normal}code{font-weight:normal;font-style:normal;text-decoration:none}.emphasis{font-size:1.00rem;font-weight:normal;font-style:italic}figcaption{margin:0;text-indent:0;text-align:center;font-size:0.83rem}.footnotes{margin:0;text-indent:0;font-size:1.00rem}.heading-1{margin:0 0 1.78rem;text-indent:0;text-align:center;font-size:1.33rem;font-weight:normal}.heading-2{margin:0.89rem 0;text-indent:0;text-align:center;font-size:1.08rem;font-weight:normal}.page-title{margin:0 0 1.33rem;text-indent:0;text-align:center;font-size:1.17rem}pre > code{white-space:pre-wrap;-webkit-hyphens:none;hyphens:none}.raw-html{}.raw-html-block{}.section-number{margin:0 0 1.78rem;text-indent:0;text-align:center;font-size:1.33rem;font-weight:normal}.sub-heading{margin:0.89rem 0;text-indent:0;text-align:center;font-size:1.08rem;font-weight:normal}.subtitle{margin:0 0 1.78rem;text-indent:0;text-align:center;font-size:1.33rem;font-weight:normal;font-style:italic}.title{margin:0 0 1.11rem;text-indent:0;text-align:center;font-size:1.33rem;font-weight:normal}.verse{margin:0;text-indent:0;line-height:1.1em;text-align:center;font-size:1.00rem}.ps1{margin-left:0;text-indent:1.50rem}a.fn-marker{font-size:0.65em;vertical-align:super;line-height:1em;text-decoration:none}a.fn-label{text-decoration:none}.separator{}.part-number-page-padding{margin:0;font-size:1rem;line-height:6rem}.part-title-page-padding{margin:0;font-size:1rem;line-height:6rem}.chapter-heading-page-padding{margin:0;font-size:1rem;line-height:6rem}.chapter-title-page-padding{margin:0;font-size:1rem;line-height:6rem}.heading-page-padding{margin:0;font-size:1rem;line-height:6rem}.chapter-page-padding{margin:0;font-size:1rem;line-height:6rem}.titled-chapter-page-padding{margin:0;font-size:1rem;line-height:6rem}.titled-section-page-padding{margin:0;font-size:1rem;line-height:6rem}table,table *{border:none;padding:0;margin:0}table{margin:1em auto;border-spacing:0;border:solid #000;border-width:0 0 1pt 1pt}table caption{margin-top:0.25em;caption-side:bottom;text-align:center}td,th{padding:0.25em 0.35em;border:solid #000;border-width:1pt 1pt 0 0}td p{margin:0;text-indent:0}img{display:block;margin:1rem auto}img + figcaption{margin-top:-0.75rem}ol ol{list-style-type:lower-alpha}ol ol ol{list-style-type:lower-roman}ol ol ol ol{list-style-type:decimal}ol ol ol ol ol{list-style-type:lower-alpha}ol ol ol ol ol ol{list-style-type:lower-roman}ol ol ol ol ol ol ol{list-style-type:decimal}ul ul{list-style:none;display:block;text-indent:-0.6em}ul ul li:before{content:'\2043\00A0'}.small-caps{font-variant:small-caps}nav#toc ol{list-style:none;line-height:1.5em;margin-top:0.5rem;margin-bottom:0.5rem}nav#toc ol li:before{content:none}.bordered-title{margin-left:10%;margin-right:10%;border-top:1px solid black;padding-top:12px;border-bottom:1px solid black;padding-bottom:12px}.bordered-title + .subtitle{padding-top:16px}blockquote{margin-left:2rem;margin-right:2rem}.attribution{margin-left:2rem;margin-right:2rem}blockquote + .attribution{margin-top:-0.5rem}figure{page-break-inside:avoid;text-align:center}.titled-chapter{margin:0;text-indent:0;font-size:0.92rem;font-weight:normal}
CSS;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    protected function getChapterXml(EPubChapter $chapter): string
    {
        $content_html = <<<HTML
<h2 id="doc2" class="titled-chapter-page-padding"><br /></h2>
<h3 class="subtitle"><em><span style="font-style:italic;">{$chapter->getTitle()}</span></em></h3>
<p class="ps1">{$chapter->getContent()}</p>
<p class="br"><br /></p>
HTML;

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('html');
        $xml->writeAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
        $xml->startElement('head');
        $xml->startElement('link');
        $xml->writeAttribute('href', 'css/style.css');
        $xml->writeAttribute('type', 'text/css');
        $xml->writeAttribute('rel', 'stylesheet');
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('body');
        $xml->writeRaw($content_html);
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    protected function getContentOpf(): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('package');
        $xml->writeAttribute('xmlns', 'http://www.idpf.org/2007/opf');
        $xml->writeAttribute('unique-identifier', 'BookId');
        $xml->writeAttribute('version', '2.0');
        $xml->startElement('metadata');
        $xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $xml->writeElement('dc:title', $this->getTitle());
        $xml->writeElement('dc:creator', $this->getAuthor());
        $xml->writeElement('dc:language', $this->getLanguage());
        $xml->endElement(); // metadata
        $xml->startElement('manifest');

        foreach ($this->chapters as $chapter) {
            $xml->startElement('item');
            $xml->writeAttribute('id', $chapter->getTitle());
            $xml->writeAttribute('href', "{$chapter->getTitle()}.xml");
            $xml->writeAttribute('media-type', 'application/xhtml+xml');
            $xml->endElement(); // item
        }
        $xml->endElement(); // manifest
        $xml->startElement('spine');

        foreach ($this->chapters as $chapter) {
            $xml->startElement('itemref');
            $xml->writeAttribute('idref', $chapter->getTitle());
            $xml->endElement(); // itemref
        }
        $xml->endElement(); // spine
        $xml->endElement(); // package
        $xml->endDocument();

        return $xml->outputMemory();
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    protected function getTocNcx()
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('ncx');
        $xml->writeAttribute('xmlns', 'http://www.daisy.org/z3986/2005/ncx/');
        $xml->writeAttribute('version', '2005-1');
        $xml->startElement('docTitle');
        $xml->writeElement('text', $this->getTitle());
        $xml->endElement(); // docTitle
        $xml->startElement('navMap');

        foreach ($this->chapters as $chapter) {
            $xml->startElement('navPoint');
            $xml->writeAttribute('id', $chapter->getTitle());
            $xml->writeAttribute('playOrder', $chapter->getOrder());
            $xml->startElement('navLabel');
            $xml->writeElement('text', $chapter->getTitle());
            $xml->endElement(); // navLabel
            $xml->startElement('content');
            $xml->writeAttribute('src', "{$chapter->getTitle()}.xml");
            $xml->endElement(); // content
            $xml->endElement(); // navPoint
        }
        $xml->endElement(); // navMap
        $xml->endElement(); // ncx
        $xml->endDocument();

        return $xml->outputMemory();
    }
}
