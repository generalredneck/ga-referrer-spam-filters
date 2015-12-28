<?php
namespace GeneralRedneck\GaReferrerSpamFilters\Command;

use GeneralRedneck\GaReferrerSpamFilters\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListViewsCommand extends Command
{
  protected function configure()
  {
    $this->setName('listviews')
      ->setDescription('List GA views associated with the configured GA account and Web Property Id')
      ->addOption(
        'ga-account-id',
        'a',
        InputOption::VALUE_OPTIONAL,
        'Set the id of the account you wish to connect to via the service email. See listaccounts.'
      )
      ->addOption(
        'ga-property-id',
        'p',
        InputOption::VALUE_OPTIONAL,
        'Set the id of the web property you wish to connect to via the account id. See listproperties.'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $ga_account_id = $input->getOption('ga-account-id');
    $ga_property_id = $input->getOption('ga-property-id');
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
    if (empty($ga_property_id)) {
      if (!empty($this->getApplication()->config['ga-property-id'])) {
        $ga_property_id = $this->getApplication()->config['ga-property-id'];
      }
      else {
        $properties = $service->getGaProperties($ga_account_id)->getItems();
        if (count($properties) == 1) {
          $output->writeln("<comment>No property id configured, but there is only one available. Using " . $properties[0]->getId() . ":" .  $properties[0]->name . " </comment>");
          $ga_property_id = $properties[0]->getId();
        }
        else {
          $output->writeln("<error>No property id configured and more than one web property is associated with this service email and account id. Configure a web property id by passing --ga-property-id or modifying config.yml. See listproperties for a full list of web properties.</error>");
        }
      }
    }
    $views = $service->getGaViews($ga_account_id, $ga_property_id);
    if (count($views->getItems()) > 0) {
      $table_data = array();
      foreach ($views as $view) {
        $table_data[] = array($view->id, $view->name);
      }
      $table = new Table($output);
      $table->setHeaders(array('ID', 'Name'))
        ->setRows($table_data);
      $table->render();
    }
    else {
      $output->writeln("<error>No views found for the specified account or web property id.</error>");
      return 1;
    }
  }
}
