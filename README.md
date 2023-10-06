# EPub Generator

This is a comprehensive PHP library for generating EPub books. It provides a simple and intuitive API to create EPub files from scratch, allowing you to focus on the content of your book rather than the technical details of the EPub format.

[![GitHub stars](https://img.shields.io/github/stars/tregor/epub?style=flat-square)](https://github.com/tregor/Tritonium/stargazers)
[![Last Commit](https://img.shields.io/github/last-commit/tregor/epub?style=flat-square)](https://github.com/tregor/ErrorHandler)
[![GitHub forks](https://img.shields.io/github/forks/tregor/epub?style=flat-square)](https://github.com/tregor/Tritonium/network)
[![GitHub license](https://img.shields.io/github/license/tregor/epub?style=flat-square)](LICENSE)


## Navigation

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Available Methods](#available-methods)
- [Future Methods](#future-methods)
- [TODO](#todo)
- [Contribute](#contribute)
- [License](#license)
- [Copyright](#copyright)

## Requirements

- PHP 7.4 or higher
- XMLWriter extension
- Zip extension

## Installation

You can install the package via composer:

```bash
composer require tregor/epub
```

## Usage

Here is a basic example of how to use the library:

```php
<?php

require 'vendor/autoload.php';

use tregor\epub\EPubBook;

$book = new EPubBook('My First Book');
$book->setAuthor('John Doe');
$book->setLanguage('en');
$book->addChapter('Chapter 1', 'This is the first chapter.', 1);
$book->addChapter('Chapter 2', 'This is the second chapter.', 2);
$book->export('my_first_book.epub');
```

In this example, we create a new `EPubBook` object, set the book's title, author, and language, add two chapters, and then export the book to an EPub file.

## Available Methods

Here are the available methods:

- `__construct($title)`: Creates a new `EPubBook` object with the specified title.
- `setAuthor($author)`: Sets the author of the book.
- `setLanguage($language)`: Sets the language of the book.
- `addChapter($title, $content, $order)`: Adds a new chapter to the book with the specified title, content, and order.
- `export($filename)`: Exports the book to an EPub file with the specified filename.

## Future Methods

We plan to add the following methods in the future:

- `addImage($path, $description)`: Adds an image to the book.
- `addTableOfContents($chapters)`: Adds a table of contents to the book.
- `addCover($path)`: Adds a cover image to the book.
- `addMetadata($metadata)`: Adds additional metadata to the book.

## TODO

Here is a list of features that we plan to implement:

- Support for adding images
- Support for adding a table of contents
- Support for adding a cover image
- Support for adding additional metadata
- Improved error handling

## Contribute

Contributions are always welcome! Please read the [contribution guidelines](CONTRIBUTING.md) first.

## License

The EPub Generator is proprietary software. See the [license file](LICENSE.md) for more information.

## Copyright

Copyright (c) 2022 tregor. All rights reserved.