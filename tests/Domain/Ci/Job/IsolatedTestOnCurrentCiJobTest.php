<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentCiJob;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;

class IsolatedTestOnCurrentCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->envFacade = $this->prophesize(EnvFacade::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    parent::setUp();
  }

  protected function createJob(): AbstractCiJob {
    $env_facade = $this->envFacade->reveal();
    $process_runner = $this->processRunner->reveal();
    return new IsolatedTestOnCurrentCiJob($env_facade, $process_runner);
  }

  public function testBasicConfiguration(): void {
    $job = $this->createJob();

    self::assertEquals(DrupalCoreVersionEnum::CURRENT(), $job->getDrupalCoreVersion(), 'Declared the correct Drupal core version.');
  }

  public function testInstall(): void {
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--sut-only',
        '--core=CURRENT',
      ])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $job = $this->createJob();

    $this->runInstallPhase($job);
  }

  public function testInstallOverrideProfile(): void {
    $profile = 'example';
    $this->envFacade
      ->get('ORCA_FIXTURE_PROFILE')
      ->willReturn($profile);
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--sut-only',
        '--core=CURRENT',
        "--profile={$profile}",
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runInstallPhase($job);
  }

  public function testInstallOverrideProjectTemplate(): void {
    $project_template = 'example';
    $this->envFacade
      ->get('ORCA_FIXTURE_PROJECT_TEMPLATE')
      ->willReturn($project_template);
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--sut-only',
        '--core=CURRENT',
        "--project-template={$project_template}",
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runInstallPhase($job);
  }

  public function testScript(): void {
    $this->processRunner
      ->runOrca(['fixture:status'])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runOrca([
        'qa:automated-tests',
        "--sut={$this->validSutName()}",
        '--sut-only',
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runScriptPhase($job);
  }

}
