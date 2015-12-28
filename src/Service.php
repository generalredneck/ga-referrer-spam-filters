<?php

namespace GeneralRedneck\GaReferrerSpamFilters;

class Service {

  protected static $service;
  protected static $service_account_email;
  protected static $key_file_location;
  protected $analytics;
  protected $client;

  public static function getService() {
    if (empty(self::$service)) {
      self::$service = new Service(self::$service_account_email, self::$key_file_location);
    }
    return self::$service;
  }

  public function __construct($service_account_email, $key_file_location) {
    self::$service_account_email = $service_account_email;
    self::$key_file_location = $key_file_location;
    self::$service = $this;
  }

  /**
   * Creates and returns the Analytics service object.
   */
  public function getGaService() {
    if (empty($this->analytics)) {
      // Create and configure a new client object.
      $this->client = new \Google_Client();
      $this->client->setApplicationName("GA Referral Spam Filters");
      $this->analytics = new \Google_Service_Analytics($this->client);

      var_dump(self::$key_file_location);
      var_dump(self::$service_account_email);
      // Read the generated p12 key.
      $key = file_get_contents(self::$key_file_location);
      $cred = new \Google_Auth_AssertionCredentials(
          self::$service_account_email,
          array(\Google_Service_Analytics::ANALYTICS_EDIT),
          $key
      );
      $this->client->setAssertionCredentials($cred);
      if($this->client->getAuth()->isAccessTokenExpired()) {
        $this->client->getAuth()->refreshTokenWithAssertion($cred);
      }
    }

    return $this->analytics;
  }
}
