<?PHP
require_once('api/Simpla.php');

class DealsLabelAdmin extends Simpla
{	
	public function fetch()
	{	
		$label = new stdClass;
		$label->color = 'ffffff';
		if($this->request->method('POST'))
		{
			$label->id = $this->request->post('id', 'integer');
			$label->name = $this->request->post('name');
			$label->color = $this->request->post('color');
			if(empty($label->id))
			{
  				$label->id = $this->deals->add_label($label);
  				$label = $this->deals->get_label($label->id);
  				$this->design->assign('message_success', 'added');
			}
			else
			{
				$this->deals->update_label($label->id, $label);
				$label = $this->deals->get_label($label->id);
				$this->design->assign('message_success', 'updated');
			}
		}
		else
		{
			$id = $this->request->get('id', 'integer');
			if(!empty($id))
				$label = $this->deals->get_label(intval($id));			
		}	

		$this->design->assign('label', $label);
		
 	  	return $this->design->fetch('deals_label.tpl');
	}
	
}

