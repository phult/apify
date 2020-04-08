<?php
namespace Megaads\Apify\Controllers;

use Megaads\Apify\FilterBuilders\FilterBuilderManagement;
use Megaads\Apify\Models\BaseModel;
if (class_exists('Illuminate\Routing\Controller')) {
    class DynamicController extends \Illuminate\Routing\Controller {}
} else if (class_exists('Laravel\Lumen\Routing\Controller')) {
    class DynamicController extends \Laravel\Lumen\Routing\Controller {}
}

class BaseController extends DynamicController
{

    const VIETNAMESE_TO_ASCII_MAP = array(
        "à" => "a", "ả" => "a", "ã" => "a", "á" => "a", "ạ" => "a", "ă" => "a", "ằ" => "a", "ẳ" => "a", "ẵ" => "a", "ắ" => "a", "ặ" => "a", "â" => "a", "ầ" => "a", "ẩ" => "a", "ẫ" => "a", "ấ" => "a", "ậ" => "a",
        "đ" => "d",
        "è" => "e", "ẻ" => "e", "ẽ" => "e", "é" => "e", "ẹ" => "e", "ê" => "e", "ề" => "e", "ể" => "e", "ễ" => "e", "ế" => "e", "ệ" => "e",
        "ì" => 'i', "ỉ" => 'i', "ĩ" => 'i', "í" => 'i', "ị" => 'i',
        "ò" => 'o', "ỏ" => 'o', "õ" => "o", "ó" => "o", "ọ" => "o", "ô" => "o", "ồ" => "o", "ổ" => "o", "ỗ" => "o", "ố" => "o", "ộ" => "o", "ơ" => "o", "ờ" => "o", "ở" => "o", "ỡ" => "o", "ớ" => "o", "ợ" => "o",
        "ù" => "u", "ủ" => "u", "ũ" => "u", "ú" => "u", "ụ" => "u", "ư" => "u", "ừ" => "u", "ử" => "u", "ữ" => "u", "ứ" => "u", "ự" => "u",
        "ỳ" => "y", "ỷ" => "y", "ỹ" => "y", "ý" => "y", "ỵ" => "y",
        "À" => "A", "Ả" => "A", "Ã" => "A", "Á" => "A", "Ạ" => "A", "Ă" => "A", "Ằ" => "A", "Ẳ" => "A", "Ẵ" => "A", "Ắ" => "A", "Ặ" => "A", "Â" => "A", "Ầ" => "A", "Ẩ" => "A", "Ẫ" => "A", "Ấ" => "A", "Ậ" => "A",
        "Đ" => "D",
        "È" => "E", "Ẻ" => "E", "Ẽ" => "E", "É" => "E", "Ẹ" => "E", "Ê" => "E", "Ề" => "E", "Ể" => "E", "Ễ" => "E", "Ế" => "E", "Ệ" => "E",
        "Ì" => "I", "Ỉ" => "I", "Ĩ" => "I", "Í" => "I", "Ị" => "I",
        "Ò" => "O", "Ỏ" => "O", "Õ" => "O", "Ó" => "O", "Ọ" => "O", "Ô" => "O", "Ồ" => "O", "Ổ" => "O", "Ỗ" => "O", "Ố" => "O", "Ộ" => "O", "Ơ" => "O", "Ờ" => "O", "Ở" => "O", "Ỡ" => "O", "Ớ" => "O", "Ợ" => "O",
        "Ù" => "U", "Ủ" => "U", "Ũ" => "U", "Ú" => "U", "Ụ" => "U", "Ư" => "U", "Ừ" => "U", "Ử" => "U", "Ữ" => "U", "Ứ" => "U", "Ự" => "U",
        "Ỳ" => "Y", "Ỷ" => "Y", "Ỹ" => "Y", "Ý" => "Y", "Ỵ" => "Y"
    );

    protected $simplePaginate = false;

    protected function getModel($entity)
    {
        $modelNameSpace = env('APIFY_MODEL_NAMESPACE', 'App\Models');
        $entityClass = $modelNameSpace . '\\' . $entity;
        if (class_exists($entityClass)) {
            $retval = new $entityClass;
        } else {
            $entityClass = $modelNameSpace . '\\' . str_replace('_', '', ucwords($entity, '_'));
            if (class_exists($entityClass)) {
                $retval = new $entityClass;
            } else {
                $retval = new BaseModel();
                $retval->bind($entity);
            }
        }
        $retval->entity = $entity;
        return $retval;
    }

