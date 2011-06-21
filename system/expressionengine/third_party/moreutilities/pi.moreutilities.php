<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
						'pi_name'			=> 'moreUtilities',
						'pi_version'		=> '1.1',
						'pi_author'			=> 'Christopher Imrie',
						'pi_author_url'		=> 'http://moresoda.co.uk/',
						'pi_description'	=> 'ExpressionEngine 2.0 utility tags for use in templates.'
					);
					
					
class Moreutilities{

	var $member_custom_fields 	= FALSE;
	var $member_cache 			= array();
	
	function __construct()
	{
		$this->EE =& get_instance(); 
		
		
	}
	
	
	/**
	 * Returns custom member data for use in templates
	 *
	 * @return void
	 * @author Christopher Imrie
	 */
	public function member_custom_field()
	{
		//If this is the first time the plugin is used, initialize the custom field array
		if(!$this->member_custom_fields){
			$this->_member_custom_field_init();
		}
		
		//Fetch params
		$author_id = $this->EE->TMPL->fetch_param('member_id');
		$field_name = $this->EE->TMPL->fetch_param('field');
		
		//If either the author id of field id has not been specified, bail out
		if( ! $author_id || ! $field_name){
			return '1';
		}
		
		
		//Have retrieved this members data before?
		if(count($this->member_cache) > 0 && array_key_exists($author_id, $this->member_cache)){
			return $this->member_cache[$author_id][$field_name];
		}
		
		//Grab the member field data
		$this->EE->db->select('*');
		$this->EE->db->where('member_id', $author_id);
		$q = $this->EE->db->get('member_data');
		
		//Bail out if there isnt any
		if($q->num_rows() == 0){
			return '3';
		}
		
		//Just need a single row for the specified author
		$data = $q->result_array();
		$data = $data[0];
		
		//Cycle through the fields, check they exist in the member field data, add it to the cache
		foreach($data  as $key => $value){
			if(array_key_exists($key, $this->member_custom_fields)){
				$this->member_cache[$author_id][$this->member_custom_fields[$key]['name']] = $value;
			}
		}
		
		//Final check to see if the field name exists for the author specified
		if(isset($this->member_cache[$author_id][$field_name])){
			return $this->member_cache[$author_id][$field_name];
		}else{
			return;
		}
		
		
	}
	
	/**
	 * Builds an array representing all the member custom fields
	 *
	 * @return void
	 * @author Christopher Imrie
	 */
	public function _member_custom_field_init()
	{
		$this->EE->db->select('*');
		$q = $this->EE->db->get('member_fields');
		
		//Bail out if no results
		if($q->num_rows() == 0){
			return;
		}
		
		//Build an array of all the custom fields
		$this->member_custom_fields = array();
		foreach($q->result() as $row){
			$this->member_custom_fields['m_field_id_' . $row->m_field_id] = array(
				'label' 		=> $row->m_field_label,
				'id' 			=> $row->m_field_id,
				'name'			=> $row->m_field_name
			);
		}
	}
	
	
	/**
	 * Returns category id from a given category url slug
	 *
	 * @return void
	 * @author Christopher Imrie
	 */
	public function category_url_to_id()
	{
		$cat_url_title = $this->EE->TMPL->fetch_param('cat_url_title');
		
		if(!$cat_url_title) return;
		
		$this->EE->db->select('cat_id, cat_url_title');
		$this->EE->db->where('cat_url_title', $cat_url_title);
		$this->EE->db->limit(1);
		$q = $this->EE->db->get('categories');
		
		if($q->num_rows() == 0) return;
		
		$row = $q->row();
		
		return $row->cat_id;
	}
	
	
	/**
	 * Return the category id for the last uri segment
	 *
	 * @return void
	 * @author Christopher Imrie
	 */
	public function last_segment_to_cat_id()
	{
		
		$cat_url_title = array_pop($this->EE->uri->segment_array());
		
		if(!$cat_url_title) return;
		
		$this->EE->db->select('cat_id, cat_url_title');
		$this->EE->db->where('cat_url_title', $cat_url_title);
		$this->EE->db->limit(1);
		$q = $this->EE->db->get('categories');
		
		if($q->num_rows() == 0) return;
		
		$row = $q->row();
		
		return $row->cat_id;
	}
	
	
	
	
	public function member_id_to_name()
	{
		$member_id = $this->EE->TMPL->fetch_param('member_id');
		
		if(!$member_id) return;
		
		$this->EE->db->select('screen_name');
		$this->EE->db->where('member_id', $member_id);
		$this->EE->db->limit(1);
		$q = $this->EE->db->get('members');
		
		if($q->num_rows() == 0) return;
		
		$row = $q->row();
		
		return $row->screen_name;
	}
	
	
	/**
	 * Returns a category name from a given category url slug
	 *
	 * @return void
	 * @author Christopher Imrie
	 */
	public function cat_url_to_name()
	{
		$cat_url_title = $this->EE->TMPL->fetch_param('cat_url_title');
		
		if(!$cat_url_title) return;
		
		$this->EE->db->select('cat_name, cat_url_title');
		$this->EE->db->where('cat_url_title', $cat_url_title);
		$this->EE->db->limit(1);
		$q = $this->EE->db->get('categories');
		
		if($q->num_rows() == 0) return;
		
		$row = $q->row();
		
		return $row->cat_name;
	}
	
	
	
