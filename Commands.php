<?php
/**
 * Commands.
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

        return $GLOBALS['chart']->createChart('Poll list', $list);
    }

    foreach ($GLOBALS['myDB']->getPolls() as $poll) {
        array_push($list, $poll['name'] . ':   ' . $poll['creator']);
    }

    return $GLOBALS['chart']->createChart('Poll list', $list);
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
        return 'Error: Not enough parameters. Usage: stats {pollName}';

    $votes = $GLOBALS['myDB']->getAllVotes($params[0], $message->author->username);
    $chart = $GLOBALS['chart']->createChart('Poll Stats for ' . $params[0], $votes);

    return $chart;

}, [
    'description' => 'Get the amount of votes from a poll',
    'usage'       => 'stats {poll name} [Filter option]',
]);


/**
 * Make command
 *
 * @param $message
 * @param $params
 *
 * @return string
 */
$discord->registerCommand('make', function($message, $params) {
    if (!isset($params[0]) || !isset($params[1]))
        return 'Error: Not enough parameters. Usage: make {poll name} {poll options}';

    $poll = $GLOBALS['myDB']->makePoll($message->author->username, $params[0], $params[1]);

    if ($poll == 1) {
        return 'Error: Poll not created, poll already exists';
    }
    if ($poll == 2) {
        return 'Error: Poll not created, poll database error';
    }

    return 'Created poll ' . $params[0] .' successfully';
});


/**
 * Test chart command
 */
$discord->registerCommand('chart', function($message){
    $stuff = array('Hello', 'World');
    $chart = $GLOBALS['chart']->createChart('Time', $stuff);

    return $chart;
});