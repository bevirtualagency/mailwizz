<?php

/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Csv;

use SeekableIterator;
use SplFileObject;
use TypeError;
use function array_keys;
use function array_walk_recursive;
use function fclose;
use function feof;
use function fflush;
use function fgetcsv;
use function fgets;
use function fopen;
use function fpassthru;
use function fputcsv;
use function fread;
use function fseek;
use function fwrite;
use function get_resource_type;
use function gettype;
use function is_resource;
use function rewind;
use function sprintf;
use function stream_filter_append;
use function stream_filter_remove;
use function stream_get_meta_data;
use function strlen;
use const PHP_VERSION_ID;
use const SEEK_SET;

/**
 * An object oriented API to handle a PHP stream resource.
 *
 * @internal used internally to iterate over a stream resource
 */
class Stream implements SeekableIterator
{
    /**
     * Attached filters.
     *
     * @var array<string, array<resource>>
     */
    protected $filters = [];

    /**
     * stream resource.
     *
     * @var resource
     */
    protected $stream;

    /**
     * Tell whether the stream should be closed on object destruction.
     *
     * @var bool
     */
    protected $should_close_stream = false;

    /**
     * Current iterator value.
     *
     * @var mixed can be a null false or a scalar type value
     */
    protected $value;

    /**
     * Current iterator key.
     *
     * @var int
     */
    protected $offset;

    /**
     * Flags for the Document.
     *
     * @var int
     */
    protected $flags = 0;

    /**
     * the field delimiter (one character only).
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * the field enclosure character (one character only).
     *
     * @var string
     */
    protected $enclosure = '"';

    /**
     * the field escape character (one character only).
     *
     * @var string
     */
    protected $escape = '\\';

    /**
     * Tell whether the current stream is seekable;.
     *
     * @var bool
     */
    protected $is_seekable = false;

    /**
     * New instance.
     *
     * @param mixed $stream stream type resource
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new TypeError(sprintf('Argument passed must be a stream resource, %s given', gettype($stream)));
        }

        if ('stream' !== ($type = get_resource_type($stream))) {
            throw new TypeError(sprintf('Argument passed must be a stream resource, %s resource given', $type));
        }

        $this->is_seekable = stream_get_meta_data($stream)['seekable'];
        $this->stream = $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        $walker = static function ($filter): bool {
            return @stream_filter_remove($filter);
        };

        array_walk_recursive($this->filters, $walker);

        if ($this->should_close_stream && is_resource($this->stream)) {
            fclose($this->stream);
        }

        unset($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        throw new Exception(sprintf('An object of class %s cannot be cloned', static::class));
    }

    /**
     * {@inheritdoc}
     */
    public function __debugInfo(): array
    {
        return stream_get_meta_data($this->stream) + [
            'delimiter' => $this->delimiter,
            'enclosure' => $this->enclosure,
            'escape' => $this->escape,
            'stream_filters' => array_keys($this->filters),
        ];
    }

    /**
     * Return a new instance from a file path.
     *
     * @param resource|null $context
     *
     * @throws Exception if the stream resource can not be created
     */
    public static function createFromPath(string $path, string $open_mode = 'r', $context = null): self
    {
        $args = [$path, $open_mode];
        if (null !== $context) {
            $args[] = false;
            $args[] = $context;
        }

        $resource = @fopen(...$args);
        if (!is_resource($resource)) {
            throw new Exception(sprintf('`%s`: failed to open stream: No such file or directory', $path));
        }

        $instance = new self($resource);
        $instance->should_close_stream = true;

        return $instance;
    }

    /**
     * Return a new instance from a string.
     */
    public static function createFromString(string $content = ''): self
    {
        /** @var resource $resource */
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $content);

        $instance = new self($resource);
        $instance->should_close_stream = true;

