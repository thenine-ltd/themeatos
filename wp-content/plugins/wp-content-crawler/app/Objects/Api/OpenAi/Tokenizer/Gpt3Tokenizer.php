<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 04/02/2023
 * Time: 23:11
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\Tokenizer;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use WPCCrawler\Factory;
use WPCCrawler\Objects\Enums\InformationType;
use WPCCrawler\Objects\File\FileService;
use WPCCrawler\Objects\Informing\Information;
use WPCCrawler\Objects\Informing\Informer;

/**
 * PHP implementation of https://github.com/latitudegames/GPT-3-Encoder/blob/master/Encoder.js
 *
 * Use {@link encode()} to retrieve the token IDs of a text. The result can be used to learn how many tokens a text
 * contains. If a need for recreating the original text arises, use {@link decode()} to create the original text.
 */
class Gpt3Tokenizer {

    const ENCODER_PATH   = 'encoder.json';
    const VOCAB_BPE_PATH = 'vocab.bpe';

    /** @var Gpt3Tokenizer|null */
    private static $instance = null;

    /**
     * @var string Regex pattern that matches tokens. This is not the pattern used by OpenAI's Tokenizer page. In that
     * page, there is an almost 20k-char regex pattern, which can be seen in one of the JS files loaded in the page.
     * Although this pattern does not match the same texts as the original tokenizer does, the results of this is pretty
     * close to the original one. It looks like this pattern results in more token IDs. If it found fewer tokens, it
     * could be a problem. However, finding more token IDs is not that big of a problem, since our goal is to learn how
     * many tokens a text contains so that we can arrange the contents of a request made to OpenAI API, to avoid errors
     * caused by sending too many tokens in one request.
     * This regex is retrieved from the NodeJS library whose link is added to the OpenAI's Tokenizer page as a
     * recommended way to access the original tokenizer programmatically. The NodeJS lib's file and the file from
     * HuggingFace's library are linked below as a reference.
     *
     * @see https://platform.openai.com/tokenizer
     * @see https://github.com/huggingface/transformers/blob/main/src/transformers/models/gpt2/tokenization_gpt2.py
     * @see https://github.com/openai/gpt-2/blob/master/src/encoder.py
     * @see https://github.com/latitudegames/GPT-3-Encoder/blob/master/Encoder.js
     */
    private $pattern = "'s|'t|'re|'ve|'m|'ll|'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+";

    /** @var array<int, string>|null */
    private $byteEncoder = null;

    /** @var array<string, int>|null */
    private $byteDecoder = null;

    /** @var array<string, int>|null */
    private $encoder = null;

    /** @var array<int, string>|null */
    private $decoder = null;

    /** @var array<string, int>|null */
    private $bpeRanks = null;

    /** @var array<string, string> */
    private $cache = [];

    /**
     * @var string Used to separate the pairs. This separator character must NOT be one of the characters returned by
     *      {@link createByteEncoder()} to avoid an infinite loop.
     */
    private $separator = "˧";

    /**
     * @return Gpt3Tokenizer The instance of the class
     * @since 1.13.0
     */
    public static function getInstance(): Gpt3Tokenizer {
        if (self::$instance === null) {
            self::$instance = new Gpt3Tokenizer();
        }

        return self::$instance;
    }

    /*
     *
     */

    /** This is a singleton. Use {@link Gpt3Tokenizer::getInstance()}. */
    protected function __construct() { }

    /**
     * @param string[] $texts The texts whose sum of token count is wanted
     * @return int The number of tokens in the given texts in total
     * @since 1.13.0
     */
    public function getTokenCount(array $texts): int {
        $count = 0;
        foreach($texts as $text) {
            $count += count($this->encode($text));
        }

        return $count;
    }

    /**
     * @param string $text The text to be encoded
     * @return int[] Token IDs
     * @since 1.13.0
     */
    public function encode(string $text): array {
        $bpeTokens = [];
        preg_match_all(sprintf('/%s/u', $this->pattern), $text, $matches);
        $tokens = $matches[0] ?? null;
        if (!$tokens) {
            return $bpeTokens;
        }

        $encoder = $this->getEncoder();
        $byteEncoder = $this->getByteEncoder();
        foreach($tokens as $token) {
            $parts = array_map(function($value) use (&$byteEncoder): string {
                return $byteEncoder[$value] ?? ' ';
            }, $this->encodeStr($token));

            $token = implode('', $parts);
            $newTokens = array_map(function($x) use (&$encoder): int {
                return $encoder[$x] ?? 0;
            }, explode(' ', $this->bpe($token)));

            $bpeTokens = array_merge($bpeTokens, $newTokens);
        }

        return $bpeTokens;
    }

