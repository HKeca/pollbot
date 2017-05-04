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
    'prefix'    => ';'
]);

/**
 * Command listPolls
 *
 * @param $message
 * @return  string
 */

$discord->registerCommand('listPolls', function($message) {
    $list = array();

    foreach ($GLOBALS['myDB']->getPolls() as $poll) {
        array_push($list, $poll['name']);
    }


    return implode(', ', $list);
}, [
    'description' => 'Get list of polls',
]);


$discord->registerCommand('vote', function($message, $params) {
    if(!isset($params[0]) || !isset($params[1]))
    {
        return 'Err: Not enough params. Usage: vote {poll name} {your vote}';
    }

    if($GLOBALS['myDB']->vote($message->author->username, $params[0], $params[1])) {
        return 'Vote successful';
    }

    return 'Error, vote not casted :(';
});

$discord->registerCommand('stats', function($message, $params) {
    if (!isset($params[0]))
        return 'Err: Not enough parameters. Usage: stats {pollName} [Filter by option]';

    if (isset($params[1])) {
        return 'Status for ' . $params[0] . ': ' . $GLOBALS['myDB']->getPollVotes($params[0], $params[1]);
    }

    return 'Status for ' . $params[0] . ': ' . $GLOBALS['myDB']->getPollVotes($params[0]);
});

/**
 * Run the bot
 */

$discord->run();
