<?php

declare(strict_types=1);

namespace Horde\Dav\Test\Unit;

use Horde_Dav_Collection;
use Horde_Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sabre\DAV\Xml\Property\GetLastModified;

#[CoversClass(Horde_Dav_Collection::class)]
class CollectionTest extends TestCase
{
    private Horde_Registry $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Horde_Registry::class);
    }

    private function makeCollection(
        string $path = 'myapp/folder',
        array $item = [],
    ): Horde_Dav_Collection {
        return new Horde_Dav_Collection(
            $path,
            $item,
            $this->registry,
            '/usr/share/misc/magic'
        );
    }

    public function testGetNameReturnsBasename(): void
    {
        $collection = $this->makeCollection('myapp/documents');

        $this->assertSame('documents', $collection->getName());
    }

    public function testGetNameFromNestedPath(): void
    {
        $collection = $this->makeCollection('myapp/deep/nested/folder');

        $this->assertSame('folder', $collection->getName());
    }

    public function testGetLastModifiedReturnsModifiedTimestamp(): void
    {
        $collection = $this->makeCollection('myapp/folder', [
            'modified' => 1700000000,
            'created' => 1699000000,
        ]);

        $this->assertSame(1700000000, $collection->getLastModified());
    }

    public function testGetLastModifiedFallsBackToCreated(): void
    {
        $collection = $this->makeCollection('myapp/folder', [
            'created' => 1699000000,
        ]);

        $this->assertSame(1699000000, $collection->getLastModified());
    }

    public function testGetLastModifiedReturnsNullWhenNoTimestamps(): void
    {
        $collection = $this->makeCollection('myapp/folder', []);

        $this->assertNull($collection->getLastModified());
    }

    public function testUpdatePropertiesReturnsFalse(): void
    {
        $collection = $this->makeCollection();

        $this->assertFalse($collection->updateProperties([]));
    }

    public function testGetPropertiesMapsToWebdavProperties(): void
    {
        $collection = $this->makeCollection('myapp/folder', [
            'contentlength' => 0,
            'contentype' => 'httpd/unix-directory',
            'etag' => '"dir-etag"',
            'owner' => 'admin',
            'read-only' => false,
            'displayname' => 'My Folder',
        ]);

        $props = $collection->getProperties([]);

        $this->assertSame(0, $props['{DAV:}getcontentlength']);
        $this->assertSame('httpd/unix-directory', $props['{DAV:}getcontenttype']);
        $this->assertSame('"dir-etag"', $props['{DAV:}getetag']);
        $this->assertSame('admin', $props['{DAV:}owner']);
        $this->assertFalse($props['{http://sabredav.org/ns}read-only']);
        $this->assertSame('My Folder', $props['{DAV:}displayname']);
    }

    public function testGetPropertiesIncludesLastModified(): void
    {
        $collection = $this->makeCollection('myapp/folder', [
            'modified' => 1700000000,
        ]);

        $props = $collection->getProperties([]);

        $this->assertInstanceOf(
            GetLastModified::class,
            $props['{DAV:}getlastmodified']
        );
    }

    public function testGetPropertiesFallsBackToNameForDisplayname(): void
    {
        $collection = $this->makeCollection('myapp/folder', [
            'name' => 'Folder Name',
        ]);

        $props = $collection->getProperties([]);

        $this->assertSame('Folder Name', $props['{DAV:}displayname']);
    }

    public function testGetPropertiesReturnsEmptyForNoItem(): void
    {
        $collection = $this->makeCollection('myapp/folder', []);

        $props = $collection->getProperties([]);

        $this->assertEmpty($props);
    }
}
