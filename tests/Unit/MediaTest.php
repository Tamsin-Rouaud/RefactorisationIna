<?php

namespace App\Tests\Unit;

use App\Entity\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PHPUnit\Framework\TestCase;

class MediaTest extends TestCase
{
    public function testSetAndGetFile(): void
    {
        $media = new Media();

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'fake content');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.jpg',
            'image/jpeg',
            null,
            true // test mode
        );

        $media->setFile($uploadedFile);
        $this->assertSame($uploadedFile, $media->getFile());
    }
}