    protected function buildQueryParams($request)
    {
        if ($request->has('simple_paginate') && $request->input('simple_paginate') == 1) {
            $this->simplePaginate = true;
        }
        $retval = [
            'metric' => 'get',
            'pagination' => [
                'page_size' => 50,
                'page_id' => 0,
            ],
            'sorts' => [],
            'fields' => [],
            'embeds' => [],
            'filters' => [],
            'groups' => [],
            'embeds_fields' => [],
        ];
        // pagination
        if ($request->has('page_id')) {
            $retval['pagination']['page_id'] = $request->input('page_id');
        }
        if ($request->has('page_size')) {
            $retval['pagination']['page_size'] = $request->input('page_size');
        }
        // fields
        if ($request->has('fields')) {
            $fields = explode(',', $request->input('fields'));
            foreach ($fields as $field) {
                if ($field != null) {
                    $fieldExplode = explode('.', $field);
                    if ($fieldExplode && count($fieldExplode) == 2) {
                        $embedName = $fieldExplode[0];
                        $col = $fieldExplode[1];
                        if (!isset($retval['embeds_fields'][$embedName])) {
                            $retval['embeds_fields'][$embedName] = [];
                        }
                        $retval['embeds_fields'][$embedName][] = $col;

                    } else {
                        $retval['fields'][] = $field;
                    }
                }
            }
        }
        // embeds
        if ($request->has('embeds')) {
            $embeds = explode(',', $request->input('embeds'));
            foreach ($embeds as $embed) {
                if ($embed != null) {
                    $retval['embeds'][] = $embed;
                }
            }
        }
        // groups
        if ($request->has('groups')) {
            $groups = explode(',', $request->input('groups'));
            foreach ($groups as $group) {
                if ($group != null) {
                    $retval['groups'][] = $group;
                }
            }
        }
        // metric
        if ($request->has('metric')) {
            $retval['metric'] = $request->input('metric');
        }
        // sorts
        if ($request->has('sorts')) {
            $sorts = explode(',', $request->input('sorts'));
            foreach ($sorts as $sort) {
                if ($sort != null) {
                    if ($sort[0] === '-') {
                        $retval['sorts'][substr($sort, 1, strlen($sort) - 1)] = 'desc';
                    } else {
                        $retval['sorts'][$sort] = 'asc';
                    }
                }
            }
        }
        // filters
        $retval['filters'] = FilterBuilderManagement::getInstance()->buildQueryParams($request);
        return $retval;
    }

    public function fetchData($query, $params = [])
    {
        if ($params['metric'] == 'count') {
            return $query->count();
        } else if ($params['metric'] == 'first') {
            return $query->first();
        } else if ($params['metric'] == 'increment'
            || $params['metric'] == 'decrement') {
            if (count($params['fields']) > 0) {
                $query = $query->$params['metric']($params['fields'][0]);
            }
            return $query;
        } else {
            if (array_key_exists('page_size', $params['pagination'])
                && array_key_exists('page_id', $params['pagination'])
                && $params['pagination']['page_size'] != -1
                && $params['pagination']['page_id'] != -1) {
                $query = $query->forPage(($params['pagination']['page_id'] + 1), $params['pagination']['page_size']);
            }
            return $query->get();
        }
    }

    public function fetchMetaData($query, $params = [])
    {
        if (!$this->simplePaginate) {
            $params['metric'] = 'count';
            $count = $this->fetchData($query, $params);
            $pageSize = -1;
            $pageId = -1;
            $pageCount = 1;
            $hasNext = false;
            $offSet = 0;
            if ($params['pagination']['page_size'] >= 0
                && $params['pagination']['page_id'] >= 0) {
                $pageSize = $params['pagination']['page_size'];
                $pageId = $params['pagination']['page_id'];
                $pageCount = ceil($count / $pageSize);
                $hasNext = true;
                if ($pageId + 1 >= $pageCount) {
                    $hasNext = false;
                }
                $offSet = ($pageId) * $pageSize;
            }
        } else {
            $pageSize = $params['pagination']['page_size'];
            $pageId = $params['pagination']['page_id'];
            $hasNext = true;
            $count = 0;
            $pageCount = 0;
            $offSet = ($pageId) * $pageSize;
        }
        return [
            'has_next' => $hasNext,
            'total_count' => $count,
            'page_count' => $pageCount,
            'page_size' => (int) $pageSize,
            'page_id' => (int) $pageId,
            'off_set' => $offSet,
        ];
    }

    protected function buildSortQuery($query, $sorts)
    {
        $tableAlias = $query->getModel()->getTable();
        foreach ($sorts as $column => $type) {
            if (preg_match("/^raw\(/", $column)) {
                preg_match('/raw\((.+)\)/', $column, $matches);
                if (count($matches) == 2) {
                    $query = $query->orderByRaw(\DB::raw($matches[1]));
                }
            } else if (str_contains($column, '.')) {
                $query = $query->orderBy($this->standardizedQueryAlias($query, $column), $type == 'desc' ? 'desc' : 'asc');
            } else {
                $query = $query->orderBy(($tableAlias ? $tableAlias . '.' : '') . $column, $type == 'desc' ? 'desc' : 'asc');
            }
        }
        return $query;
    }

