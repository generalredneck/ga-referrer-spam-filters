<?php
namespace GeneralRedneck\GaReferrerSpamFilters\Command;

use GeneralRedneck\GaReferrerSpamFilters\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSpamListCommand extends Command
{
  protected function configure()
  {
    $this->setName('updatespamlist')
      ->setDescription('Update the referrer spam domains list.')
      ->addOption(
        'domain-list-location',
        'd',
        InputOption::VALUE_OPTIONAL,
        'Set the location to download the list of spam domains to.'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $domain_list_location = $input->getOption('domain-list-location');
    $domain_list_location = empty($domain_list_location) ? $this->getApplication()->config['domain-list-location'] : $domain_list_location;
    if (empty($domain_list_location)) {
      $output->writeln("<error>Please configure a domain list location.</error>");
      return 1;
    }
    $local_list = @file($domain_list_location);
    if (empty($local_list)) {
      $local_list = array();
    }
    // Get a copy of the list that is pulled own from
    // https://raw.githubusercontent.com/desbma/referer-spam-domains-blacklist/master/spammers.txt
    // to limit hot-linking to github and not require git to be installed.
    $remote_list = file('http://generalredneck.com/sites/default/files/static-content/garefspam/spammers.txt');
    $table = new Table($output);
    $table->setHeaders(array('Status', 'Domain'));
    $removed = array_diff($local_list, $remote_list);
    $added = array_diff($remote_list, $local_list);
    if (empty($removed) && empty($added)) {
      $output->writeln('Domain list is up to date.');
    }
    else {
      foreach($removed as $domain) {
        if (empty($domain)) {
          continue;
        }
        $table->addRow(array('<fg=red>Removed</>', trim($domain)));
      }
      foreach($added as $domain) {
        if (empty($domain)) {
          continue;
        }
        $table->addRow(array('<fg=green>Added</>', trim($domain)));
      }
      $table->render();
      file_put_contents($domain_list_location, $remote_list);

      $output->writeln('Outputted list to ' . $domain_list_location);
    }
  }
}
