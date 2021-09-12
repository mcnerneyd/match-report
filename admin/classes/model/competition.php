<?php

class Model_Competition extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'section_id',
        'name',
        'code',
        'teamsize',
        'teamstars',
        'format',
        'groups',
        'sequence',
    );

    protected static $_belongs_to = array('section'=> array('cascade-save'=>false));

    protected static $_conditions = array(
        'order_by' => array('sequence' => 'asc'),
    );

    protected static $_many_many = array(
        'team' => array(
                'table_through' => 'team__competition',
                ));


    protected static $_table_name = 'competition';

    public static function getCompetition($section, $name)
    {
        return DB::select()->from('competition')
            ->where('section', $section)
            ->where('name', $name)->execute();
    }

    public static function parse($section, $rawstr)
    {
        $str=$rawstr;
        try {
            $config = Config::get("$section.pattern.competition", []);

            $patterns = array();
            $replacements = array();
            foreach ($config as $pattern) {
                if (trim($pattern) == '') {
                    break;
                }
                $parts = explode($pattern[0], $pattern);
                if (count($parts) < 3) {
                    continue;
                }
                $patterns[] = "/${parts[1]}/i";
                $replacements[] = $parts[2];
            }

            $str = trim(preg_replace($patterns, $replacements, trim($str)));

            if ($str == '!') {
                return null;
            }

            return $str;
        } catch (Exception $e) {
            return $rawstr;
        }
    }
}
