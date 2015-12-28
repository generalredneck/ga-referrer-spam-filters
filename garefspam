#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use GeneralRedneck\GaReferrerSpamFilters\Command\ListAccountsCommand;
use GeneralRedneck\GaReferrerSpamFilters\Command\ListPropertiesCommand;
use GeneralRedneck\GaReferrerSpamFilters\Command\ListViewsCommand;
use GeneralRedneck\GaReferrerSpamFilters\Command\UpdateGaFiltersCommand;
use GeneralRedneck\GaReferrerSpamFilters\Command\UpdateSpamListCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

$application = new Application();
$configValues = array();
$configDirectories = array(__DIR__);
try {
  $locator = new FileLocator($configDirectories);
  $configFile = $locator->locate('config.yml', null, true);
  $configValues = Yaml::parse(file_get_contents($configFile));
}
catch(\InvalidArgumentException $e) {
  // File was not found.
}

if (empty($configValues['key-location'])) {
  $configValues['key-location'] = __DIR__ . DIRECTORY_SEPARATOR . 'client_secrets.p12';
}
if (empty($configValues['domain-list-location'])) {
  $configValues['domain-list-location'] = __DIR__ . DIRECTORY_SEPARATOR . 'spammers.txt';
}

$application->config = $configValues;

// Add global Options to the Application
$application->getDefinition()->addOptions(array(
  new InputOption(
    '--service-email',
    '-e',
    InputOption::VALUE_OPTIONAL,
    'The service email to use to connect to Google Analytics',
    $configValues['service-email']
  ),
  new InputOption(
    '--key-location',
    '-k',
    InputOption::VALUE_OPTIONAL,
    'The p12 key file used to connect to Google Analytics',
    $configValues['key-location']
  )
));
$application->add(new ListAccountsCommand());
$application->add(new UpdateSpamListCommand());
$application->add(new UpdateGaFiltersCommand());
$application->add(new ListPropertiesCommand());
$application->add(new ListViewsCommand());
$application->run();
