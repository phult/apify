<?php
namespace Megaads\Apify\Controllers;

use Illuminate\Http\Request;
use Megaads\Apify\Controllers\BaseController;

class APIController extends BaseController
{
    public function get($entity, Request $request)
    {
        $response = [];
        $queryParams = $this->buildQueryParams($request, $entity);
        $model = $this->getModel($entity);
        $model = $this->buildSortQuery($model, $queryParams['sorts'], $entity);
        $model = $this->buildSelectionQuery($model, $queryParams['fields'], $entity);
        $model = $this->buildFilterQuery($model, $queryParams['filters'], $entity);
        $model = $this->buildGroupQuery($model, $queryParams['groups'], $entity);
        $model = $this->buildEmbedQuery($model, $queryParams['embeds'], $entity);
        if ($queryParams['metric'] == 'count'
            || $queryParams['metric'] == 'first'
            || $queryParams['metric'] == 'increment'
            || $queryParams['metric'] == 'decrement') {
            $response['result'] = $this->fetchData($model, $queryParams);
        } else {
            $response['meta'] = $this->fetchMetaData($model, $queryParams);
            $response['result'] = $this->fetchData($model, $queryParams);
        }
        return $this->success($response);
    }

    public function show($entity, $id, Request $request)
    {
        $queryParams = $this->buildQueryParams($request, $entity);
        $model = $this->getModel($entity);
        $model = $this->buildSelectionQuery($model, $queryParams['fields'], $entity);
        $model = $this->buildEmbedQuery($model, $queryParams['embeds'], $entity);
        $result = $model->find($id);
        return $this->success([
            'result' => $result,
        ]);
    }

    public function store($entity, Request $request)
    {
        $result = [];
        $status = "successful";
        $model = $this->getModel($entity);
        $inputs = $request->all();
        try {
            if (isset($inputs[0]) && is_array($inputs[0])) {
                \DB::beginTransaction();
                try {
                    foreach ($inputs as $input) {
                        $result[] = $model->create($input);
                    }
                    \DB::commit();
                } catch (\Exception $exc) {
                    $status = "fail";
                    $result = $exc->getMessage();
                    \DB::rollback();
                }
            } else {
                $result = $model->create($inputs);
            }
        } catch (\Exception $exc) {
            $status = "fail";
            $result = $exc->getMessage();
        }
        if ($status == "successful") {
            return $this->success([
                'result' => $result,
            ]);
        } else {
            return $this->error([
                'result' => $result,
            ]);
        }
    }

    public function update($entity, $id, Request $request)
    {
        $model = $this->getModel($entity);
        $attributes = $request->all();
        $obj = $model->find($id);
        if ($obj == null) {
            return $this->error([
                'result' => '404',
            ]);
        }
        $result = $obj->update($attributes);
        return $this->success([
            'result' => $result,
        ]);
    }

    public function patch($entity, $id, Request $request)
    {
        $model = $this->getModel($entity);
        $attributes = $request->all();
        $obj = $model->find($id);
        if ($obj == null) {
            return $this->error([
                'result' => '404',
            ]);
        }
        $result = $obj->update($attributes);
        return $this->success([
            'result' => $result,
        ]);
    }

    public function destroy($entity, $id, Request $request)
    {
        $model = $this->getModel($entity);
        $obj = $model->find($id);
        if ($obj == null) {
            return $this->error([
                'result' => '404',
            ]);
        }
        $result = $obj->delete();
        return $this->success([
            'result' => $result,
        ]);
    }
}
