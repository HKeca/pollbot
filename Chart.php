<?php
/**
 * Charts
 * User: hkeca
 * Date: 5/4/17
 * Time: 7:50 PM
 */
mb_internal_encoding('utf8');

class Chart
{
    public function createChart($title, $items)
    {
        $spacers = mb_strlen($title) * 1.5;

        $chart = "\n\n";
        $chart .= "    " . $title . ":\n";
        $chart .= "---------";

        for ($i = 0; $i < $spacers; $i++) {
            $chart .= "-";
        }

        $chart .= "\n\n";

        foreach ($items as $item) {
            $chart .= "    " . $item . "\n\n";
        }

        return $chart;
    }
}