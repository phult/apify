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
        $queryParams = $this->decorEmbed($model, $queryParams);
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
        $model = $this->getModel($entity);
        $queryParams = $this->buildQueryParams($request, $entity);
        $queryParams = $this->decorEmbed($model, $queryParams);
        $model = $this->buildSelectionQuery($model, $queryParams['fields'], $entity);
        $model = $this->buildEmbedQuery($model, $queryParams['embeds']);
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

    public function upload(Request $request)
    {
        $files = $request->file('file');
        $fileBase64 = $request->input('fileBase64');
        $directoryPath = env('APIFY_UPLOAD_PATH', '/home/upload');
        $customFileName = $request->get('customFileName');
        $customDirectoryPath = $request->get('customDirectoryPath');
        $ruleValidate = $request->get('ruleValidate', []);
        if (empty($files) && empty($fileBase64)) {
            $result = ["result" => "File required!"];
            return $this->error($result);
        }

        if (!empty($customDirectoryPath)) {
            $directoryPath = $directoryPath . '/' . $customDirectoryPath;
            if (!file_exists($directoryPath)) {
                mkdir($directoryPath, 0777, true);
            }
        }
        if (!empty($fileBase64)) {
            if (is_array($fileBase64)) {
                $output = [];
                foreach($fileBase64 as $file) {
                    $path = $this->uploadBase64($file, $customDirectoryPath, $directoryPath, $customFileName);
                    if (!empty($path)) {
                        array_push($output, $path);
                    }
                    $result = ['result' => $output];
                }

            }else{
                $path = $this->uploadBase64($fileBase64, $customDirectoryPath, $directoryPath, $customFileName);
                $result = ['result' => $path];
            }
        }else if (is_array($files)) {
        //mutiple
            $output = [];
            foreach($files as $file) {
                $validate = $this->validateUpload($ruleValidate, $file);
                if($validate['status']) {
                    $newFileName = time()."-".$this->getSlug(preg_replace('/\\.[^.\\s]{3,4}$/', '', $file->getClientOriginalName())).".".$file->getClientOriginalExtension();
                    $file->move($directoryPath, $newFileName);
                    $fullRelativePath = $newFileName;
                    if ($customDirectoryPath) {
                        $fullRelativePath = "/" . $customDirectoryPath . '/' . $newFileName;
                    }
                    array_push($output, $fullRelativePath);
                } else {
                    return $this->error($validate);
                }
            }
            $result = ['result' => $output];
        } else {
            $validate = $this->validateUpload($ruleValidate, $files);
            if($validate['status']) {
                $newFileName = time()."-".$this->getSlug(preg_replace('/\\.[^.\\s]{3,4}$/', '', $files->getClientOriginalName())).".".$files->getClientOriginalExtension();
                $files->move($directoryPath, $newFileName);
                $fullRelativePath = $newFileName;
                if ($customDirectoryPath) {
                    $fullRelativePath = "/" . $customDirectoryPath . '/' . $newFileName;
                }
                $result = ['result' => $fullRelativePath];
            } else {
                return $this->error($validate);
            }
        }

        return $this->success($result);
    }

    private function validateUpload($rule, $file) {
        $retVal = ['status' => false];
        if(!empty($rule['extensions']) && is_array($rule['extensions'])) {
            if(!in_array(strtolower($file->getClientOriginalExtension()), $rule['extensions'])) {
                $retVal['message'] = 'Extension not valid!';
                return $retVal;
            }
        }
        if(!empty($rule['maxSize']) && is_numeric($rule['maxSize'])) {
            if(($file->getClientSize() / 1048576) > $rule['maxSize']) {
                $retVal['message'] = 'File max size '.$rule['maxSize'].' MB!';
                return $retVal;
            }
        }
        if(!empty($rule['minWidth']) && is_numeric($rule['minWidth'])) {
            list($width) = getimagesize($file);
            if($width < $rule['minWidth']) {
                $retVal['message'] = 'File min width '.$rule['minWidth'].'px!';
                return $retVal;
            }
        }
        if(!empty($rule['maxWidth']) && is_numeric($rule['maxWidth'])) {
            list($width) = getimagesize($file);
            if($width > $rule['maxWidth']) {
                $retVal['message'] = 'File max width '.$rule['maxWidth'].'px!';
                return $retVal;
            }
        }
        if(!empty($rule['minHeight']) && is_numeric($rule['minHeight'])) {
            list($height) = getimagesize($file);
            if($height < $rule['minHeight']) {
                $retVal['message'] = 'File min height '.$rule['minHeight'].'px!';
                return $retVal;
            }
        }
        if(!empty($rule['maxHeight']) && is_numeric($rule['maxHeight'])) {
            list($height) = getimagesize($file);
            if($height < $rule['maxHeight']) {
                $retVal['message'] = 'File max height '.$rule['maxHeight'].'px!';
                return $retVal;
            }
        }
        return ['status' => true];
    }

    private function uploadBase64($file, $customDirectoryPath, $directoryPath, $customFileName){
        $retVal = '';
        $file = preg_replace('/^data:image\/\w+;base64,/', '', $file);
        $file = str_replace(' ', '+', $file);
        $image = base64_decode($file);
        if (imagecreatefromstring(base64_decode($file)) !== false ) {
            $f = finfo_open();
            $type = finfo_buffer($f, $image, FILEINFO_MIME_TYPE);

            if ($type) {
                $type = explode('/', $type)[1];
         }
         if (!empty($customFileName)) {
             $newFileName = $customFileName . '-'. time() . '.' . $type;
         }else{
            $newFileName = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(10/strlen($x)) )),1,10) . time() . '.' . $type;
         }
         $fullRelativePath = $newFileName;
         if ($customDirectoryPath) {
            $fullRelativePath = "/" . $customDirectoryPath . '/' . $newFileName;
        }
        $isSuccess = file_put_contents($directoryPath.$fullRelativePath, $image);
        if ($isSuccess) {
            $retVal = $fullRelativePath;
        }
    }

    return $retVal;
    }
}
