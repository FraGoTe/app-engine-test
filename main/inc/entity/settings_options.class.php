<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @license see /license.txt
 * @author autogenerated
 */
class SettingsOptions extends \Entity
{
    /**
     * @return \Entity\Repository\SettingsOptionsRepository
     */
     public static function repository(){
        return \Entity\Repository\SettingsOptionsRepository::instance();
    }

    /**
     * @return \Entity\SettingsOptions
     */
     public static function create(){
        return new self();
    }

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $variable
     */
    protected $variable;

    /**
     * @var string $value
     */
    protected $value;

    /**
     * @var string $display_text
     */
    protected $display_text;


    /**
     * Get id
     *
     * @return integer 
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Set variable
     *
     * @param string $value
     * @return SettingsOptions
     */
    public function set_variable($value)
    {
        $this->variable = $value;
        return $this;
    }

    /**
     * Get variable
     *
     * @return string 
     */
    public function get_variable()
    {
        return $this->variable;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return SettingsOptions
     */
    public function set_value($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function get_value()
    {
        return $this->value;
    }

    /**
     * Set display_text
     *
     * @param string $value
     * @return SettingsOptions
     */
    public function set_display_text($value)
    {
        $this->display_text = $value;
        return $this;
    }

    /**
     * Get display_text
     *
     * @return string 
     */
    public function get_display_text()
    {
        return $this->display_text;
    }
}