<?php

namespace tregor\epub;

class EPubChapter
{
    protected string $title;
    protected string $content;
    protected int $order;

    public function __construct(string $title, string $content, int $order)
    {
        $this->title = $title;
        $this->content = $content;
        $this->order = $order;
    }

    public function getTitle(): string
    {
        return $this->title;
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
}