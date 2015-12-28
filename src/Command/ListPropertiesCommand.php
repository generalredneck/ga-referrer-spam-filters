<?php
namespace GeneralRedneck\GaReferrerSpamFilters\Command;

use GeneralRedneck\GaReferrerSpamFilters\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListPropertiesCommand extends Command
{
  protected function configure()
  {
    $this->setName('listproperties')
      ->setDescription('List GA Web Property Ids (UA-xxxxxxx-yy) associated with the configured GA account')
      ->addOption(
        'ga-account-id',
        'a',
        InputOption::VALUE_OPTIONAL,
        'Set the id of the account you wish to connect to via the service email. See listaccounts.'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $ga_account_id = $input->getOption('ga-account-id');
    $service_email = $input->getOption('service-email');
    $key_location = $input->getOption('key-location');
    if (empty($service_email)) {
      $output->writeln('<error>A service-email must be configured.</error>');
      return 1;
    }
    if (empty($key_location)) {
      $output->writeln('<error>A key-location must be configured.</error>');
      return 2;
    }
    if (!file_exists($key_location)) {
      $output->writeln('<error>The file ' . $key_location . ' does not exist.');
      return 3;
    }
    $service = new Service($service_email, $key_location);
    if (empty($ga_account_id)) {
      if (!empty($this->getApplication()->config['ga-account-id'])) {
        $ga_account_id = $this->getApplication()->config['ga-account-id'];
      }
      else {
        $accounts = $service->getGaAccounts()->getItems();
        if (count($accounts) == 1) {
          $output->writeln("<comment>No Account configured, but there is only one account available. Using " . $accounts[0]->getId() . ":" .  $accounts[0]->name . " </comment>");
          $ga_account_id = $accounts[0]->getId();
        }
        else {
          $output->writeln("<error>No Account configured and more than one account is associated with this service email. Configure an account id by passing --ga-account-id or modifying config.yml. See listaccounts for a full list of accounts.</error>");
        }
      }
    }
    $properties = $service-> getGaProperties($ga_account_id);
    if (count($properties->getItems()) > 0) {
      $table_data = array();
      foreach ($properties as $property) {
        $table_data[] = array($property->id, $property->name);
      }
      $table = new Table($output);
      $table->setHeaders(array('ID', 'Name'))
        ->setRows($table_data);
      $table->render();
    }
    else {
      $output->writeln("<error>No web property ids found for this user.</error>");
      return 1;
    }
  }
}
