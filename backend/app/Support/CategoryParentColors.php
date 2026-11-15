<?php

namespace App\Support;

class CategoryParentColors
{
    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Category>  $parents
     * @return array<int, string>
     */
    public static function map($parents): array
    {
        $palette = ['blue', 'green', 'pink', 'orange', 'teal', 'violet', 'red', 'cyan'];
        $map = [];
        $paletteIndex = 0;

        foreach ($parents->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $parent) {
            if ($parent->slug === 'local-news') {
                $map[$parent->id] = 'yellow';

                continue;
            }

            $map[$parent->id] = $palette[$paletteIndex % count($palette)];
            $paletteIndex++;
        }

        return $map;
    }
}
