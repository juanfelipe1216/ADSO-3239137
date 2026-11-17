<?php

declare(strict_types=1);

namespace Tests\Integration;

use Lab\Adapters\Storage\LocalCoverStorage;
use Lab\Application\DTOs\CoverImage;
use Lab\Domain\DomainViolation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\ImageFixture;

final class LocalCoverStorageTest extends TestCase
{
    private string $directory;
    private LocalCoverStorage $storage;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/lab-cover-test-' . bin2hex(random_bytes(12));
        $this->storage = new LocalCoverStorage($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) { unlink($file); }
        if (is_dir($this->directory)) { rmdir($this->directory); }
    }

    public function test_binary_round_trip_opaque_unique_keys_and_idempotent_delete(): void
    {
        $image = new CoverImage(ImageFixture::png());
        $key = $this->storage->put($image);
        $another = $this->storage->put($image);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $key);
        self::assertNotSame($key, $another);
        self::assertSame($image->bytes, $this->storage->read($key)->bytes);
        self::assertSame('image/png', $this->storage->read($key)->mimeType);
        $this->storage->delete($key);
        $this->storage->delete($key);
        self::assertNull($this->storage->read($key));
        self::assertNotNull($this->storage->read($another));
    }

    public function test_two_mebibytes_are_accepted_but_one_more_byte_is_rejected(): void
    {
        $bytes = str_pad(ImageFixture::png(), CoverImage::MAX_BYTES, "\0");
        $image = new CoverImage($bytes);
        self::assertSame(CoverImage::MAX_BYTES, strlen($this->storage->read($this->storage->put($image))->bytes));
        $this->expectException(DomainViolation::class);
        new CoverImage($bytes . 'x');
    }

    public function test_jpeg_type_is_detected_from_bytes_and_preserved(): void
    {
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD//2Q==', true);
        $key = $this->storage->put(new CoverImage($bytes));
        self::assertSame('image/jpeg', $this->storage->read($key)->mimeType);
        self::assertSame($bytes, $this->storage->read($key)->bytes);
    }

    #[DataProvider('invalidImages')]
    public function test_content_validation_rejects_non_images(string $bytes): void
    {
        $this->expectException(DomainViolation::class);
        new CoverImage($bytes);
    }

    public static function invalidImages(): array { return [[''], ['not-a-jpeg.jpg'], ['<svg xmlns="http://www.w3.org/2000/svg"/>'], ["\x89PNG\r\n\x1a\n"]]; }

    #[DataProvider('unsafeKeys')]
    public function test_keys_cannot_address_files_outside_the_storage(string $key): void
    {
        $this->expectException(\RuntimeException::class);
        $this->storage->read($key);
    }

    public static function unsafeKeys(): array { return [['../private'], ['/etc/passwd'], ['C:\\private'], [''], [str_repeat('a', 63)]]; }
}
