<?php

namespace App\Helpers;

trait CaptchaHelper
{
    /**
     * Generate a simple math captcha.
     */
    public static function generateCaptcha()
    {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        
        $question = "What is $num1 + $num2?";
        $answer = $num1 + $num2;

        return [
            'question' => $question,
            'answer' => $answer
        ];
    }

    /**
     * Verify the captcha answer.
     */
    public static function verifyCaptcha($input, $expected)
    {
        return (int)$input === (int)$expected;
    }
}
