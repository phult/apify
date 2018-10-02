<?php
namespace Megaads\Apify\FilterBuilders;

class FilterBuilderManagement
{
    protected static $instance = null;
    protected $builders = [];

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new FilterBuilderManagement();
            self::$instance->loadBuilders();
        }
        return self::$instance;
    }

    private function loadBuilders()
    {
        $path = __DIR__ . '/impl';
        if (is_dir($path)) {
            foreach (scandir($path) as $file) {
                if (strpos($file, 'Builder.php') !== false) {
                    $builderClass = 'Megaads\Apify\FilterBuilders\Impl\\' . basename($file, '.php');
                    $builder = new $builderClass;
                    $builder->setName(strtolower(explode('Builder.php', $file)[0]));
                    $builders[] = $builder;
                }
            }
            // Sort builders by level
            for ($i = 0; $i < count($builders) - 1; $i++) {
                for ($j = $i + 1; $j < count($builders); $j++) {
                    if (($builders[$j]->getLevel()) < ($builders[$i]->getLevel())) {
                        $swapTemp = $builders[$j];
                        $builders[$j] = $builders[$i];
                        $builders[$i] = $swapTemp;
                    }
                }
            }
            $this->builders = $builders;
        }
    }

    public function getBuilder($name)
    {
        $retval = null;
        foreach ($this->builders as $filterBuilder) {
            if ($filterBuilder->getName() == $name) {
                $retval = $filterBuilder;
                break;
            }
        }
        return $retval;
    }

    public function buildQueryParams($request)
    {
        $retval = [];
        if ($request->has('filters')) {
            $params = [];
            $filterString = $request->input('filters');
            $filterStringLength = strlen($filterString);
            $paramBuffer = "";
            $brackets = 0;
            for ($i = 0; $i < $filterStringLength; $i++) {
                $char = substr($filterString, $i, 1);
                if ($char != ',') {
                    $paramBuffer .= $char;
                    if ($char == '(') {
                        $brackets++;
                    }
                    if ($char == ')') {
                        $brackets--;
                    }
                } else {
                    if ($brackets == 0) {
                        $params[] = $paramBuffer;
                        $paramBuffer = "";
                        $brackets = 0;
                    } else {
                        $paramBuffer .= $char;
                    }
                }
            }
            $params[] = $paramBuffer;
            foreach ($params as $param) {
                foreach ($this->builders as $filterBuilder) {
                    $filter = $filterBuilder->buildQueryParam($param);
                    if ($filter === false) {
                    } else {
                        $filter['operator'] = $filterBuilder->getName();
                        $retval[] = $filter;
                        break;
                    }
                }
            }
        }
        return $retval;
    }

    public function buildQuery($query, $filters)
    {
        $entity = $query->getModel()->getTable();
        foreach ($filters as $filter) {
            $field = $filter['field'];
            $operator = $filter['operator'];
            $value = $filter['value'];
            $tableAlias = $entity;
            $fieldExp = explode('.', $filter['field']);
            if (count($fieldExp) == 1) {
                $filter['field'] = $entity . '.' . $field;
            } else if (count($fieldExp) == 2) {
                $tableAlias = $fieldExp[0];
                $field = $fieldExp[1];
            }
            $builder = $this->getBuilder($operator);
            if ($builder != null) {
                if ($tableAlias != $query->getModel()->getTable()) {
                    $filter['field'] = $field;
                    $query = $query->whereHas($tableAlias, function ($query) use ($filter, $builder) {
                        $query = $builder->buildQuery($query, $filter);
                    });
                } else {
                    $query = $builder->buildQuery($query, $filter);
                }
            }
        }
        return $query;
    }
}
