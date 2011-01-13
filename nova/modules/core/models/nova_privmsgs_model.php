<?php
/**
 * Private messages model
 *
 * @package		Nova
 * @category	Model
 * @author		Anodyne Productions
 * @copyright	2010-11 Anodyne Productions
 * @version		1.0
 */

abstract class Nova_privmsgs_model extends Model {

	public function __construct()
	{
		parent::Model();
		
		$this->load->dbutil();
	}

	/**
	 * Retrieve methods
	 */
	
	function get_inbox($id = '')
	{
		$this->db->from('privmsgs_to');
		$this->db->join('privmsgs', 'privmsgs.privmsgs_id = privmsgs_to.pmto_message');
		$this->db->where('pmto_recipient_user', $id);
		$this->db->where('pmto_display', 'y');
		$this->db->order_by('privmsgs_date', 'desc');
		
		$query = $this->db->get();
		
		return $query;
	}
	
	function get_outbox($id = '')
	{
		$this->db->from('privmsgs');
		$this->db->where('privmsgs_author_user', $id);
		$this->db->where('privmsgs_author_display', 'y');
		$this->db->order_by('privmsgs_date', 'desc');
		
		$query = $this->db->get();
		
		return $query;
	}
	
	function get_message_recipients($id = '')
	{
		$query = $this->db->get_where('privmsgs_to', array('pmto_message' => $id));
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$array[] = $row->pmto_recipient_user;
			}
			
			return $array;
		}
		
		return FALSE;
	}
	
	function get_message($id = '')
	{
		$this->db->from('privmsgs_to');
		$this->db->join('privmsgs', 'privmsgs.privmsgs_id = privmsgs_to.pmto_message');
		$this->db->where('privmsgs_id', $id);
		
		$query = $this->db->get();
		
		return $query;
	}
	
	function get_messages_for_id($id = '')
	{
		$query = $this->db->get_where('privmsgs_to', array('pmto_message' => $id));
		
		return $query;
	}
	
	/**
	 * Count methods
	 */
	
	function count_unread_pms($id = '')
	{
		$this->db->from('privmsgs_to');
		$this->db->where('pmto_recipient_user', $id);
		$this->db->where('pmto_display', 'y');
		$this->db->where('pmto_unread', 'y');
		
		return $this->db->count_all_results();
	}
	
	/**
	 * Create methods
	 */
	
	function insert_pm_recipients($data = '')
	{
		$query = $this->db->insert('privmsgs_to', $data);
		
		/* optimize the table */
		$this->dbutil->optimize_table('privmsgs_to');
		
		/* return the number of affected rows to show success/failure (should be 1) */
		return $this->db->affected_rows();
	}
	
	function insert_private_message($data = '')
	{
		$query = $this->db->insert('privmsgs', $data);
		
		/* return the number of affected rows to show success/failure (should be 1) */
		return $this->db->affected_rows();
	}
	
	/**
	 * Update methods
	 */
	
	function update_message($id = '', $user = '', $data = '')
	{
		$this->db->where('pmto_message', $id);
		$this->db->where('pmto_recipient_user', $user);
		$query = $this->db->update('privmsgs_to', $data);
		
		return $query;
	}
	
	function update_private_message($id = '', $data = '')
	{
		$this->db->where('privmsgs_id', $id);
		$query = $this->db->update('privmsgs', $data);
		
		return $query;
	}
	
	function update_to_message($id = '', $user = '', $data = '')
	{
		$this->db->where('pmto_id', $id);
		$this->db->where('pmto_recipient_user', $user);
		$query = $this->db->update('privmsgs_to', $data);
		
		return $query;
	}
}