    protected function buildFilterQuery($query, $filters)
    {
        $ft = [];
        for ($i = 0; $i < count($filters); $i++) {
            $item = $filters[$i];
            $item['field'] = $this->standardizedQueryAlias($query, $item['field']);
            $ft[] = $item;
        }
        return FilterBuilderManagement::getInstance()->buildQuery($query, $ft);
    }

    protected function buildSelectionQuery($query, $selections)
    {
        $tableAlias = $query->getModel()->getTable();
        if ($selections != null && count($selections) > 0) {
            foreach ($selections as $selection) {
                if (preg_match("/^raw\(/", $selection)) {
                    preg_match('/raw\((.+)\)/', $selection, $matches);
                    if (count($matches) == 2) {
                        $query = $query->addSelect(\DB::raw($matches[1]));
                    }
                } else if (preg_match("/^count/", $selection)
                    || preg_match("/^sum/", $selection)
                    || preg_match("/^max/", $selection)
                    || preg_match("/^min/", $selection)
                    || preg_match("/^avg/", $selection)) {
                    $query = $query->addSelect(\DB::raw($selection));
                } else {
                    if (str_contains($selection, '.')) {
                        $query = $query->addSelect($this->standardizedQueryAlias($query, $selection));
                    } else {
                        $query = $query->addSelect(($tableAlias ? $tableAlias . '.' : '') . $selection);
                    }
                }
            }
        } else {
            $query = $query->select(array($tableAlias ? $tableAlias . '.*' : '*'));
        }
        return $query;
    }

    protected function buildGroupQuery($query, $groups)
    {
        $tableAlias = $query->getModel()->getTable();
        if ($groups != null && count($groups) > 0) {
            foreach ($groups as $group) {
                if (str_contains($group, '.')) {
                    $query = $query->groupBy($this->standardizedQueryAlias($query, $group));
                } else {
                    $query = $query->groupBy(($tableAlias ? $tableAlias . '.' : '') . $group);
                }
            }
        }
        return $query;
    }

    public function decorEmbed($model, $params)
    {
        if (!array_key_exists('embeds', $params)
            || !$params['embeds']) {
            return $params;
        }
        $embeds = [];
        $embedsFields = $params['embeds_fields'];
        foreach ($params['embeds'] as $embed) {
            $method = $embed . 'EmbedConfig';
            $embedInfo = method_exists($model, $method) ? call_user_func([$model, $method]) : [];
            $embedTable = isset($embedInfo['table']) ? $embedInfo['table'] : $embed;
            $embedColumns = isset($embedInfo['columns']) ? $embedInfo['columns'] : [];
            if ($embedInfo) {
                if ($embedsFields && isset($embedsFields[$embed])) {
                    $embedColumns = $embedsFields[$embed];
                }
                foreach ($embedColumns as &$column) {
                    $column = $embedTable . '.' . $column;
                }
                $embeds[$embed] = function ($query) use ($embedColumns) {
                    $query->get($embedColumns);
                };
            } else {
                $embeds[] = $embed;
            }
        }
        $params['embeds'] = $embeds;
        return $params;
    }

    protected function buildEmbedQuery($query, $embeds)
    {
        if ($embeds != null && count($embeds) > 0) {
            $query = $query->with($embeds);
        }
        return $query;
    }

    protected function standardizedQueryAlias($query, $selection)
    {
        $retval = $selection;
        $fields = explode('.', $selection);
        if (count($fields) >= 2 && $fields[0] == $query->getModel()->entity) {
            $retval = $query->getModel()->getTable();
            for ($idx = 1; $idx < count($fields); $idx++) {
                $retval .= '.' . $fields[$idx];
            }
        }
        return $retval;
    }

    protected function success($data)
    {
        $data['status'] = 'successful';
        return response()->json($data);
    }
    protected function error($data)
    {
        $data['status'] = 'fail';
        return response()->json($data);
    }

    protected function getSlug($string) {
        $lowerCaseString = strtolower($string);
        $lowerCaseAsciiString = strtr($lowerCaseString, self::VIETNAMESE_TO_ASCII_MAP);
        //remove duplicated spaces
        $removedDuplicatedSpacesString = preg_replace("/\s+/i", " ", $lowerCaseAsciiString);
        //remove special character
        $retVal = preg_replace("/[^a-z0-9]/i", "-", $removedDuplicatedSpacesString);
        return $retVal;
    }
}
