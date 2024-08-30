<?php

class Model_Competition extends \Orm\Model
{
    protected static $_properties = array(
        'id',
        'section_id',
        'name',
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

    public static function getCompetition(Model_Section $section, $name) : ?Model_Competition
    {
        return Model_Competition::query()
            ->where('section_id', $section['id'])
            ->where('name', $name)->get_one();
    }

    public function log() {
        $detail = $this['sequence'].";".$this['teamsize'].";".$this['teamstars'].";".$this['groups'];
        $detail = rtrim($detail, ";");
        if ($detail != "") $detail = " {".$detail."}";
        Log::info("+COMPETITION {$this['format']} [{$this['name']}/{$this['section']['name']}]$detail #{$this['id']}/" . Auth::get_screen_name());
    }

    public static function parse(string $rawstr)
    {
        $str=$rawstr;
        try {
            $config = Config::get("section.pattern.competition", []);

            $patterns = array();
            $replacements = array();
            foreach ($config as $pattern) {
                if (trim($pattern) == '') {
                    continue;
                }
                $parts = explode($pattern[0], $pattern);
                if (count($parts) < 3) {
                    continue;
                }
                $patterns[] = "/{$parts[1]}/i";
                $replacements[] = $parts[2];
            }

            $str = trim(preg_replace($patterns, $replacements, trim($str)));

            if (strpos($str, '!') !== false) {
                return false;
            }

            return $str;
        } catch (Exception $e) {
            Log::error("Failed to parse: $rawstr".$e->getMessage());
            return $rawstr;
        }
    }
}
