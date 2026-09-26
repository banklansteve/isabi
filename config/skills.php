<?php

/**
 * Flat skill catalog for search / profile pickers.
 * Derived from skill_groups so category filters and the full list stay aligned.
 *
 * @var array<string, list<string>> $groups
 */
$groups = require __DIR__.'/skill_groups.php';

$skills = [];
foreach ($groups as $list) {
    foreach ($list as $skill) {
        $skills[$skill] = $skill;
    }
}

ksort($skills, SORT_NATURAL | SORT_FLAG_CASE);

return array_values($skills);
