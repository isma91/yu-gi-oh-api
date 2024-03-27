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

Be aware that only error log in production will be sent to avoid too much spam.

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

## Crontab

Use the `cron.txt` file to help you with the implementation of various tasks such as Import or Backup in your cron daemon.

## Documentation

The documentation is available at the `/swagger` route