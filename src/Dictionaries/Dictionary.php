<?php

namespace QT\Foundation\Dictionaries;

use QT\Import\Dictionary as ImportDictionary;


/**
 * Import Dictionary
 *
 * @package QT\Foundation\Dictionaries
 */
class Dictionary extends ImportDictionary
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
}
