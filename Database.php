<?php
/**
 * Main database logic.
 * User: hkeca
 * Date: 5/3/17
 * Time: 4:38 PM
 */

class Database
{
    private $db;

    public function __construct($MYSQL_HOST = '127.0.0.1', $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB, $MYSQL_CHARSET = 'utf8')
    {
        $dsn = 'mysql:host=' . $MYSQL_HOST . ';dbname=' . $MYSQL_DB . ';charset=' . $MYSQL_CHARSET;

        try {
            $this->db = new PDO($dsn, $MYSQL_USER, $MYSQL_PASS);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (Exception $e) {
            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
        }
    }


    /**
     * Get all the polls
     *
     * @return array|bool
     */

    public function getPolls()
    {
        try {

            $stmt = $this->db->query('SELECT * FROM polls');

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
    }

        return false;
    }

    /**
     * Get the votes from a give poll
     * Can be filter by using @pollOption
     *
     * @param $pollName
     * @param null $pollOption
     * @return bool|int
     */
    public function getPollVotes($pollName, $pollOption = null)
    {
        return $this->getVotes($pollName, $pollOption);
    }

    /**
     * Cast a vote
     *
     * @param $username
     * @param $pollName
     * @param $option
     * @return bool
     */
    public function vote($username, $pollName, $option)
    {
        if (!isset($username) || !isset($pollName) || !isset($option))
            return false;

        if ($this->hasVoted($username, $pollName))
            return false;

        return $this->castVote($username, $pollName, $option);
    }

    /**
     * Make a poll
     *
     * @param $username
     * @param $pollName
     * @param $pollOptions
     * @return bool|int
     */
    public function makePoll($username, $pollName, $pollOptions)
    {
        if ($this->pollExists($pollName, $username))
            return 1;

        if (!$this->createPoll($username, $pollName, $pollOptions))
            return 2;

        return true;
    }

    /**
     * Get all votes for a poll
     *
     * @param $pollName
     * @param $username
     * @return array
     */
    public function getAllVotes($pollName, $username)
    {
        $pollId = $this->getPollFromName($pollName, $username);
        $options = $this->getPollOptions($pollName, $username);
        $votes = $this->getVotes($pollId);

        $voteCount = count($votes);

        $allVotes = array();

        foreach ($options as $key => $opt) {
            $val = json_decode($opt);

            if (!isset(${$val->name}))
                ${$val->name} = 0;

            for ($i = 0; $i < $voteCount; $i++) {
                if ($votes[$i] == $val->name) {
                    ${$val->name} += 1;
                }
            }

            array_push($allVotes, 'Option ' . $val->value . ' has ' . ${$val->name} . ' vote(s)');
        }

        return $allVotes;
    }

    /**
     * Helper functions
     */

    /**
     * Check if user has voted
     *
     * @param $username
     * @param $pollName
     *
     * @return Boolean
     */
    private function hasVoted($username, $pollName)
    {
        $rowCount = 0;

        try {

            $stmt = $this->db->prepare('SELECT * FROM votes WHERE casterName=? AND pollName=?');

            $stmt->execute(array($username, $pollName));

            $rowCount = $stmt->rowCount();

        } catch (PDOException $e) {

            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;

        }

        if ($rowCount > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get amount of votes a poll has
     *
     * @param $pollId
     * @return array
     */
    private function getVotes($pollId)
    {
        // if no pollName is give
        if(!isset($pollId)) {
            return false;
        }

        // All votes
        $votes = array();
        // Get votes
        Try {
            $stmt = $this->db->prepare('SELECT * FROM votes WHERE pollId=?');
            $stmt->execute(array($pollId));
            $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }

        if (empty($votes))
            return false;

        $data = array();


        foreach ($votes as $vote) {
            array_push($data, $vote['vote']);
        }



        /*
        // put each vote in its array based on its option
        foreach ($votes as $v) {
            // if no array exists with the name of the option
            // create it!
            if (!isset(${$v['vote']})) {
                ${$v['vote']} = array();
            }
            // add the vote to the array
            array_push(${$v['vote']}, $v['vote']);
        }

        // Put all the votes arrays into the data array
        foreach ($votes as $v) {
            // if the vote option has an array
            if (isset(${$v['vote']})) {
                // if its not in the array add it!
                if (!in_array(${$v['vote']}, $data))
                    array_push($data, ${$v['vote']});
            }
        }*/

        return $data;
    }

    /**
     * Send a vote to the server
     *
     * @param $username
     * @param $pollName
     * @param $option
     * @return bool
     */
    private function castVote($username, $pollName, $option)
    {
        // pollOptions array
        $opts = array();

        // No username
        if($username == null)
            return false;
        // Get available options
        $opts = $this->getPollOptions($pollName);
        // Check if given option is valid
        // for each option if it equals what is supplied it will increase
        // $amount, if $amount is greater than 0 then its a valid command
        $amount = 0;
        foreach ($opts as $o) {
            // $o = {'name': '', 'value': ''}
            $tmp = json_decode($o, true);
            // if the users' option equals one of the options
            // inc the $amount value
            if ($tmp['name'] == $option)
                $amount += 1;
        }
        // if no options matched return false
        if ($amount < 1)
            return false;


        try {
            // Insert statement
            $stmt = $this->db->prepare('INSERT INTO votes (casterName, pollName, vote) VALUES (?,?,?)');
            // Execute with the caster's username, the poll name, and the option
            $stmt->execute(array($username, $pollName, $option));
        } catch (PDOException $e) {
            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
            return false;
        }

        return true;
    }

    /**
     * Gets the poll options for specific poll
     *
     * @param $pollName
     * @param $username
     * @return array|bool
     */

    public function getPollOptions($pollName, $username)
    {
        // pollOptions from the database
        $values = array();

        // If not given a poll name/username return false
        if ($pollName == null || $username == null)
            return false;

        try {
            // Select statement
            $stmt = $this->db->prepare('SELECT options FROM polls WHERE name=?');
            // Execute with the poll name
            $stmt->execute(array($pollName));
            // get the values from the query
            $values = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
            return false;
        }

        // if no values return false
        if (empty($values))
            return false;

        // turn the json from the database to an array of options
        $jValues = json_decode($values[0]['options'], true);

        // poll options array
        $options = array();
        foreach ($jValues as $param)
        {
            array_push($options, $param);
        }

        // return the poll options
        return $options;
    }

    /**
     * Check if a poll exists
     *
     * @param $pollName
     * @param $username
     * @return bool
     */
    private function pollExists($pollName, $username)
    {
        // amount of polls exist with pollName
        $amount = 0;

        // find out.
        try {
            $stmt = $this->db->prepare('SELECT * FROM polls WHERE name=? AND creator=?');
            $stmt->execute(array($pollName, $username));
            $amount = $stmt->rowCount();
        } catch(PDOException $e) {
            return false;
        }

        if ($amount > 0)
            return true;

        return false;
    }

    /**
     * Create poll in database
     *
     * @param $username
     * @param $pollName
     * @param $pollOptions
     * @return bool
     */
    private function createPoll($username, $pollName, $pollOptions)
    {

        $options = $this->parseOptions($pollOptions);

        try {
            $stmt = $this->db->prepare('INSERT INTO polls (name, options, creator, active) VALUES (?,?,?,?)');
            $stmt->execute(array($pollName, $options, $username, '1'));
            return true;
        } catch (PDOException $e) {
            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
            return false;
        }

        return false;
    }

    /**
     * parses a string provided by the user
     *
     * required delimiters ':' ';'
     * ex test:some;a:b;
     *
     * @param $pollOptions
     * @return bool|string
     */
    private function parseOptions($pollOptions)
    {
        // if no options then return false
        if (empty($pollOptions))
            return false;
        // get options and turn into an array
        $opts = explode(';', $pollOptions);

        // For each option split the option into another array
        $jOptions = array();
        foreach($opts as $o) {
            $stuff = explode(':', $o);
            $tmp = array('name' => $stuff[0], "value" => $stuff[1]);
            $a = json_encode($tmp);

            array_push($jOptions, $a);
        }

        // return the options
        return json_encode($jOptions);
    }

    /**
     * Get the poll id from the poll name, and the creator's name
     *
     * @param $pollName
     * @param $creator
     *
     * @return bool|int
     */
    private function getPollFromName($pollName, $creator)
    {
        if ($pollName == null || $creator == null)
            return false;

        $pollData = null;

        try {
            $stmt = $this->db->prepare('SELECT id FROM polls WHERE name=? AND creator=?');
            $stmt->execute(array($pollName, $creator));
            $pollData = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
            return false;
        }

        if (empty($pollData))
            return false;

        return $pollData['id'];
    }

}