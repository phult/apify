<?php
namespace Megaads\Apify\Controllers;

use Megaads\Apify\Models\BaseModel;
use Laravel\Lumen\Routing\Controller;

class BaseController extends Controller
{
    protected function getModel($entity)
    {
        $modelNameSpace = getenv('APP_MODEL_NAMESPACE');
        $entityClass = ($modelNameSpace != null ? $modelNameSpace : 'App\Models') . '\\' . str_replace('_', '', ucwords($entity, '_'));
        if (class_exists($entityClass)) {
            $retval = new $entityClass;
        } else {
            $retval = new BaseModel();
            $retval->bind($entity);
        }
        return $retval;
    }

    protected function buildQueryParams($request, $entity)
    {
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
            'groups' => []
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
                    $retval['fields'][] = $field;
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
        $filterOperators = [
            '!<>' => 'nin', // not in
            '<>' => 'in', // in
            '>=' => 'gte', // greater or equal
            '>' => 'gt', // greater
            '<=' => 'lte', // less or equal
            '<' => 'lt', // less
            '!~' => 'nlike', // not like
            '~' => 'like', // like
            '![]' => 'nbw', // not between
            '[]' => 'bw', // between
            '!=' => 'neq', // not equal
            '=' => 'eq', // equal
        ];
        if ($request->has('filters')) {
            $filters = explode(',', $request->input('filters'));
            foreach ($filters as $filter) {
                if ($filter != null) {
                    foreach ($filterOperators as $key => $value) {
                        $operator = explode($key, $filter);
                        if (count($operator) == 2) {
                            $operatorRelation = explode('.', $operator[0]);
                            if (count($operatorRelation) == 2) {
                                $retval['filters'][$operatorRelation[0]][] = [
                                    'field' => $operatorRelation[1],
                                    'operator' => $value,
                                    'value' => $operator[1],
                                ];
                            } else if (count($operatorRelation) == 1) {
                                $retval['filters'][$entity][] = [
                                    'field' => $operator[0],
                                    'operator' => $value,
                                    'value' => $operator[1],
                                ];
                            }
                            break;
                        }
                    }
                }
            }
        }
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
        $params['metric'] = 'count';
        $count = $this->fetchData($query, $params);
        $pageSize = $params['pagination']['page_size'];
        $pageId = $params['pagination']['page_id'] + 1;
        $pageCount = ceil($count / $pageSize);
        $hasNext = true;
        if ($pageId >= $pageCount) {
            $hasNext = false;
        }
        return [
            'has_next' => $hasNext,
            'total_count' => $count,
            'page_count' => $pageCount,
            'page_size' => (int) $pageSize,
            'page_id' => (int) $pageId - 1,
            'off_set' => ($pageId - 1) * $pageSize,
        ];
    }

    protected function buildSortQuery($query, $sorts, $tableAlias = null)
    {
        foreach ($sorts as $column => $type) {
            $query = $query->orderBy(($tableAlias ? $tableAlias . '.' : '') . $column, $type == 'desc' ? 'desc' : 'asc');
        }
        return $query;
    }

