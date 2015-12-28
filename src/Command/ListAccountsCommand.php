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
    $service = new Service(
      $input->getOption('service-email'),
      $input->getOption('key-location')
    );
    $analytics = $service->getGaService();
    $accounts = $analytics->management_accounts->listManagementAccounts();
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
