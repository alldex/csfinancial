<?php
namespace AffiliateLTP;

/**
 * Represents a record of points.
 */
class Points_Record {
    
    /**
     * Life points
     * @var int
     */
    private $life;
    
    /**
     * Non life points
     * @var int
     */
    private $non_life;
    
    /**
     * Total points earned for this date
     * @var 
     */
    private $total;
    
    /**
     * Date string these points were earned.
     * @var string
     */
    private $date;
    
    public function __construct($date, $life = 0, $non_life = 0) {
        $this->date = $date;
        $this->life = absint($life);
        $this->non_life = absint($non_life);
        $this->total = $this->life + $this->non_life;
    }
    
    public function get_life() { 
        return $this->life;
    }
    
    public function get_non_life() {
        return $this->non_life;
    }
    
    public function get_total() {
        return $this->total;
    }
    
    public function get_date() {
        return $this->date;
    }
    
    public function get_date_in_milliseconds() {
        return strtotime($this->date) * 1000;
    }
}