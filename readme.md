# Yu-Gi-Oh API

**Yu-Gi-Oh API** is a complete solution backend to get all card from the popular TCG [Yu-Gi-Oh](https://www.yugioh-card.com/).

We use the [YGOPRODeck API](https://ygoprodeck.com/) to get all Card and Set information.

## Run the project

### Create .env file
You can take example with the `.env.example` file to create your own.

### Install dependencies

Just run `composer install` in the root of the project.

### Create the database with tables

Run `php bin/console doctrine:database:create` to have your empty database.

Run `php bin/console doctrine:schema:update --force --complete` to create all table with relation.

Change `$username` and `$password` value in `src/DataFixtures/User.php`

Finally, run `php bin/console doctrine:fixtures:load --append` to have some basic needed with an Admin ready to use.

### Get all Card first time

We're going to use the `app:import` command to get all Set and Card with theirs information but,
we need some cards to avoid server explosion when we're going to import all Card and Set the first time 
(13k Card & 100 Set for me).

So, we're going to launch the import multiple times per slice of 5K cards each to avoid server overload:

`php bin/console app:import --limit=5000 --no-dbygo-update`

In my case, 2 times is largely enough, next time the Import launches, we won't have too many new Entity problems.

### Prepare Docker

`docker-compose -f docker-compose.yml build` then `docker-compose -f docker-compose.yml up -d`

## Crontab

Use the `cron.txt` file to help you with the implementation of various tasks such as Import in your cron daemon.

## Documentation

### you documentation is available at the `/swagger` route