<?php

declare(strict_types=1);

namespace Horde\Dav\Test\Unit;

use Horde_Dav_File;
use Horde_Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sabre\DAV\Xml\Property\GetLastModified;

#[CoversClass(Horde_Dav_File::class)]
class FileTest extends TestCase
{
    private Horde_Registry $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Horde_Registry::class);
    }

    public function testGetNameReturnsBasename(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/folder/document.txt',
            []
        );

        $this->assertSame('document.txt', $file->getName());
    }

    public function testGetNameFromNestedPath(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/deep/nested/path/file.pdf',
            []
        );

        $this->assertSame('file.pdf', $file->getName());
    }

    public function testGetLastModifiedReturnsModifiedTimestamp(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            ['modified' => 1700000000, 'created' => 1699000000]
        );

        $this->assertSame(1700000000, $file->getLastModified());
    }

    public function testGetLastModifiedFallsBackToCreated(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            ['created' => 1699000000]
        );

        $this->assertSame(1699000000, $file->getLastModified());
    }

    public function testGetLastModifiedReturnsNullWhenNoTimestamps(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            []
        );

        $this->assertNull($file->getLastModified());
    }

    public function testGetSizeReturnsContentLength(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            ['contentlength' => 4096]
        );

        $this->assertSame(4096, $file->getSize());
    }

    public function testGetSizeReturnsNullWhenNoLength(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            []
        );

        $this->assertNull($file->getSize());
    }

    public function testGetETagReturnsEtag(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            ['etag' => '"abc123"']
        );

        $this->assertSame('"abc123"', $file->getETag());
    }

    public function testGetETagReturnsNullWhenEmpty(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            []
        );

        $this->assertNull($file->getETag());
    }

    public function testGetContentTypeReturnsType(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            ['contenttype' => 'text/plain']
        );

        $this->assertSame('text/plain', $file->getContentType());
    }

    public function testGetContentTypeReturnsNullWhenMissing(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            []
        );

        $this->assertNull($file->getContentType());
    }

    public function testGetPropertiesMapsToWebdavProperties(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            [
                'contentlength' => 1024,
                'contentype' => 'text/plain',
                'etag' => '"xyz789"',
                'owner' => 'admin',
                'read-only' => true,
                'name' => 'My File',
            ]
        );

        $props = $file->getProperties([]);

        $this->assertSame(1024, $props['{DAV:}getcontentlength']);
        $this->assertSame('text/plain', $props['{DAV:}getcontenttype']);
        $this->assertSame('"xyz789"', $props['{DAV:}getetag']);
        $this->assertSame('admin', $props['{DAV:}owner']);
        $this->assertTrue($props['{http://sabredav.org/ns}read-only']);
        $this->assertSame('My File', $props['{DAV:}displayname']);
    }

    public function testGetPropertiesIncludesLastModified(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            ['modified' => 1700000000]
        );

        $props = $file->getProperties([]);

        $this->assertInstanceOf(
            GetLastModified::class,
            $props['{DAV:}getlastmodified']
        );
    }

    public function testGetPropertiesPrefersDisplayname(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            [
                'displayname' => 'Custom Display Name',
                'name' => 'Fallback Name',
            ]
        );

        $props = $file->getProperties([]);

        $this->assertSame('Custom Display Name', $props['{DAV:}displayname']);
    }

    public function testGetPropertiesReturnsEmptyForNoItem(): void
    {
        $file = new Horde_Dav_File(
            $this->registry,
            'myapp/file.txt',
            []
        );

        $props = $file->getProperties([]);

        $this->assertEmpty($props);
    }
}
