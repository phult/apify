<?php
namespace Megaads\Apify\Controllers;

use Illuminate\Http\Request;
use Megaads\Apify\Controllers\BaseController;

class APIController extends BaseController
{
    public function get($entity, Request $request)
    {
        \Megaads\Apify\Middlewares\AuthMiddleware::checkPermission($entity, "read");
        $response = [];
        $model = $this->getModel($entity);
        $queryParams = $this->buildQueryParams($request);
        $model = $this->buildSelectionQuery($model, $queryParams['fields']);
        $model = $this->buildEmbedQuery($model, $queryParams['embeds']);
        $model = $this->buildSortQuery($model, $queryParams['sorts']);
        $model = $this->buildFilterQuery($model, $queryParams['filters']);
        $model = $this->buildGroupQuery($model, $queryParams['groups']);
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
        \Megaads\Apify\Middlewares\AuthMiddleware::checkPermission($entity, "read");
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
        \Megaads\Apify\Middlewares\AuthMiddleware::checkPermission($entity, "create");
        $result = [];
        $status = "successful";
        $model = $this->getModel($entity);
        $inputs = $request->except(\Megaads\Apify\Middlewares\AuthMiddleware::$apiTokenField);

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
        \Megaads\Apify\Middlewares\AuthMiddleware::checkPermission($entity, "update");
        $model = $this->getModel($entity);
        $attributes = $request->except(\Megaads\Apify\Middlewares\AuthMiddleware::$apiTokenField);
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
        \Megaads\Apify\Middlewares\AuthMiddleware::checkPermission($entity, "update");
        $model = $this->getModel($entity);
        $attributes = $request->except(\Megaads\Apify\Middlewares\AuthMiddleware::$apiTokenField);
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
        \Megaads\Apify\Middlewares\AuthMiddleware::checkPermission($entity, "delete");
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
    public function destroyBulk($entity, Request $request)
    {
        \Megaads\Apify\Middlewares\AuthMiddleware::checkPermission($entity, "delete");
        $response = [];
        $queryParams = $this->buildQueryParams($request, $entity);
        $model = $this->getModel($entity);
        $model = $this->buildEmbedQuery($model, $queryParams['embeds'], $entity);
        $model = $this->buildFilterQuery($model, $queryParams['filters'], $entity);
        $response['result'] = $model->delete();
        return $this->success($response);
    }

    public function upload(Request $request) {
        $files = $request->file('file');
        $directoryPath = env('APIFY_UPLOAD_PATH', '/home/upload');
        $customDirectoryPath = $request->get('customDirectoryPath');
        if (empty($files)) {
            $result = ["result" => "File required!"];
            return $this->error($result);
        }

        if (!empty($customDirectoryPath)) {
            $directoryPath = $directoryPath . '/' . $customDirectoryPath;
            if (!file_exists($directoryPath)) {
                mkdir($directoryPath, 0777, true);
            }
        }

        if (is_array($files)) {
            $output = [];
            foreach($files as $file) {
                $newFileName = time()."-".$file->getClientOriginalName();
                $newFileName = strtolower($newFileName);
                $file->move($directoryPath, $newFileName);
                $fullRelativePath = $newFileName;
                if ($customDirectoryPath) {
                    $fullRelativePath = "/" . $customDirectoryPath . '/' . $newFileName;
                }
                array_push($output, $fullRelativePath);
            }
            $result = ['result' => $output];

        } else {

            $newFileName = time()."-".$files->getClientOriginalName();
            $newFileName = strtolower($newFileName);
            $files->move($directoryPath, $newFileName);
            $fullRelativePath = $newFileName;
            if ($customDirectoryPath) {
                $fullRelativePath = "/" . $customDirectoryPath . '/' . $newFileName;
            }
            $result = ['result' => $fullRelativePath];

        }

        return $this->success($result);
    }
}
