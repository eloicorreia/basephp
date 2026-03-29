<?php

declare(strict_types=1);

namespace App\Support\Http;

class ApiErrorFormatter
{
    /**
     * @param array<string, array<int, string>> $validationErrors
     * @return array<int, array<string, mixed>>
     */
    public static function fromValidation(array $validationErrors): array
    {
        $formatted = [];

        foreach ($validationErrors as $field => $messages) {
            foreach ($messages as $message) {
                $formatted[] = [
                    'field' => $field,
                    'message' => $message,
                    'type' => 'VALIDATION_ERROR',
                ];
            }
        }

        return $formatted;
    }
}