    /**
     * @param int[] $tokens The tokens retrieved via {@link encode()}
     * @return string Decoded tokens, which is the original text that was previously encoded
     * @since 1.13.0
     */
    public function decode(array $tokens): string {
        $decoder = $this->getDecoder();
        $byteDecoder = $this->getByteDecoder();
        $decodedParts = array_map(function($x) use (&$decoder): string {
            return $decoder[$x] ?? ' ';
        }, $tokens);
        $byteDecodedParts = array_map(function($x) use (&$byteDecoder): int {
            return $byteDecoder[$x] ?? 0;
        }, mb_str_split(implode('', $decodedParts)));

        return $this->decodeStr($byteDecodedParts);
    }

    /*
     *
     */

    /**
     * @param string[] $word A character array whose character pairs are needed. This can be created via
     *                       {@link mb_str_split()}
     * @return string[] The character pairs of the word
     * @since 1.13.0
     */
    public function getPairs(array $word): array {
        $pairs = [];
        $length = count($word);
        if ($length < 2) {
            return [];
        }

        $prevChar = $word[0];
        for($i = 1; $i < $length; $i++) {
            $char = $word[$i];
            // The original implementation returns a tuple that then will be used as keys of an array. In PHP, we need
            // to use arrays instead of tuples. Because an array cannot be used as a key of an array, we instead create
            // a string that is created by joining the parts with "|" separator.
            $pairs[] = "{$prevChar}{$this->separator}{$char}";
            $prevChar = $char;
        }

        return $pairs;
    }

    /**
     * @param string $str The string to be encoded to an unsigned 8-bit int array
     * @return int[]
     * @since 1.13.0
     */
    public function encodeStr(string $str): array {
        return unpack("C*", $str) ?: [];
    }

    /**
     * @param int[] $values Unsigned 8-bit int array
     * @return string
     * @since 1.13.0
     */
    public function decodeStr(array $values): string {
        $result = pack("C*", ...$values);
        return $result === false // @phpstan-ignore-line
            ? ''
            : $result;
    }

    /**
     * @return array<int, string>
     * @since 1.13.0
     */
    public function getByteEncoder(): array {
        if ($this->byteEncoder === null) {
            $this->byteEncoder = $this->createByteEncoder();
        }

        return $this->byteEncoder;
    }

    /**
     * @return array<string, int>
     * @since 1.13.0
     */
    public function getByteDecoder(): array {
        if ($this->byteDecoder === null) {
            $this->byteDecoder = array_flip($this->getByteEncoder());
        }

        return $this->byteDecoder;
    }

    /**
     * @return array<string, int>
     * @since 1.13.0
     */
    public function getEncoder(): array {
        if ($this->encoder === null) {
            $this->encoder = $this->readEncoderFile();
        }

        return $this->encoder;
    }

    /**
     * @return array<int, string>
     * @since 1.13.0
     */
    public function getDecoder(): array {
        if ($this->decoder === null) {
            $this->decoder = array_flip($this->getEncoder());
        }
        return $this->decoder;
    }

    /**
     * @return array<string, int>
     * @since 1.13.0
     */
    public function getBpeRanks(): ?array {
        if ($this->bpeRanks === null) {
            $this->bpeRanks = $this->createBpeRanks();
        }

        return $this->bpeRanks;
    }

    /*
     * HELPERS
     */

