# EPub Generator

[![GitHub stars](https://img.shields.io/github/stars/tregor/epub?style=flat-square)](https://github.com/tregor/epub/stargazers)
[![Last Commit](https://img.shields.io/github/last-commit/tregor/epub?style=flat-square)](https://github.com/tregor/epub)
[![GitHub forks](https://img.shields.io/github/forks/tregor/epub?style=flat-square)](https://github.com/tregor/epub/network)
[![GitHub license](https://img.shields.io/github/license/tregor/epub?style=flat-square)](LICENSE)

The EPub Generator is a powerful PHP library for creating EPub books. It provides a comprehensive set of features and an intuitive API to generate EPub files with ease. Whether you're a writer, publisher, or developer, this library will simplify the process of creating EPub books and help you deliver high-quality content to your readers.

## Features

- Create EPub books from scratch
- Set book metadata such as title, author, and language
- Add chapters with custom titles, content, and order
- Remove, update, and rearrange chapters
- Export EPub books to standard EPub files
- Parse EPub files and retrieve book information

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

### Creating a New EPub Book

To create a new EPub book, you need to instantiate the `EPubBook` class and provide the book's title:

```php
use tregor\epub\EPubBook;

$book = new EPubBook('My First Book');
```

### Parsing an Existing EPub Book

You can also parse an existing EPub book and retrieve its information. To do this, use the `open` method:

```php
$book = EPubBook::open('existing_book.epub');
```

### Setting Book Metadata

You can set the author and language of the book using the `setAuthor` and `setLanguage` methods:

```php
$book->setAuthor('John Doe');
$book->setLanguage('en');
```

### Adding Chapters

You can add chapters to the book using the `addChapter` method. Each chapter requires a title, content, and order:

```php
$book->addChapter('Chapter 1', 'This is the first chapter.', 1);
$book->addChapter('Chapter 2', 'This is the second chapter.', 2);
```

### Rearranging Chapters

You can add a new chapter after an existing chapter by specifying the order of the existing chapter, the title and content of the new chapter, and the new order:

```php
$book->addChapterAfter(1, 'Chapter 2', 'This is the new Chapter 2.', 2);
```

### Removing Chapters

To remove a chapter from the book, specify its order:

```php
$book->removeChapter(2);
```

### Updating Chapters

You can update the content of a chapter by specifying its order and providing the new content:

```php
$book->setChapterContent(1, 'This is the updated content of Chapter 1.');
```

### Exporting the EPub Book

Once you have added all the chapters, you can export the EPub book to a file using the `export` method:

```php
$book->export('my_first_book.epub');
```

## Available Methods

Here are the available methods provided by the `EPubBook` class:

- `__construct($title)`: Creates a new `EPubBook` object with the specified title.
- `addChapter($title, $content, $order)`: Adds a new chapter to the book with the specified title, content, and order.
- `removeChapter($order)`: Removes a chapter from the book with the specified order.
- `addChapterAfter($order, $title, $content, $newOrder)`: Adds a new chapter after an existing chapter with the specified order, title, content, and new order.
- `getChapterContent($order)`: Retrieves the content of a chapter with the specified order.
- `setChapterContent($order, $content)`: Updates the content of a chapter with the specified order.
- `export($filename)`: Exports the book to an EPub file with the specified filename.
- `getTitle()`: Retrieves the title of the book.
- `setTitle($title)`: Sets the title of the book.
- `getAuthor()`: Retrieves the author of the book.
- `setAuthor($author)`: Sets the author of the book.
- `getLanguage()`: Retrieves the language of the book.
- `setLanguage($language)`: Sets the language of the book.

## TODO

Here is a list of features that we plan to implement in future releases:

- Support for adding a table of contents
- Support for adding a cover image
- Support for adding additional metadata
- Improved error handling and exception handling

## License

The EPub Generator is proprietary software. See the [license file](LICENSE) for more information.

## Contributing

Contributions are welcome! If you have any ideas, suggestions, or bug reports, please open an issue or submit a pull request.

## Credits

The EPub Generator library is developed and maintained by [tregor](https://github.com/tregor).

## Keywords

EPub, EPub Generator, PHP, Library, EPub Book, EPub Format, EPub Files, EPub Creation, EPub Parser, EPub Writer, EPub Library, EPub PHP Library, EPub Generator PHP, EPub Generator Library, EPub Generator PHP Library, EPub Generator Example, EPub Generator Usage, EPub Generator Documentation, EPub Generator Tutorial, EPub Generator Guide, EPub Generator Features, EPub Generator Requirements, EPub Generator Installation, EPub Generator API, EPub Generator Methods, EPub Generator TODO, EPub Generator License, EPub Generator Contributing, EPub Generator Credits