<?php
/**
 * Created by PhpStorm.
 * User: hkeca
 * Date: 5/4/17
 * Time: 11:59 AM
 */

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


/**
 *  Command: vote
 *
 * @param $message
 * @param $params
 *
 * @return string
 */
$discord->registerCommand('vote', function($message, $params) {
    if(!isset($params[0]) || !isset($params[1]))
    {
        return 'Err: Not enough params. Usage: vote {poll name} {your vote}';
    }

    if($GLOBALS['myDB']->vote($message->author->username, $params[0], $params[1])) {
        return 'Vote successful';
    }

    return 'Error, vote not casted :(';
}, [
    'description' => 'Vote for a poll',
]);

/**
 *  Command stats
 *
 * @param $message
 * @param $params
 *
 * @return string
 */
$discord->registerCommand('stats', function($message, $params) {
    if (!isset($params[0]))
        return 'Err: Not enough parameters. Usage: stats {pollName} [Filter by option]';

    if (isset($params[1])) {
        return 'Status for ' . $params[0] . ': ' . $GLOBALS['myDB']->getPollVotes($params[0], $params[1]);
    }

    return 'Status for ' . $params[0] . ': ' . $GLOBALS['myDB']->getPollVotes($params[0]);
}, [
    'description' => 'Get the amount of votes from a poll'
]);