    protected function buildFilterQuery($query, $filters, $entity)
    {
        foreach ($filters as $tableAlias => $entityFilters) {
            foreach ($entityFilters as $filter) {
                switch ($filter['operator']) {
                    case 'eq':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                if ($filter['field'] == 'ids') {
                                    $query = $query->whereIn($tableAlias . '.' . $filter['field'], explode(':', $filter['value']));
                                } else {
                                    $query = $query->where($tableAlias . '.' . $filter['field'], '=', $filter['value']);
                                }
                            });
                        } else {
                            if ($filter['field'] == 'ids') {
                                $query = $query->whereIn($tableAlias . '.' . 'id', explode(':', $filter['value']));
                            } else {
                                $query = $query->where($tableAlias . '.' . $filter['field'], '=', $filter['value']);
                            }
                        }
                        break;
                    case 'neq':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->where($tableAlias . '.' . $filter['field'], '<>', $filter['value']);
                            });
                        } else {
                            $query = $query->where($tableAlias . '.' . $filter['field'], '<>', $filter['value']);
                        }
                        break;
                    case 'gt':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->where($tableAlias . '.' . $filter['field'], '>', $filter['value']);
                            });
                        } else {
                            $query = $query->where($tableAlias . '.' . $filter['field'], '>', $filter['value']);
                        }
                        break;
                    case 'gte':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->where($tableAlias . '.' . $filter['field'], '>=', $filter['value']);
                            });
                        } else {
                            $query = $query->where($tableAlias . '.' . $filter['field'], '>=', $filter['value']);
                        }
                        break;
                    case 'lt':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->where($tableAlias . '.' . $filter['field'], '<', $filter['value']);
                            });
                        } else {
                            $query = $query->where($tableAlias . '.' . $filter['field'], '<', $filter['value']);
                        }
                        break;
                    case 'lte':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->where($tableAlias . '.' . $filter['field'], '<=', $filter['value']);
                            });
                        } else {
                            $query = $query->where($tableAlias . '.' . $filter['field'], '<=', $filter['value']);
                        }
                        break;
                    case 'bw':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->whereBetween($tableAlias . '.' . $filter['field'], explode(':', $filter['value']));
                            });
                        } else {
                            $query = $query->whereBetween($tableAlias . '.' . $filter['field'], explode(':', $filter['value']));
                        }
                        break;
                    case 'nbw':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->whereNotBetween($tableAlias . '.' . $filter['field'], explode(':', $filter['value']));
                            });
                        } else {
                            $query = $query->whereNotBetween($tableAlias . '.' . $filter['field'], explode(':', $filter['value']));
                        }
                        break;
                    case 'in':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->whereIn($tableAlias . '.' . $filter['field'], explode(':', $filter['value']));
                            });
                        } else {
                            $query = $query->whereIn($tableAlias . '.' . $filter['field'], explode(':', $filter['value']));
                        }
                        break;
                    case 'nin':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->whereNotIn($tableAlias . '.' . $filter['field'], explode(':', $filter['value']));
                            });
                        } else {
                            $query = $query->whereNotIn($tableAlias . '.' . $filter['field'], explode(':', $filter['value']));
                        }
                        break;
                    case 'like':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->where($tableAlias . '.' . $filter['field'], 'LIKE', '%' . $filter['value'] . '%');
                            });
                        } else {
                            $query = $query->where($tableAlias . '.' . $filter['field'], 'LIKE', '%' . $filter['value'] . '%');
                        }
                        break;
                    case 'nlike':
                        if ($tableAlias != $entity) {
                            $query = $query->whereHas($tableAlias, function ($query) use ($tableAlias, $filter) {
                                $query = $query->where($tableAlias . '.' . $filter['field'], 'NOT LIKE', $filter['value']);
                            });
                        } else {
                            $query = $query->where($tableAlias . '.' . $filter['field'], 'NOT LIKE', $filter['value']);
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $query;
    }

    protected function buildSelectionQuery($query, $selections, $tableAlias = null)
    {
        if ($selections != null && count($selections) > 0) {
            foreach ($selections as $selection) {
                if (preg_match("/^count/", $selection)
                    || preg_match("/^sum/", $selection)
                    || preg_match("/^max/", $selection)
                    || preg_match("/^min/", $selection)
                    || preg_match("/^avg/", $selection)) {
                    $query = $query->addSelect(\DB::raw($selection));
                } else {
                    if (str_contains($selection, '.')) {
                        $query = $query->addSelect($selection);
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

    protected function buildGroupQuery($query, $groups, $tableAlias = null)
    {
        if ($groups != null && count($groups) > 0) {
            foreach ($groups as $group) {
                if (str_contains($group, '.')) {
                    $query = $query->groupBy($group);
                } else {
                    $query = $query->groupBy(($tableAlias ? $tableAlias . '.' : '') . $group);
                }
            }
        }
        return $query;
    }

    protected function buildEmbedQuery($query, $embeds, $tableAlias = null)
    {
        if ($embeds != null && count($embeds) > 0) {
            $query = $query->with($embeds);
        }
        return $query;
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
}
