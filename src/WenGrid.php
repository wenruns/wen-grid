<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/26
 * Time: 15:27
 */

namespace Wenruns\Grid;


use Closure;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Input;
use Encore\Admin\Exception\Handler;

class WenGrid extends Grid
{

    protected $wenOptins = [
        'show_import' => false
    ];

    protected $selectAllPages = true;

    protected $showRowSelector = true;


    public function __construct(Eloquent $model, Closure $builder = null)
    {
        parent::__construct($model, $builder);
        $this->setupFilter();
    }


    public function selectedRowsAllPage($allPage = true)
    {
        $this->selectAllPages = $allPage;
        return $this;
    }


    public function wenOptions($key, $value = null)
    {
        if (is_null($value)) {
            return $this->wenOptins[$key];
        }
        $this->wenOptins[$key] = $value;
        return $this;
    }


    public function showImporterBtn()
    {
        return $this->wenOptions('show_import');
    }

    public function showImporter($show = true)
    {
        return $this->wenOptions('show_import', $show);
    }

    public function renderExportButton()
    {
        return (new WenExportButton($this))->render();
    }

    public function getImportUrl()
    {
        return $this->getExportUrl() . 'import';
    }

    protected function handleImportRequest($scope)
    {
        if ($scope != 'import') {
            return;
        }
        $this->exporter->importRun($this);
    }

    /**
     * @param bool $forceExport
     * @return bool|mixed|void
     */
    protected function handleExportRequest($forceExport = false)
    {
        if (!$scope = request(Grid\Exporter::$queryName)) {
            return;
        }
        $user = Admin::user();
        if ($user && in_array($user->staff_code, ['0021'])) {
            echo '<script>window.opener=null;window.open(\'\',\'_self\');window.close()</script>';
            exit(1);
        }
        // clear output buffer.
        if (ob_get_length()) {
            ob_end_clean();
        }

        $this->model()->usePaginate(false);

        if ($this->builder) {
            call_user_func($this->builder, $this);

            return $this->getExporter($scope)->export();
        }
        if ($forceExport) {
            $this->handleImportRequest($scope);

            $res = $this->getExporter($scope)->export();
            if (is_array($res)) {
                echo json_encode($res);
            } else {
                echo $res;
            }
            exit();
        }
    }


    public function render()
    {
//        $this->handleSelectedAllPages();
        $this->handleExportRequest(true);
        try {
            $this->build();
        } catch (\Exception $e) {
            return Handler::renderException($e);
        }
        return view($this->view, $this->variables())->render();
    }

    protected function handleSelectedAllPages()
    {
        if ($this->showRowSelector && $this->selectAllPages) {
            $script = <<<SCRIPT
window.onload = function(){
    let leftObj = document.querySelector('.pull-left'), selectedBtn = document.createElement('div'), pullSelected =document.querySelector('.grid-select-all-btn');
    console.log(leftObj, pullSelected);
    selectedBtn.innerHtml = '全选';
    selectedBtn.classList.value = 'btn-group btn btn-default btn-sm';
    leftObj.insertBefore(selectedBtn, pullSelected);
}
SCRIPT;
            Admin::script($script);
        }
    }

    /**
     * @return string
     */
    public function getWenExporter()
    {
        return $this->exporter;
    }

    public function disableRowSelector(bool $disable = true)
    {
        $this->showRowSelector = !$disable;
        return parent::disableRowSelector($disable); // TODO: Change the autogenerated stub
    }


    /**
     * Setup grid filter.
     *
     * @return void
     */
    protected function setupFilter()
    {
        $this->filter = new WenFilter($this->model());
    }


    /**
     * Set exporter driver for Grid to export.
     * @param $exporter
     * @return $this
     * @throws \Exception
     */
    public function exporter($exporter)
    {
        if (!($exporter instanceof WenAbstractExporter)) {
            throw new \Exception('The param of the function "exporter" should be an instance of ' . get_class(new WenAbstractExporter()) . ', but ' . get_parent_class($exporter) . ' given.');
        }
        $this->exporter = $exporter;
        return $this;
    }

}