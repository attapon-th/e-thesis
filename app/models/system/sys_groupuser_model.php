<?php

namespace EThesis\Models\System;


class Sys_groupuser_model extends \EThesis\Library\Adodb
{

    var $schema = 'system';
    var $table = 'SYS_GROUP';
    var $primary = 'GRP_ID';

    var $use_view = FALSE;

    var $field_insert = ['GRP_NAME_TH', 'GRP_NAME_EN', 'ENABLE', 'GRP_TOPMENU', 'GRP_USER_TYPE'];
    var $field_update = ['GRP_NAME_TH', 'GRP_NAME_EN', 'ENABLE', 'GRP_TOPMENU', 'GRP_USER_TYPE'];

    var $date_current;
    var $user_access;
    var $user_group;
    var $user_type;

    var $lang = 'TH';


    public function initialize()
    {
        parent::__construct();

        $this->adodb->debug = TRUE;

        $sess = \EThesis\Library\DIPhalcon::get('sess');

        $this->lang = strtoupper($sess->get('lang'));

        $this->date_current = $this->adodb->sysTimeStamp;
        $this->user_access = ($sess->has('username') ? $sess->get('username') : '');
        $this->user_group = ($sess->has('usergroup') ? $sess->get('usergroup') : '');
        $this->user_type = ($sess->has('usertype') ? $sess->get('usertype') : '');
    }

    private function check_filter(array $filter)
    {
        $sql = "RECORD_STATUS='N'";
        if (empty($filter)) {

        } else if (is_array($filter)) {
            $sql .= (isset($filter['GRP_NAME_TH']) ? " AND MOD_NAME_TH LIKE '%{$filter['MOD_NAME_TH']}%'" : '');
            $sql .= (isset($filter['GRP_NAME_EN']) ? " AND MOD_NAME_EN LIKE '%{$filter['MOD_NAME_EN']}%'" : '');
            $sql .= (isset($filter['GRP_TOPMENU']) ? " AND GRP_TOPMENU = '{$filter['GRP_TOPMENU']}'" : '');
            $sql .= (isset($filter['GRP_USER_TYPE']) ? " AND GRP_USER_TYPE = '{$filter['GRP_USER_TYPE']}'" : '');
            $sql .= (isset($filter['ENABLE']) ? " AND ENABLE = '{$filter['ENABLE']}'" : '');

            $sql .= (isset($filter['IN_ID']) ? " AND {$this->primary} IN ({$filter['IN_ID']})" : '');
            $sql .= (isset($filter['NOT_IN_ID']) ? " AND {$this->primary} NOT IN ({$filter['IN_ID']})" : '');

            $sql .= (isset($filter['OR_USER_TYPE']) ? " OR GRP_USER_TYPE = '{$filter['GRP_USER_TYPE']}'" : '');

        }
        return $sql;
    }

    public function count_by_filter(array $filters = array())
    {
        $sql = 'SELECT COUNT(*) ';
        $sql .= "FROM " . ($this->use_view !== FALSE ? "{$this->schema}.{$this->use_view}_{$this->table}" : "{$this->schema}.{$this->table}");
        $sql .= " WHERE " . $this->check_filter($filters);
        $num = $this->adodb->GetOne($sql);
        return $num;
    }

    public function select_by_filter(array $field = array(), array $filters = array(), $order = FALSE, $limit = FALSE, $offset = FALSE)
    {
        $sql_field = (empty($field) ? '*' : implode(',', $field));
        $sql = "SELECT  {$sql_field} ";
        $sql .= "FROM " . ($this->use_view !== FALSE ? "{$this->schema}.{$this->use_view}_{$this->table}" : "{$this->schema}.{$this->table}");
        $sql .= " WHERE " . $this->check_filter($filters);
        $sql .= ($order != FALSE ? "ORDER BY {$order}" : '');
        $result = ($limit == FALSE ? $this->adodb->Execute($sql) : $this->adodb->SelectLimit($sql, $limit, $offset));

        return $result;
    }

    public function select_default_group($group_type)
    {
        $result = $this->select_by_filter([$this->primary . ' AS value', 'GRP_NAME_' . $this->lang . ' AS title'], ['GRP_USER_TYPE' => $group_type, 'ENABLE' => 'T']);
        $row = (is_object($result) ? $result->GetRows() : false);
        return $row;
    }


    public function select_user_group($group_id)
    {
        $result = $this->select_by_filter([$this->primary . ' AS value', 'GRP_NAME_' . $this->lang . ' AS title'], ['IN_ID' => $group_id, 'ENABLE' => 'T']);
        $row = (is_object($result) ? $result->GetRows() : false);
        return $row;
    }


    public function insert(array $arrInsert)
    {
        $sql_field = '';
        $sql_value = '';
        foreach ($this->field_insert as $field) {
            if (isset($arrInsert[$field])) {
                $sql_field .= "{$field},";
                $sql_value .= "'{$arrInsert[$field]}',";
            }
        }
        $sql_field .= "RECORD_STATUS, CREATE_DATE, CREATE_USER, CREATE_USER_TYPE, LAST_DATE, LAST_USER, LAST_USER_TYPE";
        $sql_value .= "'N','{$this->date_current}','{$this->user_access}','{$this->user_type}','{$this->date_current}','{$this->user_access}','{$this->user_type}'";
        $sql = "INSERT INTO {$this->schema}.{$this->table} ({$sql_field}) VALUES ({$sql_value})";
        $sql .= ";";
        $result = $this->adodb->Execute($sql);
        return $result;

    }

    public function update(array $arrUpdate, $id)
    {
        $sql = "UPDATE {$this->schema}.{$this->table} SET ";
        foreach ($this->field_update as $field) {
            if (isset($arrUpdate[$field])) {
                $sql .= "{$field}='{$arrUpdate[$field]}',";
            } else {
                $sql .= "{$field}='',";
            }
        }
        $sql .= "LAST_DATE='{$this->date_current}', LAST_USER='{$this->user_access}', LAST_USER_TYPE='{$this->user_type}'";
        $sql .= "WHERE {$this->primary}='$id'";
        $sql .= ";";

        $result = $this->adodb->Execute($sql);
        return $result;
    }

    public function delete($id){
        $sql = "UPDATE  {$this->schema}.{$this->table} SET RECORD_STATUS='D' ";
        $sql .= "LAST_DATE='{$this->date_current}', LAST_USER='{$this->user_access}', LAST_USER_TYPE='{$this->user_type}'";
        $result = $this->adodb->Execute($sql);
        return $result;
    }


}