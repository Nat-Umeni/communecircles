<?php

namespace Tests;

use RuntimeException;

/**
 * Reads the frontend's TypeScript types, so tests can check they still match the backend they mirror. A trait, so
 * framework-free unit tests (extending PHPUnit's TestCase) can use it too.
 */
trait ReadsFrontendTypes
{
    /**
     * The values of the string union a types/enums file exports, in the order written, e.g. ['pending', 'approved']
     * from `export type ApprovalStatus = 'pending' | 'approved';`.
     *
     * @param  string  $path  relative to resources/js/types/enums/, e.g. 'approval/approval-status.ts'
     * @return list<string>
     */
    protected function frontendEnumValues(string $path): array
    {
        $file = dirname(__DIR__).'/resources/js/types/enums/'.$path;

        if (! is_file($file) || ! preg_match('/export type \w+ =([^;]+);/', (string) file_get_contents($file), $union)) {
            throw new RuntimeException("No exported union type in resources/js/types/enums/{$path}.");
        }

        preg_match_all("/'([^']+)'/", $union[1], $values);

        return $values[1];
    }
}
