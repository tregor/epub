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
    protected string $image_cover;
    protected string $date_timestamp;
    protected array $chapters = [];
    protected array $images = [];

    public function __construct(string $title)
    {
        $this->title = $title;
        $this->date_timestamp = time();
    }

    public static function open(string $filename): ?EPubBook
    {
        $zip = new ZipArchive();

        if ($zip->open($filename) !== true) {
            return null;
        }

        $book = new EPubBook(basename($filename));
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

    public static function importFromDOCX(string $filename): ?EPubBook
    {
        // Импорт из DOCX
        // Реализация опущена для краткости
        return null;
    }

    public function swapChapters(int $order1, int $order2): void
    {
        $chapter1 = null;
        $chapter2 = null;

        $this->chapters = array_map(function ($chapter) use ($order1, $order2, &$chapter1, &$chapter2) {
            if ($chapter->getOrder() === $order1) {
                $chapter1 = $chapter;
                return null;
            } elseif ($chapter->getOrder() === $order2) {
                $chapter2 = $chapter;
                return null;
            } else {
                return $chapter;
            }
        }, $this->chapters);

        if ($chapter1 && $chapter2) {
            $chapter1->setOrder($order2);
            $chapter2->setOrder($order1);

            $this->chapters[] = $chapter2;
            $this->chapters[] = $chapter1;

            usort($this->chapters, function ($a, $b) {
                return $a->getOrder() - $b->getOrder();
            });
        }
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

    public function addChapterAfter(int $order, string $title, string $content, int $newOrder): void
    {
        $newChapter = new EPubChapter($title, $content, $newOrder);

        $this->chapters = array_reduce($this->chapters, function ($result, $chapter) use ($order, $newChapter) {
            $result[] = $chapter;

            if ($chapter->getOrder() === $order) {
                $result[] = $newChapter;
            }

            return $result;
        }, []);
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

    public function exportToEPUB(string $filename): string
    {
        $zip = new ZipArchive();
        if (file_exists($filename)) {
            unlink($filename);
        }

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
            if ($chapter instanceof EPubChapter) {
                $zip->addFromString("OPS/{$chapter->getTitleMD5()}.xml", $this->getChapterXml($chapter));
            }
        }

        $zip->addEmptyDir("OPS/images");
        foreach ($this->images as $image => $content) {
            $zip->addFromString("OPS/images/{$image}", $content);
        }

        $zip->addFromString("OPS/content.opf", $this->getContentOpf());
        $zip->addFromString("OPS/toc.ncx", $this->getTocNcx());

        $zip->close();

        return $filename;
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

    protected function getChapterXml(EPubChapter $chapter): string
    {
        $content_html = <<<HTML
<h2 id="doc2" class="titled-chapter-page-padding"><br /></h2>
<h3 class="subtitle"><em><span style="font-style:italic;">{$chapter->getTitle()}</span></em></h3>
<p class="ps1">{$chapter->getContentHTML()}</p>
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
        $xml->writeAttribute('xmlns:opf', 'http://www.idpf.org/2007/opf');
        $xml->writeElement('dc:title', $this->getTitle());
        $xml->startElement('dc:identifier');
        $xml->writeAttribute('id', 'BookId');
        $xml->text(uniqid('urn:uuid:'));
        $xml->endElement();
        $xml->writeElement('dc:language', $this->getLanguage());
        $xml->startElement('dc:creator');
        $xml->writeAttribute('id', 'author');
        $xml->text($this->getAuthor());
        $xml->endElement();

        $xml->startElement('meta');
        $xml->writeAttribute('property', 'dcterms:modified');
        $xml->text(date('Y-m-dTH:i:sZ', $this->date_timestamp));
        $xml->endElement();
        $xml->writeElement('dc:date', date('Y-m-dTH:i:sZ', $this->date_timestamp));

        $xml->startElement('meta');
        $xml->writeAttribute('refines', '#author');
        $xml->writeAttribute('property', 'role');
        $xml->text('aut');
        $xml->endElement();
        $xml->startElement('meta');
        $xml->writeAttribute('refines', '#author');
        $xml->writeAttribute('property', 'file-as');
        $xml->text(explode(" ", $this->getAuthor())[1] . ', ' . explode(" ", $this->getAuthor())[0]);
        $xml->endElement();


        // Set cover image
        if (!empty($this->image_cover)) {
            $xml->startElement('meta');
            $xml->writeAttribute('name', 'cover');
            $xml->writeAttribute('content', $this->image_cover);
            $xml->endElement(); // meta
        }

        $xml->endElement(); // metadata
        $xml->startElement('manifest');
        $xml->startElement('item');
        $xml->writeAttribute('id', 'ncx');
        $xml->writeAttribute('href', 'toc.ncx');
        $xml->writeAttribute('media-type', 'application/x-dtbncx+xml');
        $xml->writeAttribute('fallback', 'contents');
        $xml->endElement(); // item

        foreach ($this->chapters as $chapter) {
            if ($chapter instanceof EPubChapter) {
                $xml->startElement('item');
                $xml->writeAttribute('id', $chapter->getTitleMD5());
                $xml->writeAttribute('href', "{$chapter->getTitleMD5()}.xml");
                $xml->writeAttribute('media-type', 'application/xhtml+xml');
                $xml->endElement(); // item
            }
        }

        // Add cover image to manifest
        if (!empty($this->image_cover)) {
            $xml->startElement('item');
            $xml->writeAttribute('id', 'cover-image');
            $xml->writeAttribute('href', $this->image_cover);
            $xml->writeAttribute('media-type', 'image/jpeg'); // Adjust the media type as needed
            $xml->endElement(); // item
        }

        $xml->endElement(); // manifest
        $xml->startElement('spine');

        foreach ($this->chapters as $chapter) {
            if ($chapter instanceof EPubChapter) {
                $xml->startElement('itemref');
                $xml->writeAttribute('idref', $chapter->getTitleMD5());
                $xml->endElement(); // itemref
            }
        }

        $xml->endElement(); // spine
        $xml->endElement(); // package
        $xml->endDocument();

        return $xml->outputMemory();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    protected function getTocNcx()
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('ncx');
        $xml->writeAttribute('xmlns', 'http://www.daisy.org/z3986/2005/ncx/');
        $xml->writeAttribute('version', '2005-1');

        $xml->startElement('head');
        $xml->startElement('meta');
        $xml->writeAttribute('name', 'dtb:uid');
        $xml->writeAttribute('content', 'urn:uuid:' . uniqid());
        $xml->endElement(); // meta
        $xml->startElement('meta');
        $xml->writeAttribute('name', 'dtb:depth');
        $xml->writeAttribute('content', '1');
        $xml->endElement(); // meta
        $xml->startElement('meta');
        $xml->writeAttribute('name', 'dtb:totalPageCount');
        $xml->writeAttribute('content', '0');
        $xml->endElement(); // meta
        $xml->startElement('meta');
        $xml->writeAttribute('name', 'dtb:maxPageNumber');
        $xml->writeAttribute('content', '0');
        $xml->endElement(); // meta
        $xml->endElement(); // head


        $xml->startElement('docTitle');
        $xml->writeElement('text', $this->getTitle());
        $xml->endElement(); // docTitle

        $xml->startElement('navMap');

        $navPointId = 1;
        $playOrder = 1;

        $this->writeNavPoint($xml, $navPointId, $playOrder, 'Contents', 'contents.xhtml');

        foreach ($this->chapters as $chapter) {
            if ($chapter instanceof EPubChapter) {
                $navPointId++;
                $playOrder++;

                $this->writeNavPoint(
                    $xml,
                    $navPointId,
                    $playOrder,
                    $chapter->getTitle(),
                    $chapter->getTitleMD5() . '.xhtml'
                );
            }
        }

        $xml->endElement(); // navMap
        $xml->endElement(); // ncx
        $xml->endDocument();

        return $xml->outputMemory();
    }

    protected function writeNavPoint(XMLWriter $xml, int $navPointId, int $playOrder, string $title, string $src)
    {
        $xml->startElement('navPoint');
        $xml->writeAttribute('id', "navPoint-{$navPointId}");
        $xml->writeAttribute('playOrder', $playOrder);

        $xml->startElement('navLabel');
        $xml->startElement('text');
        $xml->text($title);
        $xml->endElement(); // text
        $xml->endElement(); // navLabel

        $xml->startElement('content');
        $xml->writeAttribute('src', $src);
        $xml->endElement(); // content

        $xml->endElement(); // navPoint
    }

    public function exportToZIP(string $filename): string
    {
        $zip = new ZipArchive();

        if ($zip->open($filename, ZipArchive::CREATE) !== true) {
            exit("Cannot open <$filename>\n");
        }

        foreach ($this->chapters as $chapter) {
            if ($chapter instanceof EPubChapter) {
                $zip->addFromString("{$chapter->getTitle()}.txt", $chapter->getContent());
            }
        }

        foreach ($this->images as $image => $content) {
            $zip->addFromString("images/{$image}", $content);
        }

        $zip->close();

        return $filename;
    }

    public function renameChapter(int $order, string $newTitle): void
    {
        foreach ($this->chapters as $chapter) {
            if ($chapter->getOrder() === $order) {
                $chapter->setTitle($newTitle);
                break;
            }
        }
    }

    public function moveChapter(int $order, string $newFolder): void
    {
        foreach ($this->chapters as $chapter) {
            if ($chapter->getOrder() === $order) {
                $chapter->setFolder($newFolder);
                break;
            }
        }
    }

    public function addImage(string $filename, string $content): void
    {
        $this->images[$filename] = $content;
    }

    public function removeImage(string $filename): void
    {
        unset($this->images[$filename]);
    }

    public function addSection(string $title): void
    {
        $section = new EPubSection($title);
        $this->chapters[] = $section;
    }

    public function findAndReplace(string $search, string $replace): void
    {
        foreach ($this->chapters as $chapter) {
            if ($chapter instanceof EPubChapter) {
                $content = $chapter->getContent();
                $content = str_replace($search, $replace, $content);
                $chapter->setContent($content);
            }
        }
    }
}
