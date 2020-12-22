<?php

namespace Acquia\Orca\Helper\Config;

use Acquia\Orca\Exception\OrcaDirectoryNotFoundException;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaParseError;
use Exception;
use Noodlehaus\Config;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundException;
use Noodlehaus\Exception\ParseException as NoodlehausParseException;
use Noodlehaus\Parser\ParserInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Loads configuration files.
 *
 * The sole purpose of this class is to make \Noodlehaus\Config an injectable
 * dependency.
 */
class ConfigLoader {

  /**
   * A cache of config files keyed by path.
   *
   * @var \Noodlehaus\Config[]
   */
  private $cache = [];

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The config file parser.
   *
   * @var \Noodlehaus\Parser\ParserInterface|null
   */
  private $parser;

  /**
   * The path to the config file.
   *
   * @var string
   */
  protected $path;

  /**
   * Whether or not to enable loading config from a string.
   *
   * @var bool
   */
  protected $string = FALSE;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   */
  public function __construct(Filesystem $filesystem) {
    $this->filesystem = $filesystem;
  }

  /**
   * Loads configuration.
   *
   * @param string $path
   *   The filename of the configuration file.
   * @param \Noodlehaus\Parser\ParserInterface|null $parser
   *   The config file parser.
   *
   * @return \Noodlehaus\Config
   *   A config object.
   *
   * @throws \Acquia\Orca\Exception\OrcaDirectoryNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaException
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  public function load(string $path, ?ParserInterface $parser = NULL): Config {
    if (!empty($this->cache[$path])) {
      return $this->cache[$path];
    }

    $this->path = $path;
    $this->parser = $parser;
    $this->assertDirectoryExists();
    $this->cache[$path] = $this->createConfig();
    return $this->cache[$path];
  }

  /**
   * Asserts that the config directory exists.
   *
   * @throws \Acquia\Orca\Exception\OrcaDirectoryNotFoundException
   */
  protected function assertDirectoryExists(): void {
    $dir_path = $this->getDirPath();
    if (!$this->filesystem->exists($dir_path)) {
      throw new OrcaDirectoryNotFoundException("SUT is absent from expected location: {$dir_path}");
    }
  }

  /**
   * Gets the directory path from the given config file path.
   *
   * @return string
   *   The parent directory path.
   */
  private function getDirPath(): string {
    $parts = explode(DIRECTORY_SEPARATOR, $this->path);
    array_pop($parts);
    return implode(DIRECTORY_SEPARATOR, $parts);
  }

  /**
   * Creates the config object.
   *
   * @return \Noodlehaus\Config
   *   The config object.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  protected function createConfig(): Config {
    try {
      return $this->loadConfig();
    }
    catch (NoodlehausFileNotFoundException $e) {
      throw new OrcaFileNotFoundException($e->getMessage());
    }
    catch (NoodlehausParseException $e) {
      throw new OrcaParseError($e->getMessage());
    }
    catch (Exception $e) {
      throw new OrcaException($e->getMessage());
    }
  }

  /**
   * Actually loads the config object from the system.
   *
   * This method is extracted exclusively for testability.
   *
   * @return \Noodlehaus\Config
   *   The config object.
   */
  protected function loadConfig(): Config {
    return new Config($this->path, $this->parser, $this->string);
  }

}