    /**
     * Byte-pair encoding (BPE)
     *
     * @param string $token
     * @return string
     * @since 1.13.0
     */
    protected function bpe(string $token): string {
        $cachedResult = $this->cache[$token] ?? null;
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        /** @var string[] $word */
        $word = mb_str_split($token);

        $pairs = $this->getPairs($word);
        if (!$pairs) {
            return $token;
        }

        $ranks = $this->getBpeRanks();
        while (true) {
            /** @var array<int, string> $minPairs */
            $minPairs = [];
            foreach($pairs as $pair) {
                $minPairs[$ranks[$pair] ?? 10e10] = $pair;
            }

            $bigram = $minPairs[min(array_keys($minPairs))];
            if (!isset($ranks[$bigram])) {
                break;
            }

            $bigramArr = explode($this->separator, $bigram);
            if (!$bigramArr || count($bigramArr) < 2) {
                continue;
            }

            $first = $bigramArr[0];
            $second = $bigramArr[1];
            $newWord = [];
            $i = 0;

            $wordLength = count($word);
            while ($i < $wordLength) {
                $j = array_search($first, array_slice($word, $i));
                if ($j === false) {
                    $newWord = array_merge($newWord, array_slice($word, $i));
                    break;
                }

                $j = (int) $j;
                $j += $i;
                $newWord = array_merge($newWord, array_slice($word, $i, $j - $i));
                $i = $j;

                if ($word[$i] === $first && $i < $wordLength - 1 && $word[$i + 1] === $second) {
                    $newWord[] = $first . $second;
                    $i += 2;
                } else {
                    $newWord[] = $word[$i];
                    $i += 1;
                }
            }

            $word = $newWord;
            if (count($word) === 1) {
                break;
            } else {
                $pairs = $this->getPairs($word);
            }
        }

        $wordStr = implode(' ', $word);
        $this->cache[$token] = $wordStr;

        // Evict the earlier caches to free up some memory
        if (count($this->cache) > 16000) {
            $this->cache = array_slice($this->cache, 6000, null, true);
        }

        return $wordStr;
    }

    /**
     * @return array<string, int>
     * @since 1.13.0
     */
    protected function createBpeRanks(): array {
        $contents = $this->readFile(self::VOCAB_BPE_PATH);
        if ($contents === null) {
            return [];
        }

        $lines = explode("\n", $contents);
        $bpeMerges = array_slice($lines, 1, count($lines) - 2);
        $bpeMerges = array_map(function($line) {
            // The return value will later be used as a key of the result. In the original implementation, the return
            // values are Python tuples, which can be used as dictionary keys. In PHP, because there is no tuple data
            // type, we need to use arrays. However, arrays cannot be used as keys of an array. Therefore, instead of
            // returning an array, we join the parts of the array with "|" separator and return it instead, so that we
            // can use it as a key of the final array.
            return implode($this->separator, array_filter(
                preg_split('/\s+/', $line) ?: [],
                function(string $part) {
                    return trim($part);
                })
            );
        }, $bpeMerges);

        return array_combine($bpeMerges, range(0, count($bpeMerges) - 1)) ?: [];
    }

    /**
     * Reads the encoder file from the file system
     *
     * @return array<string, int> The parsed encoder file
     * @since 1.13.0
     */
    protected function readEncoderFile(): array {
        $contents = $this->readFile(self::ENCODER_PATH);
        if ($contents === null) {
            return [];
        }

        $data = json_decode($contents, true);
        if (!is_array($data)) {
            $message = _wpcc('The encoder file for GPT-3 tokenizer could not be read.');
            $info = new Information($message, json_last_error_msg(), InformationType::ERROR);
            Informer::add($info->addAsLog());
            return [];
        }

        return $data;
    }

    /**
     * @return array<int, string>
     * @since 1.13.0
     */
    protected function createByteEncoder(): array {
        $bs = array_merge(
            range(mb_ord('!'), mb_ord('~')),
            range(mb_ord('¡'), mb_ord('¬')),
            range(mb_ord('®'), mb_ord('ÿ')),
        );

        $cs = array_map(function($b) {
            return mb_chr($b);
        }, $bs);
        $n = 0;
        $length = 2 ** 8;
        for($b = 0; $b < $length; $b++) {
            if (in_array($b, $bs)) continue;

            $bs[] = $b;
            $cs[] = mb_chr($length + $n);
            $n += 1;
        }

        return array_combine($bs, $cs) ?: [];
    }

    /**
     * @param string $relativePath File path relative to the openai/data directory
     * @return string|null The contents of the file. If the file is not found, or it could not be read, returns `null`.
     * @since 1.13.0
     */
    protected function readFile(string $relativePath): ?string {
        $filePath = $this->getDataPath($relativePath);

        $fs = FileService::getInstance()->getFileSystem();
        if (!$fs->exists($filePath) || !$fs->isFile($filePath)) {
            Informer::addError(sprintf(_wpcc('File "%1$s" could not be found.'), $filePath))
                ->addAsLog();
            return null;
        }

        try {
            $contents = $fs->get($filePath);

        } catch (FileNotFoundException $e) {
            Informer::addError($e->getMessage())
                ->setException($e)
                ->addAsLog();
            return null;
        }

        return $contents;
    }

    /**
     * @param string $relativeFilePath A file path relative to `data/openai/` directory.
     * @return string
     * @since 1.13.0
     */
    protected function getDataPath(string $relativeFilePath): string {
        return Factory::assetManager()
            ->pluginPath('data/openai/' . $relativeFilePath);
    }

}