<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\SecretReader;
use RuntimeException;
use Tests\TestCase;

final class SecretReaderTest extends TestCase
{
    public function test_reads_files_environment_aliases_and_optional_values(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'secret-');
        self::assertIsString($path);
        file_put_contents($path, " file-value \n");

        putenv("FILE_SECRET_FILE={$path}");
        putenv('FILE_SECRET=ignored');
        putenv('ALIAS_SECRET= alias-value ');
        putenv('OPTIONAL_SECRET');
        putenv('DIRECT_SECRET_FILE=   ');
        putenv('DIRECT_SECRET= direct-value ');
        putenv('BLANK_SECRET=   ');

        self::assertSame('file-value', SecretReader::read('FILE_SECRET'));
        self::assertSame('alias-value', SecretReader::read('PRIMARY_SECRET', ['ALIAS_SECRET']));
        self::assertSame('direct-value', SecretReader::read('DIRECT_SECRET'));
        self::assertSame('', SecretReader::read('OPTIONAL_SECRET', required: false));
        self::assertSame('', SecretReader::read('BLANK_SECRET', required: false));

        putenv('FILE_SECRET_FILE');
        putenv('FILE_SECRET');
        putenv('ALIAS_SECRET');
        putenv('DIRECT_SECRET_FILE');
        putenv('DIRECT_SECRET');
        putenv('BLANK_SECRET');
        unlink($path);
    }

    public function test_rejects_missing_empty_and_unreadable_secrets(): void
    {
        putenv('MISSING_SECRET');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Segredo obrigatório ausente: MISSING_SECRET');
        SecretReader::read('MISSING_SECRET');
    }

    public function test_rejects_invalid_secret_files(): void
    {
        $empty = tempnam(sys_get_temp_dir(), 'empty-secret-');
        self::assertIsString($empty);
        file_put_contents($empty, ' ');
        putenv("EMPTY_FILE_SECRET_FILE={$empty}");

        try {
            SecretReader::read('EMPTY_FILE_SECRET');
            self::fail('O segredo vazio deveria falhar.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('EMPTY_FILE_SECRET', $exception->getMessage());
        } finally {
            putenv('EMPTY_FILE_SECRET_FILE');
            unlink($empty);
        }

        putenv('ABSENT_FILE_SECRET_FILE='.sys_get_temp_dir().'/missing-secret-file');
        try {
            SecretReader::read('ABSENT_FILE_SECRET');
            self::fail('O arquivo ausente deveria falhar.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('ABSENT_FILE_SECRET', $exception->getMessage());
        } finally {
            putenv('ABSENT_FILE_SECRET_FILE');
        }
    }

    public function test_normalizes_application_keys(): void
    {
        putenv('APP_KEY=base64:already-normalized');
        self::assertSame('base64:already-normalized', SecretReader::applicationKey());

        putenv('APP_KEY=12345678901234567890123456789012');
        self::assertSame('12345678901234567890123456789012', SecretReader::applicationKey());

        putenv('APP_KEY=legacy-flask-key');
        self::assertStringStartsWith('base64:', SecretReader::applicationKey());

        putenv('APP_KEY');
        putenv('FLASK_SECRET_KEY');
        self::assertSame('', SecretReader::applicationKey(required: false));
        putenv('APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
    }
}
