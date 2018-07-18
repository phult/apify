<?php
namespace Megaads\Apify\Controllers;

use Laravel\Lumen\Routing\Controller;
use Megaads\Apify\FilterBuilders\FilterBuilderManagement;
use Megaads\Apify\Models\BaseModel;

class BaseController extends Controller
{

    protected function getModel($entity)
    {
        $modelNameSpace = env('APIFY_MODEL_NAMESPACE', 'App\Models');
        $entityClass = $modelNameSpace . '\\' . str_replace('_', '', ucwords($entity, '_'));
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
            'groups' => [],
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
        $retval['filters'] = FilterBuilderManagement::getInstance()->buildQueryParams($request, $entity);
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
        $pageSize = -1;
        $pageId = -1;
        $pageCount = 1;
        $hasNext = true;
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
        return [
            'has_next' => $hasNext,
            'total_count' => $count,
            'page_count' => $pageCount,
            'page_size' => (int) $pageSize,
            'page_id' => (int) $pageId,
            'off_set' => $offSet,
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
        return FilterBuilderManagement::getInstance()->buildQuery($query, $filters, $entity);
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
