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

$discord->registerCommand('listpolls', function($message, $params) {

    $list = array();

    if (!isset($params[0]) && !$params[0] == "full") {
        foreach ($GLOBALS['myDB']->getPolls() as $poll) {
            array_push($list, $poll['name']);
        }

        return implode(', ', $list);
    }

    foreach ($GLOBALS['myDB']->getPolls() as $poll) {
        array_push($list, $poll['name'] . ':   ' . $poll['creator']);
    }

    return implode(', ', $list);
}, [
    'description' => 'Get list of polls',
    'usage'       => 'listPolls [full]',
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
        return 'Error: Not enough params. Usage: vote {poll name} {your vote}';
    }

    if($GLOBALS['myDB']->vote($message->author->username, $params[0], $params[1])) {
        return 'Vote successful';
    }

    return 'Error, vote not casted :(';
}, [
    'description' => 'Vote for a poll',
    'usage'       => 'vote {poll name} {your vote}',
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
        return 'Error: Not enough parameters. Usage: stats {pollName} [Filter by option]';

    if (isset($params[1])) {
        return 'Status for ' . $params[0] . ': ' . $GLOBALS['myDB']->getPollVotes($params[0], $params[1]);
    }

    return 'Status for ' . $params[0] . ': ' . $GLOBALS['myDB']->getPollVotes($params[0]);
}, [
    'description' => 'Get the amount of votes from a poll',
    'usage'       => 'stats {poll name} [Filter option]',
]);

$discord->registerCommand('make', function($message, $params) {
    if (!isset($params[0]) || !isset($params[1]))
        return 'Error: Not enough parameters. Usage: make {poll name} {poll options}';

    if (!$GLOBALS['myDB']->makePoll($message->author->username, $params[0], $params[1])) {
        return 'Error: Poll not created :(';
    }

    return 'Created poll ' . $params[0] .' successfully';
});


$discord->registerCommand('chart', function($message){
    $stuff = array('Hello', 'World');
    $chart = $GLOBALS['chart']->createChart('Time', $stuff);

    return $chart;
});