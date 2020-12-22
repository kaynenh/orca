<?php

namespace Acquia\Orca\Tests\Domain\Fixture\Helper;

use Acquia\Orca\Domain\Fixture\Helper\ComposerLockHelper;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaFixtureNotExistsException;
use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Helper\Config\ConfigLoader|\Prophecy\Prophecy\ObjectProphecy $configLoader
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 */
class ComposerLockHelperTest extends TestCase {

  private const COMPOSER_LOCK = 'composer.lock';

  protected function setUp(): void {
    $json = json_encode([]);
    $config = new Config($json, new Json(), TRUE);
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->configLoader
      ->load(Argument::any())
      ->willReturn($config);
    $this->configLoader
      ->load(Argument::any(), Argument::any())
      ->willReturn($config);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture
      ->exists()
      ->willReturn(TRUE);
    $this->fixture
      ->exists(self::COMPOSER_LOCK)
      ->willReturn(TRUE);
    $this->fixture
      ->getPath(Argument::any())
      ->willReturnArgument();
  }

  private function createComposerLockHelper(): ComposerLockHelper {
    $config_loader = $this->configLoader->reveal();
    $fixture = $this->fixture->reveal();
    return new ComposerLockHelper($config_loader, $fixture);
  }

  public function testGetNoFixture(): void {
    $this->fixture
      ->exists()
      ->willReturn(FALSE);
    $this->expectException(OrcaFixtureNotExistsException::class);
    $composer_lock = $this->createComposerLockHelper();

    $composer_lock->getPackages();
  }

  public function testGetNoComposerLock(): void {
    $this->fixture
      ->exists(self::COMPOSER_LOCK)
      ->willReturn(FALSE);
    $this->expectException(OrcaFileNotFoundException::class);
    $composer_lock = $this->createComposerLockHelper();

    $composer_lock->getPackages();
  }

  public function testGetInvalidComposerLock(): void {
    $this->configLoader
      ->load(self::COMPOSER_LOCK, new Json())
      ->willThrow(OrcaParseError::class);
    $this->expectException(OrcaParseError::class);
    $composer_lock = $this->createComposerLockHelper();

    $composer_lock->getPackages();
  }

  public function testGetSuccessful(): void {
    $config = $this->prophesize(Config::class);
    $expected = ['test' => ['example']];
    $config->get('packages')
      ->shouldBeCalledOnce()
      ->willReturn($expected);
    $this->configLoader
      ->load(self::COMPOSER_LOCK, new Json())
      ->willReturn($config);
    $composer_lock = $this->createComposerLockHelper();

    $actual = $composer_lock->getPackages();

    self::assertSame($expected, $actual);
  }

}
