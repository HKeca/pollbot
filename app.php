<?php
/**
 * Bot for discord
 * User: hkeca
 * Date: 5/3/17
 * Time: 3:30 PM
 */

include __DIR__ . '/vendor/autoload.php';

include __DIR__ . '/config.php';
include __DIR__ . '/Database.php';

use Discord\DiscordCommandClient;

$myDB = new Database($MYSQL_USER, $MYSQL_PASS, $MYSQL_DB);

/**
 * Init discord bot
 *
 * @token = bot token
 */

$discord = new DiscordCommandClient([
    'token'     => $apiToken,
    'prefix'    => 'poller'
]);


/**
 * Include commands
 */

require __DIR__ . '/Commands.php';


/**
 * Run the bot
 */

$discord->run();
