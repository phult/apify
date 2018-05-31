<?php
namespace Megaads\Apify\FilterBuilders;

abstract class FilterBuilder {
    protected $level = 0;
    protected $name = '';
    public function getLevel() {
        return $this->level;
    }
    public function getName() {
        return $this->name;
    }
    public function setName($name) {
        return $this->name = $name;
    }
    abstract function buildQueryParam($queryParam);
    abstract function buildQuery($query, $filters);
}