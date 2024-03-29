<?PHP 

require_once('api/Simpla.php');

########################################
class DealsAdmin extends Simpla
{
	public function fetch()
	{
	 	$filter = array();
	  	$filter['page'] = max(1, $this->request->get('page', 'integer'));
	  		
	  	$filter['limit'] = 40;
	  	
	    // Поиск
	  	$keyword = $this->request->get('keyword', 'string');
	  	if(!empty($keyword))
	  	{
		  	$filter['keyword'] = $keyword;
	 		$this->design->assign('keyword', $keyword);
		}

		// Фильтр по метке
	  	$label = $this->deals->get_label($this->request->get('label'));	  	
	  	if(!empty($label))
	  	{
		  	$filter['label'] = $label->id;
		 	$this->design->assign('label', $label);
		}


		// Обработка действий
		if($this->request->method('post'))
		{

			// Действия с выбранными
			$ids = $this->request->post('check');
			if(is_array($ids))
			switch($this->request->post('action'))
			{
				case 'delete':
				{
					foreach($ids as $id)
					{
						$o = $this->deals->get_order(intval($id));
						if($o->status<3)
						{
							$this->deals->update_order($id, array('status'=>3));
							$this->deals->open($id);							
						}
						else
							$this->deals->delete_order($id);
					}
					break;
				}
				case 'set_status_0':
				{
					foreach($ids as $id)
					{
						if($this->deals->open(intval($id)))
							$this->deals->update_order($id, array('status'=>0));	
					}
					break;
				}
				case 'set_status_1':
				{
					foreach($ids as $id)
					{
						if(!$this->deals->close(intval($id)))
							$this->design->assign('message_error', 'error_closing');
						else
							$this->deals->update_order($id, array('status'=>1));	
					}
					break;
				}
				case 'set_status_2':
				{
					foreach($ids as $id)
					{
						if(!$this->deals->close(intval($id)))
							$this->design->assign('message_error', 'error_closing');
						else
							$this->deals->update_order($id, array('status'=>2));	
					}
					break;
				}
				case(preg_match('/^set_label_([0-9]+)/', $this->request->post('action'), $a) ? true : false):
				{
					$l_id = intval($a[1]);
					if($l_id>0)
					foreach($ids as $id)
					{
						$this->deals->add_order_labels($id, $l_id);
					}
					break;
				}
				case(preg_match('/^unset_label_([0-9]+)/', $this->request->post('action'), $a) ? true : false):
				{
					$l_id = intval($a[1]);
					if($l_id>0)
					foreach($ids as $id)
					{
						$this->deals->delete_order_labels($id, $l_id);
					}
					break;
				}
			}
		}		

		if(empty($keyword))
		{
			$status = $this->request->get('status', 'integer');
			$filter['status'] = $status;
		 	$this->design->assign('status', $status);
		}
				  	
	  	$orders_count = $this->deals->count_orders($filter);
		// Показать все страницы сразу
		if($this->request->get('page') == 'all')
			$filter['limit'] = $orders_count;	

		// Отображение
		$orders = array();
		foreach($this->deals->get_orders($filter) as $o)
			$orders[$o->id] = $o;

		foreach($orders as $o)
		{
		   $this->db->query("SELECT bot FROM __users WHERE id=? LIMIT 1", $o->user_id);
		   $is_bot = $this->db->result('bot');
		   $o->is_bot = $is_bot;
		}
	 	
		// Метки заказов
		$orders_labels = array();
	  	foreach($this->deals->get_order_labels(array_keys($orders)) as $ol)
	  		$orders[$ol->order_id]->labels[] = $ol;
	  	
	 	$this->design->assign('pages_count', ceil($orders_count/$filter['limit']));
	 	$this->design->assign('current_page', $filter['page']);
	  	
	 	$this->design->assign('deals_count', $orders_count);
	
	 	$this->design->assign('deals', $orders);
	
		// Метки заказов
	  	$labels = $this->deals->get_labels();
	 	$this->design->assign('labels', $labels);
	  	
		return $this->design->fetch('deals.tpl');
	}
}
