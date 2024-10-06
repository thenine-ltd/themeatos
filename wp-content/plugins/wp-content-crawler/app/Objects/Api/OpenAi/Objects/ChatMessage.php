<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 02/03/2023
 * Time: 10:59
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\Objects;

use WPCCrawler\Interfaces\Arrayable;
use WPCCrawler\Objects\Api\OpenAi\Enums\ChatRole;

/**
 * An OpenAI ChatGPT message
 */
class ChatMessage implements Arrayable {

    const KEY_ROLE    = 'role';
    const KEY_CONTENT = 'content';

    /** @var string One of the constants defined in {@link ChatRole} */
    private $role;

    /** @var string The chat message */
    private $content;

    /**
     * @param string $role    See {@link $role}
     * @param string $content See {@link $content}
     * @since 1.13.0
     */
    public function __construct(string $role, string $content) {
        $this->role    = $role;
        $this->content = $content;
    }

    /**
     * @return string See {@link $role}
     * @since 1.13.0
     */
    public function getRole(): string {
        return $this->role;
    }

    /**
     * @return string See {@link $content}
     * @since 1.13.0
     */
    public function getContent(): string {
        return $this->content;
    }

    public function toArray(): array {
        return [
            self::KEY_ROLE    => $this->getRole(),
            self::KEY_CONTENT => $this->getContent(),
        ];
    }

    /*
     * STATIC METHODS
     */

    /**
     * Creates a {@link ChatMessage} from an array
     *
     * @param array|null $message The array that contains a chat message
     * @return ChatMessage|null If a {@link ChatMessage} can be created from the given message, the {@link ChatMessage}
     *                          will be returned. Otherwise, `null` is returned.
     * @since 1.13.0
     */
    public static function fromArray(?array $message): ?ChatMessage {
        if (!$message) {
            return null;
        }

        $role    = $message[self::KEY_ROLE]    ?? null;
        $content = $message[self::KEY_CONTENT] ?? null;
        if ($role === null || $content === null) {
            return null;
        }

        return new ChatMessage($role, $content);
    }

}