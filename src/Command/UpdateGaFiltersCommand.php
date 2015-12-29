<?php
namespace GeneralRedneck\GaReferrerSpamFilters\Command;

use GeneralRedneck\GaReferrerSpamFilters\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateGaFiltersCommand extends Command
{
  protected function configure()
  {
    $this->setName('updategafilters')
      ->setDescription('Update the specified Google Analytics view with filters to block referral spam from the domain list file')
      ->addOption(
        'domain-list-location',
        'd',
        InputOption::VALUE_OPTIONAL,
        'Set the location to download the list of spam domains to.'
      )
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
      )
      ->addOption(
        'ga-view-id',
        'w',
        InputOption::VALUE_OPTIONAL,
        'Set the id of the view (profile) you wish to connect to via the account id. See listviews.'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $service_email = $input->getOption('service-email');
    $key_location = $input->getOption('key-location');
    $ga_account_id = $input->getOption('ga-account-id');
    $ga_property_id = $input->getOption('ga-property-id');
    $ga_view_id = $input->getOption('ga-view-id');
    $domain_list_location = $input->getOption('domain-list-location');
    $domain_list_location = empty($domain_list_location) ? $this->getApplication()->config['domain-list-location'] : $domain_list_location;

    if (empty($service_email)) {
      $this->outputError($output, 'A service-email must be configured.');
      return 1;
    }
    if (empty($key_location)) {
      $this->outputError($output, 'A key-location must be configured.');
      return 2;
    }
    if (!file_exists($key_location)) {
      $this->outputError($output, 'The file ' . $key_location . ' does not exist.');
      return 3;
    }
    $service = new Service(
      $input->getOption('service-email'),
      $input->getOption('key-location')
    );

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
          $this->outputError($output, "No Account configured and more than one account is associated with this service email. Configure an account id by passing --ga-account-id or modifying config.yml. See listaccounts for a full list of accounts.");
          return 4;
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
          $this->outputError($output, "No property id configured and more than one web property is associated with this service email and account id. Configure a web property id by passing --ga-property-id or modifying config.yml. See listproperties for a full list of web properties.");
          return 5;
        }
      }
    }
    if (empty($ga_view_id)) {
      if (!empty($this->getApplication()->config['ga-view-id'])) {
        $ga_view_id = $this->getApplication()->config['ga-view-id'];
      }
      else {
        $views = $service->getGaViews($ga_account_id, $ga_property_id)->getItems();
        if (count($views) == 1) {
          $output->writeln("<comment>No view id configured, but there is only one available. Using " . $views[0]->getId() . ":" .  $views[0]->name . " </comment>");
          $ga_view_id = $views[0]->getId();
        }
        else {
          $this->outputError($output, "No view id configured and more than one view is associated with this service email, account id, and property id. Configure a view id by passing --ga-view-id or modifying config.yml. See listviews for a full list of views.");
          return 6;
        }
      }
    }
    $filters = array();
    $filtersResults = $service->getGaFilters($ga_account_id);
    foreach ($filtersResults as $filter) {
      if (strpos($filter->name, 'Spam Referral') !== FALSE) {
        $filters[$filter->name] = $filter;
      }
    }
    $output->writeln(count($filters) . " Spam Referral filters already exist.");
    $spammers = file($domain_list_location);
    $charcount = 2;
    $regex = '(';
    $count = 1;
    foreach($spammers as $spammer) {
      $spammer = trim(str_replace('.', '\.', $spammer));
      $spammer_length = strlen($spammer);
      if (($charcount + $spammer_length + 1) > 255 || $spammer == end($spammers)) {
        $regex .= ')';
        /**
         * This request creates a new filter.
         */
        // Construct the filter expression object.
        $details = new \Google_Service_Analytics_FilterExpression();
        $details->setField("REFERRAL");
        $details->setMatchType("MATCHES");
        $details->setExpressionValue($regex);
        $details->setCaseSensitive(false);

        $name = "Spam Referral " . str_pad($count, 3, '0', STR_PAD_LEFT);
        if (empty($filters[$name])) {
          // Construct the filter and set the details.
          $filter = new \Google_Service_Analytics_Filter();
          $filter->setName($name);
          $filter->setType("EXCLUDE");
          $filter->setExcludeDetails($details);
          $filterResult = $service->getGaService()->management_filters->insert($ga_account_id, $filter);
          sleep(1);

          // TODO: we need to check to see if the filters are linked with the
          // specified view cause we may already have the filters but they just
          // not linked up.

          // Construct the filter reference.
          $filterRef = new \Google_Service_Analytics_FilterRef();
          $filterRef->setAccountId($ga_account_id);
          $filterRef->setId($filterResult->getId());
           // Construct the body of the request.
          $filterLink = new \Google_Service_Analytics_ProfileFilterLink();
          $filterLink->setFilterRef($filterRef);
          $service->getGaService()->management_profileFilterLinks->insert($ga_account_id, $ga_property_id, $ga_view_id, $filterLink);
          $output->writeln("Created Filter {$filter->name} on Profile $ga_view_id");
          sleep(1);
        }
        else {
          $filter = $filters[$name];
          // TODO: we need to check to see what other views we are updateing by
          // updating this filter.
          if ($filter->getExcludeDetails()->getExpressionValue() != $details->getExpressionValue()) {
            $filter->setType("EXCLUDE");
            $filter->setExcludeDetails($details);
            $filterResult = $service->getGaService()->management_filters->update($ga_account_id, $filter->id, $filter);
            $output->writeln("Updated Filter {$filter->name}");
            sleep(1);
          }
          else {
            "Skipped Filter $name because it is the same as what is in GA.\n";
          }
        }
        $count++;
        $regex = '(';
        $charcount = 2;
      }
      if ($charcount > 2) {
        $regex .= '|';
        $charcount++;
      }
      $regex .= $spammer;
      $charcount += $spammer_length;
    }
  }
  // TODO: split this out into a parent class and convert all errors over to it.
  function outputError($output, $message, $title = 'Error') {
    $errorMessages = array_merge(array('[ ' . $title . ' ]', ''), explode("\n", wordwrap($message, 75, "\n", true)));
    $formattedBlock = $this->getHelper('formatter')->formatBlock($errorMessages, 'error', true);
    $output->writeln($formattedBlock);
  }
}
