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


    public function vote($username, $pollName, $option)
    {
        if (!isset($username) || !isset($pollName) || !isset($option))
            return false;

        if ($this->hasVoted($username, $pollName))
            return false;

        return $this->castVote($username, $pollName, $option);
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
        $opts = array();

        // No username
        if($username == null)
            return false;
        // Get available options
        $opts = $this->getPollOptions($pollName);
        if (empty($opts))
            return false;
        // Check if given option is valid
        $amount = 0;
        foreach ($opts as $o) {
            if ($o == $option)
                $amount += 1;
        }
        if ($amount < 1)
            return false;


        try {
            $stmt = $this->db->prepare('INSERT INTO votes (casterName, pollName, vote) VALUES (?,?,?)');
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

    public function getPollOptions($pollName)
    {
        $values = array();

        if ($pollName == null)
            return false;

        try {
            $stmt = $this->db->prepare('SELECT options FROM polls WHERE name=?');
            $stmt->execute(array($pollName));
            $values = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            echo 'PDO -> FATAL ERROR: ' . $e, PHP_EOL;
            return false;
        }

        if (empty($values))
            return false;

        $jValues = json_decode($values[0]['options'], true);

        $options = array();
        foreach ($jValues as $param)
        {
            array_push($options, $param['name']);
        }

        return $options;
    }

}