	public function prev_entry_url_title()
	{
		
		//Gather params
		$cat_url = $this->EE->TMPL->fetch_param('cat_url_title');
		$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		
		//Fetch the current article being shown
		// Although this is redundant, it avoids date and timezone correction issues
		
		$this->EE->db->select('entry_id, entry_date');
		$this->EE->db->limit(1);
		$this->EE->db->where('entry_id', $entry_id);
		$r = $this->EE->db->get('channel_titles');
		
		//WTF?  Something went wrong....
		if($r->num_rows() == 0) return;
		
		$current_entry = $r->row();
		
		
		
		
		//Main query
		$this->EE->db->select(
			'channel_titles.entry_id, 
			channel_titles.title, 
			channel_titles.url_title, 
			channel_titles.status, 
			channel_titles.entry_date,
			category_posts.entry_id,
			category_posts.cat_id,
			categories.cat_id,
			categories.cat_name,
			categories.cat_url_title ');
			
		$this->EE->db->from('channel_titles');
		$this->EE->db->join('category_posts', 'channel_titles.entry_id = category_posts.entry_id');
		$this->EE->db->join('categories', 'category_posts.cat_id = categories.cat_id');
		$this->EE->db->where('channel_titles.entry_id !=', $current_entry->entry_id);
		$this->EE->db->where('channel_titles.entry_date <',  $current_entry->entry_date);
		$this->EE->db->where('categories.cat_url_title', $cat_url);
		$this->EE->db->where('status', 'open');
		$this->EE->db->order_by('channel_titles.entry_date', 'desc');
		$this->EE->db->limit(1);
		
		$q = $this->EE->db->get();
		
		if($q->num_rows() == 0){
			return;
		}
	
		$row = $q->row();
		
	
		return $row->url_title;
		
	}
	
	
	public function next_entry_url_title()
	{
		//Gather params
		$cat_url = $this->EE->TMPL->fetch_param('cat_url_title');
		$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		
		//Fetch the current article being shown
		// Although this is redundant, it avoids date and timezone correction issues
		
		$this->EE->db->select('entry_id, entry_date');
		$this->EE->db->limit(1);
		$this->EE->db->where('entry_id', $entry_id);
		$r = $this->EE->db->get('channel_titles');
		
		//WTF?  Something went wrong....
		if($r->num_rows() == 0) return;
		
		$current_entry = $r->row();
		
		
		
		
		//Main query
		$this->EE->db->select(
			'channel_titles.entry_id, 
			channel_titles.title, 
			channel_titles.url_title, 
			channel_titles.status, 
			channel_titles.entry_date,
			category_posts.entry_id,
			category_posts.cat_id,
			categories.cat_id,
			categories.cat_name,
			categories.cat_url_title ');
			
		$this->EE->db->from('channel_titles');
		$this->EE->db->join('category_posts', 'channel_titles.entry_id = category_posts.entry_id');
		$this->EE->db->join('categories', 'category_posts.cat_id = categories.cat_id');
		$this->EE->db->where('channel_titles.entry_id !=', $current_entry->entry_id);
		$this->EE->db->where('channel_titles.entry_date >',  $current_entry->entry_date);
		$this->EE->db->where('categories.cat_url_title', $cat_url);
		$this->EE->db->where('status', 'open');
		$this->EE->db->order_by('channel_titles.entry_date', 'asc');
		$this->EE->db->limit(1);
		
		$q = $this->EE->db->get();
		
		if($q->num_rows() == 0){
			return;
		}
	
		$row = $q->row();
		
		
		return $row->url_title;
		
	}
	
	
	
	public function cache_buster()
	{
		$this->EE->load->helper('file');
		
		//Cache buster file already exists?  If so, read it and return it, else create it
		if($cache_buster_time = read_file(APPPATH."cache/page_cache/cache_buster")){
			return "?" . $cache_buster_time;
		}else{
			
			$new_time = time();
			write_file(APPPATH."cache/page_cache/cache_buster", $new_time);
			return "?".$new_time;
		}
	}
	
	
	
	/**
	 * Allows access to sanitized post data from templates
	 * 
	 * @author Christopher Imrie
	 * Apr 14, 2011
	 */
	public function post()
	{

		$key = $this->EE->TMPL->fetch_param('key');

		return $this->EE->input->post($key, true);
	}


	/**
	 * Allows access to sanitized Get data from templates
	 * 
	 * @author Christopher Imrie
	 * Apr 14, 2011
	 */
	public function get()
	{
		$key = $this->EE->TMPL->fetch_param('key');

		return $this->EE->input->get($key, true);
	}
}

/* End of file pi.plugin_name.php */ 
/* Location: ./system/expressionengine/third_party/utilities/pi.utilities.php */