# Yu-Gi-Oh API

**Yu-Gi-Oh API** is a complete backend solution to get all cards from the popular TCG [Yu-Gi-Oh](https://www.yugioh-card.com/).

We use the [YGOPRODeck API](https://ygoprodeck.com/) to get all Card and Set information.

## Run the project

### Create .env file
You can take the `.env.example` file as a template to create your own.

### Create the database with tables

Run `php bin/console doctrine:database:create` to have your empty database.

Run `php bin/console doctrine:schema:update --force --complete` to create all tables with relations.

Change `$username` and `$password` values in `src/DataFixtures/User.php`

Finally, run `php bin/console doctrine:fixtures:load --append` to have some basic needed data with an Admin ready to use.

Be aware that we also have fixtures for testing purposes called `UserTestFixtures`.
Delete these two users in your database or rename the file by adding `.old` to the filename to prevent them from being loaded by doctrine.

### Get all Cards first time

We're going to use the `app:import` command to get all Sets and Cards with their information. However,
we need to import the data in chunks to avoid server overload when importing all Cards and Sets for the first time
(approximately 13k Cards & 1k Sets).

So, we're going to launch the import multiple times with slices of 5K cards each to avoid server overload:

`php bin/console app:import --limit=5000 --no-dbygo-update`

In my case, 2 iterations are more than enough. For subsequent imports, we won't face issues with too many new entities.

### OCG to TCG Card Converter

The `app:ocg-tcg-converter` command allows you to convert OCG (Oriental Card Game) cards to their TCG (Trading Card Game) equivalents. This process:
1. Identifies OCG cards in the database
2. Finds their TCG equivalents
3. Replaces all references to the OCG card with the TCG version
4. Removes the OCG card data

For the first run, it's recommended to process cards in batches to avoid overloading your server, similar to the initial card import. Each card processed will have its `isMaybeOCG` flag set to `false` if it's not an OCG card, ensuring it won't be processed again in future runs.

To run the converter with a limit:
```
php bin/console app:ocg-tcg-converter --limit=200
```

Run this multiple times with appropriate limits until you've processed the majority of your cards. After this initial processing, subsequent cron job executions will be much faster as they'll only need to check newly added cards.

You can also check a specific card by its YGO ID:
```
php bin/console app:ocg-tcg-converter --idYGO=12345
```

This progressive approach helps maintain a clean database focused on TCG cards which are more relevant for most users, while ensuring efficient resource usage over time.
### Set Google JSON Auth file for the Backup

You can use the Backup functionality in the `src/Command` folder, but you need to have a Gmail account with Google Drive access.

You need to create a Service Account and enable the Google Drive API in your Google Cloud Platform console. If you need help, you can refer to [this guide](https://github.com/googleapis/google-api-php-client/blob/main/docs/oauth-server.md).

After that, you need to create a folder named `Backup` at the root of your Drive `My Drive` and share it with the Service Account email.

Download the auth.json file and add it to the `var/google` folder of the project.

### Send logs to Telegram

Be aware that only error & warning logs in production will be sent to avoid too much spam.

You need to first set the `SEND_LOG_TO_TELEGRAM` variable to `"TRUE"` in your `.env` file.

You can send logs to a Telegram chat room. First, you need to create a bot and interact with it to initiate a chat room between you and the bot. More info [here](https://core.telegram.org/bots/tutorial#getting-ready).

After creating your bot, send a message to your newly created bot from Telegram.

Then go to `https://api.telegram.org/bot<YOUR_BOT_Token>/getUpdates` to get a JSON response.

You need to get the chat id from `result[0]["message"]["chat"]["id"]`.
You can now update your `.env` file with the bot token, name, and chat id.

### Prepare Docker

We've prepared a Dockerfile to avoid installing all the dependencies needed to run the project.
We use the `Europe/Paris` timezone, which you can change in the `Dockerfile` if needed.

We've already named the container `yu-gi-oh-api`, but you can rename it in the `docker-compose.yaml` file.

Run `docker-compose -f docker-compose.yml build`
then `docker-compose -f docker-compose.yml up -d` to have your container ready to use.

### Install dependencies if not using Docker

Just run `composer install` in the root of the project.

## Logger

You can see all logs in the `var/log` directory of the project.

The Logger creates text files with the nomenclature `YYYY-MM-DD_IS_CRON_LOG_LEVEL.txt`
(e.g. `2024-03-21_cron_error.txt`, `2024-03-22_info.txt`).

We separate logs from CRON jobs from the rest of the project to make them easier to find.

Please be aware that if you activate the `Backup` cron, it deletes all old log files that were not created on the day the cron is launched.

Errors related to non-existent routes or existing routes with incorrect request methods are not
logged. Instead, we only display a JSONResponse with the documentation route.

## Crontab

Use the `cron.txt` file to help you with the implementation of various tasks such as Import or Backup in your cron daemon.

## Documentation

The documentation is available at the `/swagger` route.

## UserToken

### What is it

The `UserToken` entity serves multiple purposes.

It stores the unique token that the user is going to use for this specific session (or in many cases, device like laptop, smartphone, etc.).

It also stores multiple `UserTracking` entities.

### UserTracking

This entity captures information from the server such as IP address, user-agent, accepted encoding/language, geolocation (with the help of MaxMind, see below), etc.

We collect all this to create a fingerprint, which can be used in the future to target ban users who demonstrate malicious behavior.

If you want to know more, check the method `_refreshTokenAndJWT` in `src/Service/Tool/User/Auth.php`.

### MaxMind

We use MaxMind's GeoLite2 database, which is a free database service where you input an IP address and it returns the associated city/country/ASN.

MaxMind offers 3 databases, one for each purpose (city/country/ASN). To use them, you first need to sign up [here](https://www.maxmind.com/en/geolite2/signup).

Then go to the License key page (you will see it in the Account category on the left after logging in).

Create a new license key and store it in your `.env` file as `MAXMIND_LICENSE_KEY`. Don't forget to put your account id in `MAXMIND_ACCOUNT_ID`.

After that, you just need to run the `GeoIpCheck` command with `php bin/console app:geo-ip` or let your crontab handle it for you (see the crontab section).

### Geocode.maps

We use [geocode.maps](https://geocode.maps.co) as a free API for reverse geocoding.

All you need is to create an account and store your API key as `GEOCODE_MAPS_CO_API_KEY` in your `.env` file.

## Testing

### Package

We use the `symfony/test-pack` for testing the project, which comes with PHPUnit.

Be aware that this package is only required in the dev environment, not in production.

### Initialization

If you want to test the project, you must create a `.env.test` file as a copy of your `.env` file,
but you need to change the `DATABASE_URL` value. Usually, use the same database name but with the suffix `-test`.

We need to clone the current database (with data) to another; we can do it with the mysqldump command:
`mysqldump --host=127.0.0.1 --port=3306 --user=DB_USER --password=DB_PASSWORD yu-gi-oh-api | mysql -u DB_USER -p yu-gi-oh-api-test`.

Launch the `UserTestFixtures` to have a user and an admin for testing purposes with the command:
`php bin/console doctrine:fixtures:load --group=user-test --env=test --append`.

### Run Tests

After that, all you need to do is run the command `php bin/phpunit <directory> --process-isolation` at the root of the project,
where `<directory>` can be `test/Controller`, `test/Entity`, or `test/Service`.

Be aware that some tests WILL fail if you try to run them individually because they have dependencies. Run `php bin/phpunit --list-groups` to find them.

You can also run `php bin/phpunit --process-isolation` to run all tests at once.