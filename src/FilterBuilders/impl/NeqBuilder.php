<?php
namespace Megaads\Apify\FilterBuilders\Impl;

use Megaads\Apify\FilterBuilders\FilterBuilder;

class NeqBuilder extends FilterBuilder
{
    const regex = '/(^[a-zA-Z0-9\.\_\-]+)\!\=(.*)/';
    protected $level = 10;
    public function buildQueryParam($filterParam)
    {
        preg_match(self::regex, $filterParam, $matches);
        if (count($matches) == 3) {
            return [
                "field" => $matches[1],
                "value" => $matches[2],
            ];
        } else {
            return false;
        }
    }
    public function buildQuery($query, $filter)
    {
        if (strtolower($filter['value']) == 'null') {
            $query = $query->whereNotNull($filter['field']);
        } else {
            $query = $query->where($filter['field'], '<>', $filter['value']);
        }
        return $query;
    }
}
