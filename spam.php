<?php
require_once './vendor/autoload.php';

function getService()
{
  // Creates and returns the Analytics service object.

  // Use the developers console and replace the values with your
  // service account email, and relative location of your key file.
  $service_account_email = 'ga-referral-spam@arched-canyon-117217.iam.gserviceaccount.com';
  $key_file_location = 'client_secrets.p12';

  // Create and configure a new client object.
  $client = new Google_Client();
  $client->setApplicationName("GA Referral Spam Filters");
  $analytics = new Google_Service_Analytics($client);

  // Read the generated client_secrets.p12 key.
  $key = file_get_contents($key_file_location);
  $cred = new Google_Auth_AssertionCredentials(
      $service_account_email,
      array(Google_Service_Analytics::ANALYTICS_EDIT),
      $key
  );
  $client->setAssertionCredentials($cred);
  if($client->getAuth()->isAccessTokenExpired()) {
    $client->getAuth()->refreshTokenWithAssertion($cred);
  }

  return $analytics;
}

function createFilter($account, $filter_details) {

}
/**
 * This request creates a new filter.
 */

$analytics = getService();
$accounts = $analytics->management_accounts->listManagementAccounts();
if (count($accounts->getItems()) > 0) {
  $items = $accounts->getItems();
  $firstAccountId = $items[0]->getId();
}
else {
  throw new Exception('No accounts found for this user.');
}

 // Get the list of properties for the authorized user.
$properties = $analytics->management_webproperties->listManagementWebproperties($firstAccountId);
if (count($properties->getItems()) > 0) {
  $items = $properties->getItems();
  $firstPropertyId = $items[0]->getId();
}
else{
  throw new Exception('No properties found for this user.');
}

// Get the list of views (profiles) for the authorized user.
$profiles = $analytics->management_profiles->listManagementProfiles($firstAccountId, $firstPropertyId);
if (count($profiles->getItems()) > 0) {
  $items = $profiles->getItems();
  // Return the first view (profile) ID.
  $firstProfileId = $items[0]->getId();
}
else {
  throw new Exception('No profiles found for this user');
}

$filters = array();
try {
  $filtersResults = $analytics->management_filters
      ->listManagementFilters($firstAccountId);

} catch (apiServiceException $e) {
  print 'There was an Analytics API service error '
      . $e->getCode() . ':' . $e->getMessage();
  exit;

} catch (apiException $e) {
  print 'There was a general API error '
      . $e->getCode() . ':' . $e->getMessage();
  exit;
}
foreach ($filtersResults as $filter) {
  if (strpos($filter->name, 'Spam Referral') !== FALSE) {
    $filters[$filter->name] = $filter;
  }
}
var_dump(array_keys($filters));

$spammers = file('spammers.txt');

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
    $details = new Google_Service_Analytics_FilterExpression();
    $details->setField("REFERRAL");
    $details->setMatchType("MATCHES");
    $details->setExpressionValue($regex);
    $details->setCaseSensitive(false);

    $name = "Spam Referral " . str_pad($count, 3, '0', STR_PAD_LEFT);
    if (empty($filters[$name])) {
      try {
        // Construct the filter and set the details.
        $filter = new Google_Service_Analytics_Filter();
        $filter->setName($name);
        $filter->setType("EXCLUDE");
        $filter->setExcludeDetails($details);
        $filterResult = $analytics->management_filters->insert($firstAccountId, $filter);
        sleep(1);
      } catch (apiServiceException $e) {
        print 'There was an Analytics API service error '
            . $e->getCode() . ':' . $e->getMessage();
        exit;
      } catch (apiException $e) {
        print 'There was a general API error '
            . $e->getCode() . ':' . $e->getMessage();
        exit;
      }
      try {
        // Construct the filter reference.
        $filterRef = new Google_Service_Analytics_FilterRef();
        $filterRef->setAccountId($firstAccountId);
        $filterRef->setId($filterResult->getId());

         // Construct the body of the request.
        $filterLink = new Google_Service_Analytics_ProfileFilterLink();
        $filterLink->setFilterRef($filterRef);
        $analytics->management_profileFilterLinks->insert($firstAccountId, $firstPropertyId, $firstProfileId, $filterLink);
        echo "Created Filter {$filter->name} on Profile $firstProfileId\n";
        sleep(1);
      } catch (apiServiceException $e) {
        print 'There was an Analytics API service error '
            . $e->getCode() . ':' . $e->getMessage();
        exit;
      } catch (apiException $e) {
        print 'There was a general API error '
            . $e->getCode() . ':' . $e->getMessage();
        exit;
      }
    }
    else {
      $filter = $filters[$name];
      if ($filter->getExcludeDetails()->getExpressionValue() != $details->getExpressionValue()) {
        try {
          $filter->setType("EXCLUDE");
          $filter->setExcludeDetails($details);
          $filterResult = $analytics->management_filters->update($firstAccountId, $filter->id, $filter);
          echo "Updated Filter {$filter->name}\n";
          sleep(1);
        } catch (apiServiceException $e) {
          print 'There was an Analytics API service error '
              . $e->getCode() . ':' . $e->getMessage();
          exit;
        } catch (apiException $e) {
          print 'There was a general API error '
              . $e->getCode() . ':' . $e->getMessage();
          exit;
        }
      }
      else {
        echo "Skipped Filter $name because it is the same as what is in GA.\n";
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
