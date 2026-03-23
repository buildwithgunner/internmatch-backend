<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects input that contains emoji characters.
 * Allows standard text, numbers, punctuation, and accented letters.
 */
class NoEmoji implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        // Match emoji Unicode ranges
        $emojiPattern = '/[\x{1F600}-\x{1F64F}'  // Emoticons
            . '\x{1F300}-\x{1F5FF}'               // Misc Symbols & Pictographs
            . '\x{1F680}-\x{1F6FF}'               // Transport & Map
            . '\x{1F1E0}-\x{1F1FF}'               // Flags
            . '\x{2600}-\x{26FF}'                  // Misc Symbols
            . '\x{2700}-\x{27BF}'                  // Dingbats
            . '\x{FE00}-\x{FE0F}'                  // Variation Selectors
            . '\x{1F900}-\x{1F9FF}'               // Supplemental Symbols
            . '\x{1FA00}-\x{1FA6F}'               // Chess Symbols
            . '\x{1FA70}-\x{1FAFF}'               // Symbols Extended-A
            . '\x{200D}'                           // Zero Width Joiner
            . '\x{20E3}'                           // Combining Enclosing Keycap
            . '\x{FE0F}'                           // Variation Selector-16
            . '\x{E0020}-\x{E007F}'               // Tags
            . ']/u';

        if (preg_match($emojiPattern, $value)) {
            $field = str_replace('_', ' ', $attribute);
            $fail("The {$field} field must not contain emojis.");
        }
    }
}
