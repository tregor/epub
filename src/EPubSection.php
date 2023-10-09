<?php

namespace tregor\epub;

class EPubSection extends EPubChapter
{
    public function __construct(string $title)
    {
        parent::__construct($title, '', 0);
    }
}