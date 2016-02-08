<?php

namespace AppBundle\Filter;

use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Common\Annotations\Reader;

class GenericSQLFilter extends SQLFilter
{
    protected $reader;
    protected $primaryTargetTableAlias;
    protected $filter_parameters;
    protected $em;

    public function setEntityManager($em){
        $this->em = $em;
    }

    public function setFilterParameters($params=array()){
        $this->filter_parameters = $params;
    }

    public function reset()
    {
        $this->primaryTargetTableAlias = null;
    }

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!$this->filter_parameters) return '';
        if (!$this->getParameter('id')) return '';

        $entity_id = $this->getParameter('id');

        $ignore_classes = array();
        if (array_key_exists('ignore_class', $this->filter_parameters)){
            $ignore_classes = $this->filter_parameters['ignore_class'];
        }

        if (in_array($targetEntity->getName(), $ignore_classes)){
            return '';
        }

        $filter = '';
        $arrFilters = array();

        if ($this->filter_parameters['target_class'] == $targetEntity->getName()){
            $identifierFieldName = $targetEntity->getIdentifierColumnNames()[0];
            $arrFilters[$identifierFieldName] = $entity_id;
        }else {
            $associationMappings = $targetEntity->getAssociationsByTargetClass($this->filter_parameters['target_class']);
            foreach ($associationMappings as $key => $mapping){
                if ($mapping['type'] == 2) {
                    foreach ($mapping['joinColumns'] as $keycolumn => $joinColumn) {
                        $columnname = $joinColumn['name'];
                        $arrFilters[$columnname] = $entity_id;
                    }
                }

            }

        }


        if (empty($this->reader)) {
            return '';
        }

        $strWhere = "";
        foreach($arrFilters as $key=>$value){
            $strWhere .= ($strWhere != "") ? " AND " : "";
            $strWhere .= sprintf('%s.%s = %s', $targetTableAlias, $key, $value);
        }

        return $strWhere;

    }

    public function setAnnotationReader(Reader $reader)
    {
        $this->reader = $reader;
    }
}
