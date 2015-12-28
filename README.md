# Google Analytics Referrer Spam Filter Tool

A console script written to create filters on your Google Analytics account so
that you can filter out all those unwanted Referrer Spam Bots.

This script connects through an API Project you create against your analytics
account.

The script is built using Symfony2's Console component and has a list of several
commands that can be used to help you.

## Installation

Installation requires that you download [Composer](https://getcomposer.org/), as
you need some of the components that come with that. After downloading Composer,
simply run the following command from commandline.

`composer global require generalredneck/ga-referrer-spam-filters "*"`

From here, you will need to watch the output of the command to figure out where
your User's Composer directory is located. Places to look are:

* ~/.composer/vendor/bin/garefspam
* %userprofile%/AppData/Roaming/Composer/vendor/bin/garefspam

Adding the vendor/bin folder to your $PATH Environment Variable would be a good
idea.

## Setup Google Analytics with Access

Here I'll give you a run down on how to set up your Google Analytics account
with access so that you can configure the application to connect to it. This is
an adaptation of [Google Developers Documentation][setup-1]

1. Create or select a project in the Google Developers Console and enable the
   api by navigating to this [wizard][setup-2]
2. Navigate to the Credentials Page.    
   <br />![Credentials Page][setupimg-1]<br />
3. Click "New Credentials" and select "Service Account Key"    
   <br />![New Credentials Menu][setupimg-2]<br />
4. Create a new Service Account, giving it the name ga-referral-spam. 
5. Select P12 from the Key Type.
6. Click Create. 
7. A file will be downloaded to your computer. This is the key garefspam will 
   need to run.    
   <br />![Downloaded Key][setupimg-3]<br />
8. Click on "Manage Service Accounts"
   <br />![Manage Service Accounts][setupimg-4]<br />
9. Grab the Email Address listed there for you service account and save it 
   somewhere you can grab it later. 
10. Log in to your Google Analytics Account 
11. Navigate to Admin 
12. Select the Account you wish to add these filters to from the "Account" 
    Dropdown
13. Click "User Mangement"     
    <br />![Analytics][setupimg-5]<br /> 
14. Paste the service account's email address you grabbed earlier in the 
    "Add permissions for:" dialog. 
15. Select "Edit" from the dropdown that says "Read & Analyze".
    
    **Note:** You are in fact giving my application rights to edit settings in
    your Google Analytics account, but the app does not have rights to modify 
    users in any capacity. 

16. Click Add.

[setup-1]: https://developers.google.com/analytics/devguides/reporting/core/v3/quickstart/service-php
[setup-2]: https://console.developers.google.com/flows/enableapi?apiid=analytics&credential=client_key

[setupimg-1]: http://content.screencast.com/users/talkitivewizard/folders/Jing/media/3802c447-6e3c-4d17-bd27-cae73b8168bc/2015-12-28_1146.png
[setupimg-2]: http://content.screencast.com/users/talkitivewizard/folders/Jing/media/015b4ff4-8351-44c0-92bc-9700d9fcde3d/2015-12-28_1149.png
[setupimg-3]: http://content.screencast.com/users/talkitivewizard/folders/Jing/media/72575922-63f9-4cda-be81-f9039ae605f1/2015-12-28_1155.png
[setupimg-4]: http://content.screencast.com/users/talkitivewizard/folders/Jing/media/3ba7aa4a-205c-4b6a-88a2-c80c1b8e28d0/2015-12-28_1203.png
[setupimg-5]: http://content.screencast.com/users/talkitivewizard/folders/Jing/media/e3af0721-08c6-483f-bcf5-4e4e3285d783/2015-12-28_1208.png

## Configuration

There are 2 methods of configuration

* config.yml command line switches

### Config.yml

This method is preferred if you are going to be running this tool on the same
account all the time.

**Note:** Keep in mind that if you have only one account, one web property id, 
and one view, the application can function on as little as the service account 
and key you set up for the API.

Simply put config.yml in the folder the package was downloaded to, usually one
of the following:

* ~/.composer/vendor/generalredneck/ga-referrer-spam-filters 
* %userprofile%/AppData/Roaming/Composer/vendor/generalredneck/ga-referrer-spam-filters

You can copy the [config.yml.dist][config-1] file as config.yml, but keep
in mind, you might not want to have all of the items. The most important one you
will need to set up is your service-email. See [config.yml.dist][config-1] for 
specifics about the different items.

[config-1]:https://github.com/generalredneck/ga-referrer-spam-filters/blob/master/config.yml.dist

### Switches

Using this form of configuration is best for when you have multiple accounts,
web properties, and/or views you want to work with.

The global switches are:

* `-e, --service-email[=SERVICE-EMAIL]` - The service email to use to connect to
* `-Google Analytics k, --key-location[=KEY-LOCATION]` - The p12 key file used
* `-to connect to Google Analytics

Use `garefspam` to get a list of commands you can run using this tool and the
expected switches. An example of this output is as follows:

    $ garefspam
    GA Referrer Spam Filters version 0.3


    Usage:
      command [options] [arguments]

    Options:
      -h, --help                           Display this help message
      -q, --quiet                          Do not output any message
      -V, --version                        Display this application version
          --ansi                           Force ANSI output
          --no-ansi                        Disable ANSI output
      -n, --no-interaction                 Do not ask any interactive question
      -e, --service-email[=SERVICE-EMAIL]  The service email to use to connect to Google Analytics [default: "ga-referral-spam@iam.gserviceaccount.com"]
      -k, --key-location[=KEY-LOCATION]    The p12 key file used to connect to Google Analytics [default: "C:\Users\Allan\workspace\spam\client_secrets.p12"]
      -v|vv|vvv, --verbose                 Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

    Available commands:
      help             Displays help for a command
      list             Lists commands
      listaccounts     List Accounts associated with the configured GA user
      listproperties   List GA Web Property Ids (UA-xxxxxxx-yy) associated with the configured GA account
      listviews        List GA views associated with the configured GA account and Web Property Id
      updategafilters  Update the specified Google Analytics view with filters to block referral spam from the domain list file
      updatespamlist   Update the referrer spam domains list.

## Usage

Once configured, you will need to run at the very least the following 2 commands

    $ garefspam updatespamlist 
    $ garefspam updatefilters

A sample session may look like so:

    $ garefspam updatespamlist
    +---------+----------------------------------+
    | Status  | Domain                           |
    +---------+----------------------------------+
    | Removed | aftermarket.7zap.com             |
    | Removed | bmw.afora.ru                     |
    | Removed | forum20.smailik.org              |
    | Removed | mini.7zap.com                    |
    | Removed | msk.afora.ru                     |
    | Removed | nissan.afora.ru                  |
    | Removed | spb.afora.ru                     |
    | Removed | toyota.7zap.com                  |
    | Removed | ╨╜╨░╤Ç╨║╨╛╨╝╨░╨╜╨╕╤Å.╨╗╨╡╤ç╨╡╨╜╨╕╨╡╨╜╨░╤Ç╨║╨╛╨╝╨░╨╜╨╕╨╕.com |
    | Added   | 7zap.com                         |
    | Added   | for-your.website                 |
    | Added   | onlinetvseries.me                |
    | Added   | smailik.org                      |
    | Added   | snip.to                          |
    | Added   | uptimechecker.com                |
    | Added   | ╨╗╨╡╤ç╨╡╨╜╨╕╨╡╨╜╨░╤Ç╨║╨╛╨╝╨░╨╜╨╕╨╕.com            |
    +---------+----------------------------------+
    Outputted list to C:\Users\Allan\workspace\spam\spammers.txt

    $ garefspam updategafilters
    No Account configured, but there is only one account available. Using 33703024:GeneralRedneck.com
    No property id configured, but there is only one available. Using UA-33703024-1:GeneralRedneck.com
    121 Spam Referral filters already exist.
    Updated Filter Spam Referral 002
    Updated Filter Spam Referral 003
    Updated Filter Spam Referral 013
    Updated Filter Spam Referral 014
    Updated Filter Spam Referral 015
    Updated Filter Spam Referral 016
    Updated Filter Spam Referral 017
    Updated Filter Spam Referral 018
    Updated Filter Spam Referral 039
    Updated Filter Spam Referral 040
    ...

## FAQ

To come.
