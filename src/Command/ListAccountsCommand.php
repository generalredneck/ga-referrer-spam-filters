<?php
namespace GeneralRedneck\GaReferrerSpamFilters\Command;

use GeneralRedneck\GaReferrerSpamFilters\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListAccountsCommand extends Command
{
  protected function configure()
  {
    $this->setName('listaccounts')
    ->setDescription('List Accounts associated with the configured GA user');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
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
    $accounts = $service->getGaAccounts();
    if (count($accounts->getItems()) > 0) {
      $table_data = array();
      foreach ($accounts as $account) {
        $table_data[] = array($account->id, $account->name);
      }
      $table = new Table($output);
      $table->setHeaders(array('ID', 'Name'))
        ->setRows($table_data);
      $table->render();
    }
    else {
      $output->writeln("<error>No accounts found for this user.</error>");
      return 1;
    }
  }
}
