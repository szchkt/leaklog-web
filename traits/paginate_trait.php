<?

trait PaginateTrait {

	private $paginate_row_count = 0;
	private $paginate_current_page = 0;
	protected $per_page = 1000;

	public function page($page, $field = null) {
		if ($field === null)
			$field = $this->table().'.id';

		$model = clone $this;
		$this->paginate_row_count = $model->count_rows($field);
		$this->paginate_current_page = intval($page);

		$this->offset($this->paginate_current_page * $this->per_page)->limit($this->per_page);

		return $this;
	}

	public function paginate($page = 'page') {
		global $controller;

		$page_count = ceil($this->paginate_row_count / $this->per_page);
		if ($page_count < 2)
			return '';

		$pages = [];
		for ($i = 0; $i < $page_count; $i++) {
			if ($i == $this->paginate_current_page)
				$pages[] = $i + 1;
			else
				$pages[] = link_tag($i + 1, $controller->current_url([$page => $i]));
		}
		return implode(' | ', $pages);
	}

}

?>