<?php
namespace Civi\Cv\Command;

use Civi\Cv\Util\Filesystem;
use Civi\Cv\Util\BootTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScriptCommand extends BaseCommand {

  use BootTrait;

  protected function configure() {
    $this
      ->setName('php:script')
      ->setAliases(array('scr'))
      ->setDescription('Execute a PHP script')
      ->addArgument('script', InputArgument::REQUIRED)
      ->addArgument('scriptArguments', InputArgument::IS_ARRAY, 'Optional arguments to pass to the script as $argv');
    $this->configureBootOptions();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $fs = new Filesystem();
    $origScript = $fs->toAbsolutePath($input->getArgument('script'));
    $scriptArguments = $input->getArgument('scriptArguments');

    $origCwd = getcwd();
    $this->boot($input, $output);
    $postCwd = getcwd();

    // Normal operation: Use the script path provided at input.
    if (file_exists($origScript)) {
      chdir($origCwd);
      $this->runScript($output, $origScript, $scriptArguments);
      return 0;
    }

    // Backward compat: Try script relative the post-boot CWD.
    $postScript = $fs->toAbsolutePath($input->getArgument('script'));
    if (file_exists($postScript)) {
      $output->getErrorOutput()->writeln("<comment>WARNING: Loaded script relative to CMS root -- which is deprecated. Script path should be (a) absolute or (b) relative to CWD.</comment>");
      chdir($postCwd);
      $this->runScript($output, $postScript, $scriptArguments);
      return 0;
    }

    $output->getErrorOutput()->writeln("<error>Failed to locate script: " . $input->getArgument('script') . "</error>");
    return 1;
  }

  /**
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param $script
   * @param array
   */
  protected function runScript(OutputInterface $output, $script, $scriptArguments) {
    $output->writeln("<info>[ScriptCommand]</info> Start \"$script\"", OutputInterface::VERBOSITY_DEBUG);
    // This puts the script arguments in the same variable scope as the script
    // so scripts can access arguments using $argv $argc
    $argv = $scriptArguments;
    $argc = count($argv);
    require $script;
    $output->writeln("<info>[ScriptCommand]</info> Finish \"$script\"", OutputInterface::VERBOSITY_DEBUG);
  }

}