        return $instance;
    }

    /**
     * Return the URI of the underlying stream.
     */
    public function getPathname(): string
    {
        return stream_get_meta_data($this->stream)['uri'];
    }

    /**
     * append a filter.
     *
     * @see http://php.net/manual/en/function.stream-filter-append.php
     *
     * @param  null|mixed $params
     * @throws Exception  if the filter can not be appended
     */
    public function appendFilter(string $filtername, int $read_write, $params = null): void
    {
        $res = @stream_filter_append($this->stream, $filtername, $read_write, $params);
        if (!is_resource($res)) {
            throw new InvalidArgument(sprintf('unable to locate filter `%s`', $filtername));
        }

        $this->filters[$filtername][] = $res;
    }

    /**
     * Set CSV control.
     *
     * @see http://php.net/manual/en/SplFileObject.setcsvcontrol.php
     */
    public function setCsvControl(string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): void
    {
        list($this->delimiter, $this->enclosure, $this->escape) = $this->filterControl($delimiter, $enclosure, $escape, __METHOD__);
    }

    /**
     * Filter Csv control characters.
     *
     * @throws Exception If the Csv control character is not one character only.
     */
    protected function filterControl(string $delimiter, string $enclosure, string $escape, string $caller): array
    {
        if (1 !== strlen($delimiter)) {
            throw new InvalidArgument(sprintf('%s() expects delimiter to be a single character', $caller));
        }

        if (1 !== strlen($enclosure)) {
            throw new InvalidArgument(sprintf('%s() expects enclosure to be a single character', $caller));
        }

        if (1 === strlen($escape) || ('' === $escape && 70400 <= PHP_VERSION_ID)) {
            return [$delimiter, $enclosure, $escape];
        }

        throw new InvalidArgument(sprintf('%s() expects escape to be a single character', $caller));
    }

    /**
     * Set CSV control.
     *
     * @see http://php.net/manual/en/SplFileObject.getcsvcontrol.php
     *
     * @return string[]
     */
    public function getCsvControl(): array
    {
        return [$this->delimiter, $this->enclosure, $this->escape];
    }

    /**
     * Set CSV stream flags.
     *
     * @see http://php.net/manual/en/SplFileObject.setflags.php
     */
    public function setFlags(int $flags): void
    {
        $this->flags = $flags;
    }

    /**
     * Write a field array as a CSV line.
     *
     * @see http://php.net/manual/en/SplFileObject.fputcsv.php
     *
     * @return int|false
     */
    public function fputcsv(array $fields, string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
    {
        $controls = $this->filterControl($delimiter, $enclosure, $escape, __METHOD__);

        return fputcsv($this->stream, $fields, ...$controls);
    }

    /**
     * Get line number.
     *
     * @see http://php.net/manual/en/SplFileObject.key.php
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->offset;
    }

    /**
     * Read next line.
     *
     * @see http://php.net/manual/en/SplFileObject.next.php
     */
    public function next(): void
    {
        $this->value = false;
        $this->offset++;
    }

    /**
     * Rewind the file to the first line.
     *
     * @see http://php.net/manual/en/SplFileObject.rewind.php
     *
     * @throws Exception if the stream resource is not seekable
     */
    public function rewind(): void
    {
        if (!$this->is_seekable) {
            throw new Exception('stream does not support seeking');
        }

        rewind($this->stream);
        $this->offset = 0;
        $this->value = false;
        if (0 !== ($this->flags & SplFileObject::READ_AHEAD)) {
            $this->current();
        }
    }

    /**
     * Not at EOF.
     *
     * @see http://php.net/manual/en/SplFileObject.valid.php
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        if (0 !== ($this->flags & SplFileObject::READ_AHEAD)) {
            return $this->current() !== false;
        }

        return !feof($this->stream);
    }

    /**
     * Retrieves the current line of the file.
     *
     * @see http://php.net/manual/en/SplFileObject.current.php
     *
     * @return mixed The value of the current element.
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        if (false !== $this->value) {
            return $this->value;
        }

        $this->value = $this->getCurrentRecord();

        return $this->value;
    }

    /**
     * Retrieves the current line as a CSV Record.
     *
     * @return array|false|null
     */
    protected function getCurrentRecord()
    {
        do {
            $ret = fgetcsv($this->stream, 0, $this->delimiter, $this->enclosure, $this->escape);
        } while ((0 !== ($this->flags & SplFileObject::SKIP_EMPTY)) && $ret !== null && $ret !== false && $ret[0] === null);

        return $ret;
    }

    /**
     * Seek to specified line.
     *
     * @see http://php.net/manual/en/SplFileObject.seek.php
     *
     * @param  int       $position
     * @throws Exception if the position is negative
     */
    public function seek($position): void
    {
        if ($position < 0) {
            throw new Exception(sprintf('%s() can\'t seek stream to negative line %d', __METHOD__, $position));
        }

        $this->rewind();
        while ($this->key() !== $position && $this->valid()) {
            $this->current();
            $this->next();
        }

        if (0 !== $position) {
            $this->offset--;
        }

        $this->current();
    }

    /**
     * Output all remaining data on a file pointer.
     *
     * @see http://php.net/manual/en/SplFileObject.fpatssthru.php
     *
     * @return int|false
     */
    public function fpassthru()
    {
        return fpassthru($this->stream);
    }

    /**
     * Read from file.
     *
     * @see http://php.net/manual/en/SplFileObject.fread.php
     *
     * @param int $length The number of bytes to read
     *
     * @return string|false
     */
    public function fread(int $length)
    {
        return fread($this->stream, $length);
    }

    /**
     * Gets a line from file.
     *
     * @see http://php.net/manual/en/SplFileObject.fgets.php
     *
     * @return string|false
     */
    public function fgets()
    {
        return fgets($this->stream);
    }

    /**
     * Seek to a position.
     *
     * @see http://php.net/manual/en/SplFileObject.fseek.php
     *
     * @throws Exception if the stream resource is not seekable
     */
    public function fseek(int $offset, int $whence = SEEK_SET): int
    {
        if (!$this->is_seekable) {
            throw new Exception('stream does not support seeking');
        }

        return fseek($this->stream, $offset, $whence);
    }

    /**
     * Write to stream.
     *
     * @see http://php.net/manual/en/SplFileObject.fwrite.php
     *
     * @return int|false
     */
    public function fwrite(string $str, int $length = null)
    {
        $args = [$this->stream, $str];
        if (null !== $length) {
            $args[] = $length;
        }

        return fwrite(...$args);
    }

    /**
     * Flushes the output to a file.
     *
     * @see http://php.net/manual/en/SplFileObject.fwrite.php
     */
    public function fflush(): bool
    {
        return fflush($this->stream);
    }
}