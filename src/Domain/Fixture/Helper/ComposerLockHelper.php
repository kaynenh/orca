<?php

namespace Acquia\Orca\Domain\Fixture\Helper;

use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaFixtureNotExistsException;
use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;

/**
 * Provides facilities for working with the fixture's composer.lock.
 */
class ComposerLockHelper {

  private const COMPOSER_LOCK = 'composer.lock';

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Helper\Config\ConfigLoader
   */
  private $configLoader;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Config\ConfigLoader $config_loader
   *   The config loader.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   */
  public function __construct(ConfigLoader $config_loader, FixturePathHandler $fixture_path_handler) {
    $this->configLoader = $config_loader;
    $this->fixture = $fixture_path_handler;
  }

  /**
   * Gets the packages data array.
   *
   * @return array
   *   The packages data.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function getPackages(): array {
    return $this->get('packages') ?? [];
  }

  /**
   * Gets a config value.
   *
   * @param string $key
   *   The key.
   *
   * @return mixed|null
   *   The config value.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  private function get(string $key) {
    $config = $this->loadFile();
    return $config->get($key);
  }

  /**
   * Loads the file.
   *
   * @return \Noodlehaus\Config
   *   The file as a config object.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  private function loadFile(): Config {
    if (!$this->fixture->exists()) {
      throw new OrcaFixtureNotExistsException('No fixture exists.');
    }
    if (!$this->fixture->exists(self::COMPOSER_LOCK)) {
      throw new OrcaFileNotFoundException('Fixture is missing composer.lock.');
    }

    try {
      $config = $this->configLoader->load($this->filePath(), new Json());
    }
    catch (OrcaParseError $e) {
      throw new OrcaParseError('Fixture composer.lock is corrupted.');
    }
    return $config;
  }

  /**
   * Gets the file path.
   *
   * @return string
   *   The file path.
   */
  private function filePath(): string {
    return $this->fixture->getPath(self::COMPOSER_LOCK);
  }

}
