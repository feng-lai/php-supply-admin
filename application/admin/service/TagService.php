<?php
namespace app\admin\service;

use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use think\Db;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\response\Json;
use app\admin\service\BaseService;
use app\admin\model\User;
use app\admin\model\UserArchive;
use app\admin\model\Specialist;
use app\admin\model\Tag;


class TagService extends BaseService
{

    public function tags(){
        
    }
    
}