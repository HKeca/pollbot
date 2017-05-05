<?php
/**
 * Created by PhpStorm.
 * User: hkeca
 * Date: 5/3/17
 * Time: 4:38 PM
 */

class Database
{
    private $db;

    public function __construct($MYSQL_USER, $MYSQL_PASS, $MYSQL_DB, $MYSQL_CHARSET = 'utf8')
    {
        try {
            $this->db = new PDO('mysql:host=127.0.0.1;dbname=' . $MYSQL_DB . ';charset=' . $MYSQL_CHARSET, $MYSQL_USER, $MYSQL_PASS);
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
     * @return bool
     */
    public function makePoll($username, $pollName, $pollOptions)
    {
        if ($this->pollExists($pollName))
            return false;

        if (!$this->createPoll($username, $pollName, $pollOptions))
            return false;

        return true;
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
    protected function hasVoted($username, $pollName)
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
     * @param $pollName
     * @param null $pollOption
     * @return bool|int
     */

    private function getVotes($pollName, $pollOption = null)
    {
        // if no pollName is give
        if(!isset($pollName)) {
            return false;
        }

        // If you want a certain poll option
        if (isset($pollOption)) {
            try {
                $stmt = $this->db->prepare('SELECT * FROM votes WHERE vote=? AND pollName=?');
                $stmt->execute(array($pollOption, $pollName));

                return $stmt->rowCount();
            } catch (PDOException $e) {
                echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
                return false;
            }
        }

        try {
            $stmt = $this->db->prepare('SELECT * FROM votes WHERE pollName=?');
            $stmt->execute(array($pollName));
            // Return the amount of rows the sql statement returns
            return $stmt->rowCount();
        } catch (PDOException $e) {
            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
            return false;
        }

        return false;
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
     * @return array|bool
     */

    private function getPollOptions($pollName)
    {
        // pollOptions from the database
        $values = array();

        // If not given a poll name return false
        if ($pollName == null)
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
     * @return bool
     */
    private function pollExists($pollName)
    {
        // amount of polls exist with pollName
        $amount = 0;

        // find out.
        try {
            $stmt = $this->db->prepare('SELECT * FROM polls WHERE name=?');
            $stmt->execute(array($pollName));
            $amount = $stmt->rowCount();
        } catch(PDOException $e) {
            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
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
        if (empty($pollOptions))
            return false;

        $opts = explode(';', $pollOptions);

        $jOptions = array();
        foreach($opts as $o) {
            $stuff = explode(':', $o);
            $tmp = array($stuff[0] => array("name" => $stuff[0], "value" => $stuff[1]));
            $a = json_encode($tmp);

            array_push($jOptions, $a);
        }

        return json_encode($jOptions);
    }

}