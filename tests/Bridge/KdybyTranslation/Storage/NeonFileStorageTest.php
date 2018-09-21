<?php

namespace Efabrica\TranslationsAutomatization\Tests\Bridge\KdybyTranslation\Storage;

use Efabrica\TranslationsAutomatization\Bridge\KdybyTranslation\Storage\NeonFileStorage;
use PHPUnit\Framework\TestCase;

class NeonFileStorageTest extends TestCase
{
    private $neonFileStoragePath;

    protected function setUp()
    {
        parent::setUp();
        $this->neonFileStoragePath = __DIR__ . '/../../../../temp/neon-file-storage-test.neon';
        if (file_exists($this->neonFileStoragePath)) {
            unlink($this->neonFileStoragePath);
        }
    }

    public function testLoadEmptyFile()
    {
        file_put_contents($this->neonFileStoragePath, '');

        $fileStorage = new NeonFileStorage($this->neonFileStoragePath, '');
        $this->assertEquals([], $fileStorage->load());
    }

    public function testLoadFileWithNewRows()
    {
        file_put_contents($this->neonFileStoragePath, "\n\n\n\n");

        $fileStorage = new NeonFileStorage($this->neonFileStoragePath, '');
        $this->assertEquals([], $fileStorage->load());
    }

    public function testSaveWithExistingPrefix()
    {
        $fileStorage = new NeonFileStorage($this->neonFileStoragePath, 'prefix.');
        $fileStorage->save([
            'prefix.a' => 'a',
            'prefix.b' => 'b',
            'prefix.c' => 'c',
        ]);
        $content = file_get_contents($this->neonFileStoragePath);
        $this->assertEquals("a: a\nb: b\nc: c\n", $content);
    }

    public function testSaveNumericKeys()
    {
        $fileStorage = new NeonFileStorage($this->neonFileStoragePath, 'prefix.');
        $fileStorage->save([
            1 => 'a',
            2 => 'b',
            3 => 'c',
        ]);
        $content = file_get_contents($this->neonFileStoragePath);
        $this->assertEquals("- a\n- b\n- c\n", $content);
    }

    public function testSaveWithNonExistingPrefix()
    {
        $fileStorage = new NeonFileStorage($this->neonFileStoragePath, 'non.existing.prefix.');
        $fileStorage->save([
            'prefix.a' => 'a',
            'prefix.b' => 'b',
            'prefix.c' => 'c',
        ]);
        $content = file_get_contents($this->neonFileStoragePath);
        $this->assertEquals("prefix:\n\ta: a\n\tb: b\n\tc: c\n\n", $content);
    }
}
