<?php

namespace tregor\epub;

class EPubChapter
{
    protected string $title;
    protected string $content;
    protected int $order = 99;
    protected string $folder;

    public function __construct(string $title, string $content, int $order)
    {
        $this->title = $title;
        $this->content = $content;
        $this->order = $order;
        $this->folder = 'OPS';
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitleMD5(): string
    {
        return hash('md5', $this->title);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder(int $order): void
    {
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * @param string $folder
     */
    public function setFolder(string $folder): void
    {
        $this->folder = $folder;
    }
}