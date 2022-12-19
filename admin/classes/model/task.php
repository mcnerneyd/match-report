<?php

class Model_Task extends \Orm\Model
{
    protected static $_properies = array(
        'id',
        'command',
        'datetime',
        'status',
        'recur'
    );

    protected static $_table_name = 'task';

    public static function command($task)
    {
        //return dirname(dirname(\Uri::base(false)))."/".$task['command'];
        return "http://cards.leinsterhockey.ie/cards/".$task;
    }
}
