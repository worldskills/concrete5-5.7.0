<?
namespace Concrete\Core\Search\Result;
use \Concrete\Core\Search\Column\Set;
use \Concrete\Core\Search\ListItemInterface;
use Pagerfanta\View\DefaultView;
use stdClass;

class Result {

	protected $listColumns;
	protected $list;
	protected $baseURL;
	protected $pagination;

	protected $items;
	protected $fields;
	protected $columns;

	public function getItemListObject() {return $this->list;}

	public function setBaseURL($url) {
		$this->baseURL = $url;
	}

	public function getBaseURL() {
		return $this->baseURL;
	}

	public function __construct(Set $columns, ListItemInterface $il, $url, $fields = array()) {
		$this->listColumns = $columns;
		$this->list = $il;
		$this->baseURL = $url;
		$this->fields = $fields;
        $this->pagination = $il->getPagination();
	}

	public function getItems() {
		if (!isset($this->items)) {
			$this->items = array();
			$items = $this->pagination->getCurrentPageResults();
			foreach($items as $item) {
				$node = $this->getItemDetails($item);
				$this->items[] = $node;
			}
		}
		return $this->items;
	}

	public function getColumns() {
		if (!isset($this->columns)) {
			$this->columns = array();
			foreach($this->listColumns->getColumns() as $column) {
				$node = $this->getColumnDetails($column);
				$this->columns[] = $node;
			}
		}
		return $this->columns;
	}

	public function getColumnDetails($column) {
		$node = new Column($this, $column);
		return $node;
	}

	public function getItemDetails($item) {
		$node = new Item($this, $this->listColumns, $item);
		return $node;
	}

	public function getJSONObject() {
		$obj = new stdClass;
		$obj->items = array();
		foreach($this->getItems() as $item) {
			$obj->items[] = $item;
		}
		foreach($this->getColumns() as $column) {
			$obj->columns[] = $column;
		}

        $view = new DefaultView();
        $options = array('proximity' => 3);
        $html = $view->render($this->pagination, function($page) {
            return '/derp?page=' . $page;
        }, $options);

		$obj->paginationTemplate = $html;
		$obj->fields = $this->fields;
		return $obj;
	}


}
