# Yu-Gi-Oh API

**Yu-Gi-Oh API** is a complete solution backend to get all card from the popular TCG [Yu-Gi-Oh](https://www.yugioh-card.com/).

We use the [YGOPRODeck API](https://ygoprodeck.com/) to get all Card and Set information.

## Run the project

### Create .env file
You can take example with the `.env.example` file to create your own.

### Create the database with tables

Run `php bin/console doctrine:database:create` to have your empty database.

Run `php bin/console doctrine:schema:update --force --complete` to create all table with relation.

Change `$username` and `$password` value in `src/DataFixtures/User.php`

Finally, run `php bin/console doctrine:fixtures:load --append` to have some basic needed with an Admin ready to use.

Be aware that we also have a fixtures for testing purposes who's `UserTestFixtures`,
delete these two user in your database or rename the file by adding `.old` in the file name to ber avoided by doctrine.

### Get all Card first time

We're going to use the `app:import` command to get all Set and Card with theirs information but,
we need some cards to avoid server explosion when we're going to import all Card and Set the first time 
(13k Card & 1k Set for me).

So, we're going to launch the import multiple times per slice of 5K cards each to avoid server overload:

`php bin/console app:import --limit=5000 --no-dbygo-update`

In my case, 2 times is largely enough, next time the Import launches, we won't have too many new Entity problems.

### Set Google JSON Auth file for the Backup

You can use the Backup in the `src/Command` folder, but you need to have a Gmail account with a Google Drive access.

You need to create a Service Account and enable the Google Drive API in your console google cloud platform, if you need help you can go [here](https://github.com/googleapis/google-api-php-client/blob/main/docs/oauth-server.md).

After that, you need to create a folder named `Backup` at the root of your Drive `My Drive` and share with the Service Account email.

Download the auth.json file and add it to `var/google` folder of the project.

### Send log to Telegram

Be aware that only error & warning log in production will be sent to avoid too much spam.

You need first to set in your `.env` the var `SEND_LOG_TO_TELEGRAM` to `"TRUE"`.

You can send some logs to a Telegram chat room. You need first to create a bot and speak with him to initiate a chat room with you and the bot, more info [here](https://core.telegram.org/bots/tutorial#getting-ready).

After you create your bot, you need to send a message to your newly created bot from telegram.

After the message send you can go to `https://api.telegram.org/bot<YOUR_BOT_Token>/getUpdates` to get a JSON response.

You need to get the chat id who's in `result[0]["message"]["chat"]["id"]`.
You can now update your `env` file with the bot token, name and chat id.

### Prepare Docker

We prepare a Dockerfile to avoid installing all the dependencies needed to run the project, 
we take the `Europe/Paris` timezone so if you want to change you can set it in the `Dockerfile`.

We already named the container `yu-gi-oh-api`, but you can rename it in the `docker-compose.yaml`.


Run `docker-compose -f docker-compose.yml build` 
then `docker-compose -f docker-compose.yml up -d` to have your container ready-to-use.

### Install dependencies if not use of Docker

Just run `composer install` in the root of the project.

## Logger

You can see all logs in `var/log` directory of the project.

The Logger create text file with the nomenclature `YYYY-MM-DD_IS_CRON_LOG_LEVEL.txt`
(ex: `2024-03-21_cron_error.txt`, `2024-03-22_info.txt`).

We separate log from CRON from the project to be quickly findable.

Please be aware that if you activate the `Backup` cron, we delete old all logs file who's not created on the day the cron is launched. 

Errors related to aa non-existent route or an existing route but with a bad request method are not
taken into account, and we only display a JSONResponse with the documentation route.

## Crontab

Use the `cron.txt` file to help you with the implementation of various tasks such as Import or Backup in your cron daemon.

## Documentation

The documentation is available at the `/swagger` route

## UserToken

### What is it

The `UserToken` entity serve multiple purposes. 

It stores the unique token that the user is going to use for this specific session (or in many cases, machine like laptop, smartphone...).

It also takes some info from the server like the ip, user-agent, accepted encoding/language, geolocation (with the help of Maxmind see below) etc...

We take all that to get a fingerprint, usable in the futur to target ban some user who are trying to do bad behavior.

If you want to know more, check the method `_generateUserToken` in `src/Service/Tool/User/Auth.php`.

### Maxmind

We use Maxmind's GeoLite2 database which is a free database service where you put an ip and he finds a city/country/ASN.

Maxmind offer 3 databases, one for each purpose (city/country/asn) and do search in it but first, you need to sing up [here](https://www.maxmind.com/en/geolite2/signup).

Then you need to go to the License key page (you will see it in the Account category in your left after login).

You need to create a new license key where you can store it in your `.env` file in `MAXMIND_LICENSE_KEY` key, don't forget to put your account id in `MAXMIND_ACCOUNT_ID`.

After that you juste need to run the `GeoIpCheck` command with `php bin/console app:geo-ip` or leave your crontab do it for you ( see crontab section).

## Testing

### Package

We use the `symfony/test-pack` for Testing the project, it comes with PHPUnit.

Be aware that this package is not required in production but only in dev environment

### Initialization

If you want to test the project you must create a `.env.test` file who's a copy of your `.env` file
but, you need to change the `DATABASE_URL` value, usually the same database name but with the suffixe `-test`.

We need to clone the current database (fulfilled) to another; we can do it with the mysqldump command:
`mysqldump --host=127.0.0.1 --port=3306 --user=DB_USER --password=DB_PASSWORD yu-gi-oh-api | mysql -u DB_USER -p yu-gi-oh-api-test`.

Launch the `UserTestFixtures` to have a user and an admin as test purposes with the command
`php bin/console doctrine:fixtures:load --group=user-test --env=test --append`.

### Run Tests

After that, all you need is run the command `php bin/phpunit <directory> --process-isolation` at the root of the project,
where `<directory>` can be `test/Controller`, `test/Entity` or `test/Service`.

Be aware that some test WILL fail if you try to run it individually because they have dependencies, run `php bin/phpunit --list-groups` to find it.

You can also run `php bin/phpunit --process-isolation`to run all test at once.