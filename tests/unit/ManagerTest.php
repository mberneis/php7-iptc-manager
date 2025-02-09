<?php

declare(strict_types=1);

namespace iBudasov\Iptc\Tests\Unit;

use iBudasov\Iptc\Domain\Binary;
use iBudasov\Iptc\Domain\FileSystem;
use iBudasov\Iptc\Domain\Image;
use iBudasov\Iptc\Domain\Tag;
use iBudasov\Iptc\Manager;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var FileSystem|MockInterface
     */
    private $fileSystemMock;

    /**
     * @var Image|MockInterface
     */
    private $imageMock;

    /**
     * @var Binary|MockInterface
     */
    private $binaryMock;

    protected function setUp(): void
    {
        $this->fileSystemMock = \Mockery::mock(FileSystem::class);
        $this->imageMock = \Mockery::mock(Image::class);
        $this->binaryMock = \Mockery::mock(Binary::class);

        $this->manager = new Manager(
            $this->fileSystemMock,
            $this->imageMock,
            $this->binaryMock
        );
    }

    public function testThatManagerCanBeStaticallyCreated(): void
    {
        $manager = Manager::create();
        self::assertInstanceOf(Manager::class, $manager);
    }

    /** @doesNotPerformAssertions */
    public function testThatPathToFileCanBeSetWhenFileTypeIsCorrect(): void
    {
        $pathToFile = '/tmp/test.jpg';

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();

        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn(\json_decode('{"2#001":["\u001b%G"],"2#000":["\u0000\u0004"],"2#055":["20180328"],"2#060":["124126"],"2#062":["20180328"],"2#063":["124126"],"2#080":["IGOR BUDASOV"],"2#025":["norway","scandinavia","spring","2018","nordic","outdoor","relax","beautiful","tourism","hiking","walking","Cl lofoten islands","louds","cold","frost","Clououdy","clouds","sky","mountain","nature","background","cloudy","hillside","distance","highland"],"2#120":["Spring in Norway: a large mountain in the background"]}', true));

        $this->manager->loadFile($pathToFile);
    }

    public function testThatExceptionIsThrownWhenFileExtensionIsNotSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $pathToFile = '/tmp/test.wrong';

        $this->manager->loadFile($pathToFile);
    }

    public function testThatExceptionIsThrownWhenFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $pathToFile = '/tmp/test.jpg';

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnFalse();

        $this->manager->loadFile($pathToFile);
    }

    public function testThatAllTheExistingTagsCanBeParsedAndReturned(): void
    {
        $pathToFile = '/tmp/test.jpg';

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();

        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([
                new Tag('025', ['norway', 'scandinavia', 'spring']),
            ]);

        $this->manager->loadFile($pathToFile);

        self::assertIsArray($this->manager->getTags());
        self::assertInstanceOf(Tag::class, \current($this->manager->getTags()));
    }

    public function testThatKeywordTagsCanBeReturned(): void
    {
        $pathToFile = '/tmp/test.jpg';

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();

        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([
                new Tag('025', ['norway', 'scandinavia', 'spring']),
            ]);

        $this->manager->loadFile($pathToFile);

        self::assertEquals(
            'norway, scandinavia, spring',
            $this->manager->getTag(Tag::KEYWORDS)
        );
    }

    public function testThatDescriptionTagsCanBeReturned(): void
    {
        $pathToFile = '/tmp/test.jpg';

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();

        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([
                new Tag('120', ['some description']),
            ]);

        $this->manager->loadFile($pathToFile);

        self::assertEquals(
            'some description',
            $this->manager->getTag(Tag::DESCRIPTION)
        );
    }

    public function testThatAuthorTagsCanBeReturned(): void
    {
        $pathToFile = '/tmp/test.jpg';

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();

        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([
                new Tag('080', ['AUTHOR NAME']),
            ]);

        $this->manager->loadFile($pathToFile);

        self::assertEquals(
            'AUTHOR NAME',
            $this->manager->getTag(Tag::AUTHOR)
        );
    }

    public function testThatNullIsReturnedWhenThereIsNoSuchATag(): void
    {
        $pathToFile = '/tmp/test.jpg';

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();

        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([]);

        $this->manager->loadFile($pathToFile);

        self::assertNull(
            $this->manager->getTag(Tag::AUTHOR)
        );
    }

    /**
     * @doesNotPerformAssertions
     *
     * @throws \Exception
     */
    public function testThatTagsAreEncodedAndWrittenToPictureFile(): void
    {
        $pathToFile = '/tmp/test.jpg';
        $tag = new Tag('080', ['AUTHOR NAME']);

        $this->imageMock->shouldReceive('writeIptcTags')
            ->once()
            ->with($pathToFile, 'binary-string')
            ->andReturn('updated-binary-string');

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();

        $this->binaryMock->shouldReceive('createBinaryStringFromTag')
            ->once()
            ->with($tag)
            ->andReturn('binary-string');

        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([$tag]);

        $this->fileSystemMock->shouldReceive('deleteFile')
            ->once()
            ->with($pathToFile);

        $this->fileSystemMock->shouldReceive('createFileWithBinaryContent')
            ->once()
            ->with($pathToFile, 'updated-binary-string')
            ->andReturnTrue();

        $this->manager->loadFile($pathToFile);

        $this->manager->write();
    }

    /**
     * @throws \Exception
     */
    public function testThatExceptionIsThrownIfCanNotCreateANewBinaryStringWithNewTags(): void
    {
        $pathToFile = '/tmp/test.jpg';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Can not write IPTC tags to file: '.$pathToFile);

        $tag = new Tag('080', ['AUTHOR NAME']);

        $this->imageMock->shouldReceive('writeIptcTags')
            ->once()
            ->with($pathToFile, 'binary-string')
            ->andReturn('');

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();

        $this->binaryMock->shouldReceive('createBinaryStringFromTag')
            ->once()
            ->with($tag)
            ->andReturn('binary-string');

        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([$tag]);

        $this->manager->loadFile($pathToFile);

        $this->manager->write();
    }

    public function testThatTagCanBeAdded(): void
    {
        $pathToFile = '/tmp/test.jpg';
        $tag = new Tag('080', ['AUTHOR NAME']);
        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();
        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([]);

        $this->manager->loadFile($pathToFile);

        $this->manager->addTag($tag);

        self::assertEquals('AUTHOR NAME', $this->manager->getTag('080'));
    }

    public function testThatTagCanBeDeleted(): void
    {
        $pathToFile = '/tmp/test.jpg';
        $tag = new Tag('080', ['AUTHOR NAME']);
        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();
        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([$tag]);

        $this->manager->loadFile($pathToFile);

        $this->manager->deleteTag('080');

        self::assertNull($this->manager->getTag('080'));
    }

    public function testThatExceptionIsThrownWhenTryingToDeleteNonExistingTag(): void
    {
        $pathToFile = '/tmp/test.jpg';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Can not delete tag with code \'080\', because it does not exist in file '.$pathToFile
        );

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();
        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([]);

        $this->manager->loadFile($pathToFile);

        $this->manager->deleteTag('080');
    }

    public function testThatExceptionIsThrownWhenTryingToOverwriteExistingTag(): void
    {
        $pathToFile = '/tmp/test.jpg';
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Trying to add tag with code \'080\' but it already exists in file '.$pathToFile
        );
        $tag = new Tag('080', ['AUTHOR NAME']);

        $this->fileSystemMock->shouldReceive('isFile')
            ->once()
            ->with($pathToFile)
            ->andReturnTrue();
        $this->imageMock->shouldReceive('getIptcTags')
            ->once()
            ->with($pathToFile)
            ->andReturn([$tag]);

        $this->manager->loadFile($pathToFile);
        $this->manager->addTag($tag);
    }
}
