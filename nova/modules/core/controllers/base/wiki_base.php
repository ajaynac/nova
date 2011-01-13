<?php
/**
 * Wiki controller
 *
 * @package		Nova
 * @category	Controller
 * @author		Anodyne Productions
 * @copyright	2010-11 Anodyne Productions
 * @version		1.3
 *
 * Updated the flash message so they can be overridden by seamless substitution,
 * updated the wiki page management method to be able to clean up old drafts based
 * on what the admin selects for clean up, updated thresher with system pages and
 * the ability to edit and revert system pages
 */

class Wiki_base extends Controller {

	// set the variables
	var $options;
	var $skin;
	var $rank;
	var $timezone;
	var $dst;
	
	function Wiki_base()
	{
		parent::Controller();
		
		// load the system model
		$this->load->model('system_model', 'sys');
		$installed = $this->sys->check_install_status();
		
		if ($installed === FALSE)
		{
			redirect('install/index', 'refresh');
		}
		
		if (floor(phpversion()) < 5)
		{
			show_error("Due to a bug in Thresher we have been unable to identify, you must be running at least PHP 5.0 or higher on your server in order to use Nova's mini-wiki feature. We apologize for this inconvenience and will continue to troubleshoot the bug to find a resolution that will allow PHP 4 servers to run Thresher. If you have any questions, please contact <a href='http://www.anodyne-productions.com' target='_blank'>Anodyne Productions</a>.");
		}
		
		// load the libraries
		$this->load->library('session');
		$this->load->library('thresher');
		
		// load the models
		$this->load->model('characters_model', 'char');
		$this->load->model('users_model', 'user');
		$this->load->model('wiki_model', 'wiki');
		
		// check to see if they are logged in
		$this->auth->is_logged_in();
		
		// an array of the global we want to retrieve
		$settings_array = array(
			'skin_wiki',
			'display_rank',
			'timezone',
			'daylight_savings',
			'sim_name',
			'date_format',
			'system_email',
			'email_subject'
		);
		
		// grab the settings
		$this->options = $this->settings->get_settings($settings_array);
		
		// set the variables
		$this->skin = $this->options['skin_wiki'];
		$this->rank = $this->options['display_rank'];
		$this->timezone = $this->options['timezone'];
		$this->dst = (bool) $this->options['daylight_savings'];
		
		if ($this->auth->is_logged_in())
		{
			$this->skin = (file_exists(APPPATH .'views/'.$this->session->userdata('skin_wiki').'/template_wiki'.EXT))
				? $this->session->userdata('skin_wiki')
				: $this->skin;
			$this->rank = $this->session->userdata('display_rank');
			$this->timezone = $this->session->userdata('timezone');
			$this->dst = (bool) $this->session->userdata('dst');
		}
		
		// set and load the language file needed
		$this->lang->load('app', $this->session->userdata('language'));
		
		// set the template
		$this->template->set_template('wiki');
		$this->template->set_master_template($this->skin . '/template_wiki.php');
		
		// write the common elements to the template
		$this->template->write('nav_main', $this->menu->build('main', 'main'), TRUE);
		$this->template->write('nav_sub', $this->menu->build('sub', 'wiki'), TRUE);
		$this->template->write('title', $this->options['sim_name'] . ' :: ');
		
		if ($this->auth->is_logged_in())
		{
			// create the user panels
			$this->template->write('panel_1', $this->user_panel->panel_1(), TRUE);
			$this->template->write('panel_2', $this->user_panel->panel_2(), TRUE);
			$this->template->write('panel_3', $this->user_panel->panel_3(), TRUE);
			$this->template->write('panel_workflow', $this->user_panel->panel_workflow(), TRUE);
		}
	}

	function index()
	{
		// pull the system page
		$syspage = $this->wiki->get_system_page('index');
		
		// send the system page to the view
		$data['header'] = $syspage->draft_title;
		$data['syspage'] = $syspage->draft_content;
		
		// build the page title
		$pagetitle = ucwords(lang('global_wiki').' - '.lang('labels_main').' '.lang('labels_page'));
		
		// set the input data
		$data['inputs'] = array(
			'search' => array(
				'name' => 'input',
				'id' => 'input',
				'placeholder' => ucwords(lang('actions_search').' '.lang('global_wiki').' '.lang('labels_pages'))),
			'submit' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'search',
				'value' => 'search',
				'content' => ucwords(lang('actions_search')))
		);
		
		$data['component'] = array(
			'title' => ucwords(lang('labels_title')),
			'content' => ucwords(lang('labels_content')),
		);
		
		$data['label'] = array(
			'type' => ucwords(lang('labels_type')),
			'search_in' => ucwords(lang('actions_search').' '.lang('labels_in')),
			'search_for' => ucwords(lang('actions_search').' '.lang('labels_for')),
			'search' => ucfirst(lang('actions_search')),
		);
		
		$data['images'] = array(
			'search' => array(
				'src' => img_location('magnifier.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('actions_search'))),
		);
		
		// figure out where the view files should be coming from
		$view_loc = view_location('wiki_index', $this->skin, 'wiki');
		$js_loc = js_location('wiki_index_js', $this->skin, 'wiki');
		
		// write the data to the template
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);
		$this->template->write('title', $pagetitle);
		
		// render the template
		$this->template->render();
	}
	
	function categories()
	{
		/* check the user's access */
		$data['access'] = ($this->auth->is_logged_in()) ? $this->auth->check_access('wiki/categories', FALSE) : FALSE;
		
		// pull the system page
		$syspage = $this->wiki->get_system_page('categories');
		
		// send the system page to the view
		$data['title'] = $syspage->draft_title;
		$data['syspage'] = $syspage->draft_content;
		
		/* grab the categories */
		$categories = $this->wiki->get_categories();
		
		/* create the uncategorized item first */
		$data['categories'][0] = ucfirst(lang('labels_uncategorized'));
		
		if ($categories->num_rows() > 0)
		{
			foreach ($categories->result() as $c)
			{
				$data['categories'][$c->wikicat_id] = $c->wikicat_name;
			}
		}
		
		/* set the header */
		$data['header'] = ucwords(lang('global_wiki') .' '. lang('labels_categories'));
		
		$data['label'] = array(
			'edit' => '[ '. ucwords(lang('actions_edit') .' '. lang('labels_categories')) .' ]',
			'nocats' => sprintf(
				lang('error_not_found'),
				lang('global_wiki') .' '. lang('labels_categories')
			),
			'text' => sprintf(
				lang('wiki_categories_text'),
				lang('labels_categories'),
				lang('global_wiki'),
				lang('labels_category')
			),
		);
		
		/* figure out where the view files should be coming from */
		$view_loc = view_location('wiki_categories', $this->skin, 'wiki');
		
		/* write the data to the template */
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write('title', $data['header']);
		
		/* render the template */
		$this->template->render();
	}
	
	function category()
	{
		/* set the variables */
		$id = $this->uri->segment(3, 0, TRUE);
		
		/* get the category name */
		$category = $this->wiki->get_category($id, 'wikicat_name');
		$category = ($category === FALSE) ? ucfirst(lang('labels_uncategorized')) : $category;
		
		/* grab the pages */
		$pages = $this->wiki->get_pages($id);
		
		if ($pages->num_rows() > 0)
		{
			foreach ($pages->result() as $p)
			{
				if ($p->page_type == 'standard')
				{
					$data['pages'][$p->page_id]['id'] = $p->page_id;
					$data['pages'][$p->page_id]['title'] = $p->draft_title;
					$data['pages'][$p->page_id]['author'] = $this->char->get_character_name($p->draft_author_character);
					$data['pages'][$p->page_id]['summary'] = $p->draft_summary;
				}
			}
		}
		
		/* set the header */
		$data['header'] = ucfirst(lang('labels_category')) .' - '. $category;
		
		$data['label'] = array(
			'nopages' => sprintf(
				lang('error_not_found'),
				lang('global_wiki') .' '. lang('labels_pages')
			),
		);
		
		/* figure out where the view files should be coming from */
		$view_loc = view_location('wiki_category', $this->skin, 'wiki');
		$js_loc = js_location('wiki_category_js', $this->skin, 'wiki');
		
		/* write the data to the template */
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);
		$this->template->write('title', $data['header']);
		
		/* render the template */
		$this->template->render();
	}
	
	/**
	 * Error page that used by the new page restriction code.
	 *
	 * 1 - This is a restricted wiki page that you are not authorized to view
	 * 2 - This draft is associated with a restricted wiki page that you are not authorized to view
	 *
	 * @since	1.3
	 * @param	integer	the error code
	 */
	function error($code = 0)
	{
		// set the header
		$data['header'] = ucfirst(lang('error_pagetitle'));
		
		// build the error message
		$data['message'] = sprintf(
			lang('error_wiki_'.$code),
			lang('global_game_master')
		);
		
		// figure out where the view files should be coming from
		$view_loc = view_location('wiki_error', $this->skin, 'wiki');
		
		// write the data to the template
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write('title', $data['header']);
		
		// render the template
		$this->template->render();
	}
	
	function managecategories()
	{
		$this->auth->check_access('wiki/categories');
		
		if (isset($_POST['submit']))
		{
			switch ($this->uri->segment(3))
			{
				case 'add':
					$insert_array = array(
						'wikicat_name' => $this->input->post('name', TRUE),
						'wikicat_desc' => $this->input->post('desc', TRUE),
					);
					
					/* insert the record */
					$insert = $this->wiki->create_category($insert_array);
					
					if ($insert > 0)
					{
						$message = sprintf(
							lang('flash_success'),
							ucfirst(lang('global_wiki') .' '. lang('labels_category')),
							lang('actions_created'),
							''
						);

						$flash['status'] = 'success';
						$flash['message'] = text_output($message);
					}
					else
					{
						$message = sprintf(
							lang('flash_failure'),
							ucfirst(lang('global_wiki') .' '. lang('labels_category')),
							lang('actions_created'),
							''
						);

						$flash['status'] = 'error';
						$flash['message'] = text_output($message);
					}
					
					// set the location of the flash view
					$flashloc = view_location('flash', $this->skin, 'wiki');
					
					// write everything to the template
					$this->template->write_view('flash_message', $flashloc, $flash);
				break;
					
				case 'delete':
					$id = $this->input->post('id', TRUE);
				
					/* insert the record */
					$delete = $this->wiki->delete_category($id);
					
					if ($delete > 0)
					{
						$message = sprintf(
							lang('flash_success'),
							ucfirst(lang('global_wiki') .' '. lang('labels_category')),
							lang('actions_deleted'),
							''
						);

						$flash['status'] = 'success';
						$flash['message'] = text_output($message);
					}
					else
					{
						$message = sprintf(
							lang('flash_failure'),
							ucfirst(lang('global_wiki') .' '. lang('labels_category')),
							lang('actions_deleted'),
							''
						);

						$flash['status'] = 'error';
						$flash['message'] = text_output($message);
					}
					
					// set the location of the flash view
					$flashloc = view_location('flash', $this->skin, 'wiki');
					
					// write everything to the template
					$this->template->write_view('flash_message', $flashloc, $flash);
				break;
					
				case 'edit':
					$id = $this->input->post('id', TRUE);
					$id = (is_numeric($id)) ? $id : FALSE;
					
					$update_array = array(
						'wikicat_name' => $this->input->post('name', TRUE),
						'wikicat_desc' => $this->input->post('desc', TRUE)
					);
					
					/* insert the record */
					$update = $this->wiki->update_category($id, $update_array);
					
					if ($update > 0)
					{
						$message = sprintf(
							lang('flash_success'),
							ucfirst(lang('global_wiki') .' '. lang('labels_category')),
							lang('actions_updated'),
							''
						);

						$flash['status'] = 'success';
						$flash['message'] = text_output($message);
					}
					else
					{
						$message = sprintf(
							lang('flash_failure'),
							ucfirst(lang('global_wiki') .' '. lang('labels_category')),
							lang('actions_updated'),
							''
						);

						$flash['status'] = 'error';
						$flash['message'] = text_output($message);
					}
					
					// set the location of the flash view
					$flashloc = view_location('flash', $this->skin, 'wiki');
					
					// write everything to the template
					$this->template->write_view('flash_message', $flashloc, $flash);
				break;
			}
		}
		
		/* grab the categories */
		$categories = $this->wiki->get_categories();
		
		if ($categories->num_rows() > 0)
		{
			foreach ($categories->result() as $c)
			{
				$data['categories'][$c->wikicat_id]['id'] = $c->wikicat_id;
				$data['categories'][$c->wikicat_id]['name'] = $c->wikicat_name;
				$data['categories'][$c->wikicat_id]['desc'] = $c->wikicat_desc;
			}
		}
		
		$data['header'] = ucwords(lang('actions_manage') .' '. lang('global_wiki') .' '. lang('labels_categories'));
		
		$data['images'] = array(
			'add' => array(
				'src' => img_location('category-add.png', $this->skin, 'wiki'),
				'alt' => '',
				'class' => 'image inline_img_left'),
			'delete' => array(
				'src' => img_location('category-delete.png', $this->skin, 'wiki'),
				'alt' => ''),
			'edit' => array(
				'src' => img_location('category-edit.png', $this->skin, 'wiki'),
				'alt' => ''),
		);
		
		$data['inputs'] = array(
			'name' => array(
				'name' => 'name',
				'id' => 'name'),
			'desc' => array(
				'name' => 'desc',
				'id' => 'desc',
				'rows' => 3),
		);
		
		$data['label'] = array(
			'catdesc' => ucfirst(lang('labels_desc')),
			'catname' => ucfirst(lang('labels_name')),
			'name' => ucfirst(lang('labels_name')),
			'desc' => ucfirst(lang('labels_desc')),
			'add' => ucwords(lang('actions_add') .' '. lang('global_wiki') .' '.
				lang('labels_category') .' '. RARROW),
			'delete' => ucfirst(lang('actions_delete')),
			'nocats' => sprintf(
				lang('error_not_found'),
				lang('global_wiki') .' '. lang('labels_categories')
			),
		);
		
		$data['buttons'] = array(
			'update' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'update',
				'content' => ucwords(lang('actions_update'))),
			'add' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'add',
				'content' => ucwords(lang('actions_add')))
		);
		
		/* figure out where the view files should be coming from */
		$view_loc = view_location('wiki_managecats', $this->skin, 'wiki');
		$js_loc = js_location('wiki_managecats_js', $this->skin, 'wiki');
		
		/* write the data to the template */
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);
		$this->template->write('title', $data['header']);
		
		/* render the template */
		$this->template->render();
	}
	
	function managepages()
	{
		$this->auth->check_access('wiki/page');
		
		if (isset($_POST['submit']))
		{
			$level = $this->auth->get_access_level('wiki/page');
			
			if ($level == 3)
			{
				switch ($this->uri->segment(3))
				{
					case 'deletepage':
						$id = $this->input->post('id', TRUE);
					
						/* insert the record */
						$delete = $this->wiki->delete_page($id);
						
						if ($delete > 0)
						{
							$message = sprintf(
								lang('flash_success'),
								ucfirst(lang('global_wiki') .' '. lang('labels_page')),
								lang('actions_deleted'),
								''
							);
	
							$flash['status'] = 'success';
							$flash['message'] = text_output($message);
						}
						else
						{
							$message = sprintf(
								lang('flash_failure'),
								ucfirst(lang('global_wiki') .' '. lang('labels_page')),
								lang('actions_deleted'),
								''
							);
	
							$flash['status'] = 'error';
							$flash['message'] = text_output($message);
						}
						
						// set the location of the flash view
						$flashloc = view_location('flash', $this->skin, 'wiki');
						
						// write everything to the template
						$this->template->write_view('flash_message', $flashloc, $flash);
					break;
					
					case 'deletedraft':
						$id = $this->input->post('id', TRUE);
					
						/* insert the record */
						$delete = $this->wiki->delete_draft($id);
						
						if ($delete > 0)
						{
							$message = sprintf(
								lang('flash_success'),
								ucfirst(lang('global_wiki') .' '. lang('labels_draft')),
								lang('actions_deleted'),
								''
							);
	
							$flash['status'] = 'success';
							$flash['message'] = text_output($message);
						}
						else
						{
							$message = sprintf(
								lang('flash_failure'),
								ucfirst(lang('global_wiki') .' '. lang('labels_draft')),
								lang('actions_deleted'),
								''
							);
	
							$flash['status'] = 'error';
							$flash['message'] = text_output($message);
						}
						
						// set the location of the flash view
						$flashloc = view_location('flash', $this->skin, 'wiki');
						
						// write everything to the template
						$this->template->write_view('flash_message', $flashloc, $flash);
					break;
					
					case 'cleanup':
						// get the timeframe
						$timeframe = $this->input->post('time');
						
						// calculate the date we need to use
						$threshold = (is_numeric($timeframe)) ? now() - ($timeframe * 86400) : FALSE;
						
						// start by getting all the pages
						$pages = $this->wiki->get_pages();
						
						// set the delete start number
						$delete = 0;
						
						if ($pages->num_rows() > 0)
						{
							// create an array for storing the "safe" drafts
							$safe = array();
							
							foreach ($pages->result() as $p)
							{
								// get a list of all the "safe" drafts
								$safe[] = $p->page_draft;
							}
							
							// get all the drafts
							$drafts = $this->wiki->get_drafts(NULL);
							
							if ($drafts->num_rows() > 0)
							{
								foreach ($drafts->result() as $d)
								{
									if ( ! in_array($d->draft_id, $safe))
									{
										if ($timeframe == 'all')
										{
											$delete += $this->wiki->delete_draft($d->draft_id);
										}
										else
										{
											if ($d->draft_created_at < $threshold)
											{
												$delete += $this->wiki->delete_draft($d->draft_id);
											}
										}
									}
								}
							}
						}
						
						if ($delete > 0)
						{
							$message = sprintf(
								lang('flash_success_plural'),
								$delete.' '.lang('global_wiki') .' '. lang('labels_drafts'),
								lang('actions_removed'),
								''
							);
	
							$flash['status'] = 'success';
							$flash['message'] = text_output($message);
						}
						else
						{
							$message = sprintf(
								lang('flash_success_plural'),
								$delete.' '.lang('global_wiki') .' '. lang('labels_drafts'),
								lang('actions_removed'),
								''
							);
	
							$flash['status'] = 'info';
							$flash['message'] = text_output($message);
						}
						
						// set the location of the flash view
						$flashloc = view_location('flash', $this->skin, 'wiki');
						
						// write everything to the template
						$this->template->write_view('flash_message', $flashloc, $flash);
					break;
					
					case 'revert':
						/* get the POST variables */
						$page = $this->input->post('page', TRUE);
						$draft = $this->input->post('draft', TRUE);
						
						/* get the draft we're reverting to */
						$draft = $this->wiki->get_draft($draft);
						
						if ($draft->num_rows() > 0)
						{
							$row = $draft->row();
							
							$insert_array = array(
								'draft_id_old' => $row->draft_id,
								'draft_title' => $row->draft_title,
								'draft_author_user' => $this->session->userdata('userid'),
								'draft_author_character' => $this->session->userdata('main_char'),
								'draft_summary' => $row->draft_summary,
								'draft_content' => $row->draft_content,
								'draft_page' => $page,
								'draft_created_at' => now(),
								'draft_categories' => $row->draft_categories,
								'draft_changed_comments' => lang('wiki_reverted')
							);
							
							$insert = $this->wiki->create_draft($insert_array);
							$draftid = $this->db->insert_id();
							
							/* optimize the table */
							$this->sys->optimize_table('wiki_drafts');
							
							$update_array = array(
								'page_draft' => $draftid,
								'page_updated_by_user' => $this->session->userdata('userid'),
								'page_updated_by_character' => $this->session->userdata('main_char'),
								'page_updated_at' => now()
							);
							
							$update = $this->wiki->update_page($page, $update_array);
							
							if ($insert > 0 && $update > 0)
							{
								$message = sprintf(
									lang('flash_success'),
									ucfirst(lang('global_wiki') .' '. lang('labels_page')),
									lang('actions_reverted'),
									''
								);
		
								$flash['status'] = 'success';
								$flash['message'] = text_output($message);
							}
							else
							{
								$message = sprintf(
									lang('flash_failure'),
									ucfirst(lang('global_wiki') .' '. lang('labels_page')),
									lang('actions_reverted'),
									''
								);
		
								$flash['status'] = 'error';
								$flash['message'] = text_output($message);
							}
						}
						else
						{
							$message = sprintf(
								lang('error_not_found'),
								lang('labels_draft')
							);
		
							$flash['status'] = 'error';
							$flash['message'] = text_output($message);
						}
						
						// set the location of the flash view
						$flashloc = view_location('flash', $this->skin, 'wiki');
						
						// write everything to the template
						$this->template->write_view('flash_message', $flashloc, $flash);
					break;
				}
			}
		}
		
		// grab the pages
		$pages = $this->wiki->get_pages(NULL, 'wiki_drafts.draft_created_at', 'desc');
		
		// get all the page restrictions
		$restr = $this->wiki->get_page_restrictions();
		
		// set up the restrictions array
		$restrictions = array();
		
		if ($restr->num_rows() > 0)
		{
			foreach ($restr->result() as $r)
			{
				$restrictions[] = $r->restr_page;
			}
		}
		
		/* set the date format */
		$datestring = $this->options['date_format'];
		
		if ($pages->num_rows() > 0)
		{
			foreach ($pages->result() as $p)
			{
				/* set the date */
				$created = gmt_to_local($p->page_created_at, $this->timezone, $this->dst);
				$updated = gmt_to_local($p->page_updated_at, $this->timezone, $this->dst);
			
				$data['pages'][$p->page_id]['id'] = $p->page_id;
				$data['pages'][$p->page_id]['title'] = $p->draft_title;
				$data['pages'][$p->page_id]['type'] = $p->page_type;
				$data['pages'][$p->page_id]['created'] = ($p->page_created_by_user == 0) 
					? ucfirst(lang('labels_system')) 
					: $this->char->get_character_name($p->page_created_by_character, TRUE);
				$data['pages'][$p->page_id]['updated'] = ( ! empty($p->page_updated_by_character)) 
					? $this->char->get_character_name($p->page_updated_by_character, TRUE) 
					: FALSE;
				$data['pages'][$p->page_id]['created_date'] = mdate($datestring, $created);
				$data['pages'][$p->page_id]['updated_date'] = mdate($datestring, $updated);
				$data['pages'][$p->page_id]['restrictions'] = (in_array($p->page_id, $restrictions)) ? 'restricted' : FALSE;
			}
		}
		
		$data['header'] = ucwords(lang('actions_manage').' '.lang('global_wiki').' '.lang('labels_pages'));
		
		$data['images'] = array(
			'add' => array(
				'src' => img_location('icon-add.png', $this->skin, 'wiki'),
				'alt' => '',
				'class' => 'image subnav-icon'),
			'delete' => array(
				'src' => img_location('page-delete.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('actions_delete'))),
			'edit' => array(
				'src' => img_location('page-edit.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('actions_edit'))),
			'clean' => array(
				'src' => img_location('broom.png', $this->skin, 'wiki'),
				'alt' => '',
				'class' => 'image subnav-icon'),
			'history' => array(
				'src' => img_location('clock-history.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('labels_history'))),
			'lock' => array(
				'src' => img_location('lock.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('labels_restrictions'))),
			'info' => array(
				'src' => img_location('information.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('labels_information'))),
			'view' => array(
				'src' => img_location('magnifier.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('actions_view'))),
			'eye' => array(
				'src' => img_location('eye.png', $this->skin, 'wiki'),
				'alt' => '',
				'class' => 'image subnav-icon-right'),
			'loading' => array(
				'src' => img_location('loading.gif', $this->skin, 'wiki'),
				'alt' => lang('actions_loading')),
		);
		
		$data['label'] = array(
			'add' => ucwords(lang('status_new').' '.lang('labels_page')),
			'clean' => ucwords(lang('actions_cleanup').' '.lang('labels_drafts')),
			'created' => ucfirst(lang('actions_created') .' '. lang('labels_by')),
			'loading' => ucfirst(lang('actions_loading')).'...',
			'name' => ucwords(lang('labels_page') .' '. lang('labels_name')),
			'nopages' => sprintf(lang('error_not_found'), lang('global_wiki').' '.lang('labels_pages')),
			'on' => lang('labels_on'),
			'pages' => ucfirst(lang('labels_pages')),
			'restrict'=> ucfirst(lang('labels_restricted')),
			'show' => ucwords(lang('actions_show').' '.lang('labels_filters')),
			'show_all' => ucfirst(lang('labels_all')),
			'show_std' => ucfirst(lang('labels_standard')),
			'system' => ucfirst(lang('labels_system')),
			'updated' => ucfirst(lang('order_last').' '.lang('actions_updated').' '.lang('labels_by')),
		);
		
		/* figure out where the view files should be coming from */
		$view_loc = view_location('wiki_managepages', $this->skin, 'wiki');
		$js_loc = js_location('wiki_managepages_js', $this->skin, 'wiki');
		
		/* write the data to the template */
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);
		$this->template->write('title', $data['header']);
		
		/* render the template */
		$this->template->render();
	}
	
	function page()
	{
		$this->auth->check_access('wiki/page');
		
		/* set the variables */
		$id = $this->uri->segment(3, 0, TRUE);
		
		if (isset($_POST['submit']))
		{
			switch ($this->uri->segment(4))
			{
				case 'create':
					/* create the array of page data */
					$page_array = array(
						'page_created_at' => now(),
						'page_created_by_user' => $this->session->userdata('userid'),
						'page_created_by_character' => $this->session->userdata('main_char'),
						'page_comments' => $this->input->post('comments', TRUE)
					);
					
					/* put the page information into the database */
					$insert = $this->wiki->create_page($page_array);
					$pageid = $this->db->insert_id();
					
					/* optimize the table */
					$this->sys->optimize_table('wiki_pages');
					
					foreach ($_POST as $key => $value)
					{
						if (substr($key, 0, 4) == 'cat_')
						{
							$category_array[$key] = $value;
						}
					}
					
					$category_string = (isset($category_array) && is_array($category_array)) ? implode(',', $category_array) : '';
					
					/* create the array of draft data */
					$draft_array = array(
						'draft_author_user' => $this->session->userdata('userid'),
						'draft_author_character' => $this->session->userdata('main_char'),
						'draft_content' => $this->input->post('content', TRUE),
						'draft_title' => $this->input->post('title', TRUE),
						'draft_created_at' => now(),
						'draft_page' => $pageid,
						'draft_categories' => $category_string,
						'draft_summary' => $this->input->post('summary', TRUE),
					);
					
					/* put the draft information into the database */
					$insert += $this->wiki->create_draft($draft_array);
					$draftid = $this->db->insert_id();
					
					/* optimize the table */
					$this->sys->optimize_table('wiki_drafts');
					
					/* update the page with the draft ID */
					$this->wiki->update_page($pageid, array('page_draft' => $draftid));
					
					if ($insert > 1)
					{
						$message = sprintf(
							lang('flash_success'),
							ucfirst(lang('global_wiki') .' '. lang('labels_page')),
							lang('actions_created'),
							''
						);

						$flash['status'] = 'success';
						$flash['message'] = text_output($message);
					}
					else
					{
						$message = sprintf(
							lang('flash_failure'),
							ucfirst(lang('global_wiki') .' '. lang('labels_page')),
							lang('actions_created'),
							''
						);

						$flash['status'] = 'error';
						$flash['message'] = text_output($message);
					}
				break;
					
				case 'edit':
					foreach ($_POST as $key => $value)
					{
						if (substr($key, 0, 4) == 'cat_')
						{
							$category_array[$key] = $value;
						}
					}
					
					$category_string = (isset($category_array) && is_array($category_array)) ? implode(',', $category_array) : FALSE;
					
					/* create the array of draft data */
					$draft_array = array(
						'draft_author_user' => $this->session->userdata('userid'),
						'draft_author_character' => $this->session->userdata('main_char'),
						'draft_content' => $this->input->post('content', TRUE),
						'draft_title' => $this->input->post('title', TRUE),
						'draft_created_at' => now(),
						'draft_page' => $id,
						'draft_categories' => $category_string,
						'draft_summary' => $this->input->post('summary', TRUE),
						'draft_changed_comments' => $this->input->post('changes', TRUE),
					);
					
					/* put the draft information into the database */
					$insert = $this->wiki->create_draft($draft_array);
					$draftid = $this->db->insert_id();
					
					/* optimize the table */
					$this->sys->optimize_table('wiki_drafts');
					
					// get the comments item
					$comments = $this->input->post('comments', TRUE);
					
					/* create the array of page data */
					$page_array = array(
						'page_updated_at' => now(),
						'page_updated_by_user' => $this->session->userdata('userid'),
						'page_updated_by_character' => $this->session->userdata('main_char'),
						'page_comments' => ($comments === FALSE) ? 'closed' : $comments,
						'page_draft' => $draftid
					);
					
					/* put the page information into the database */
					$update = $this->wiki->update_page($id, $page_array);
					
					if ($insert > 0)
					{
						$message = sprintf(
							lang('flash_success'),
							ucfirst(lang('global_wiki') .' '. lang('labels_page')),
							lang('actions_updated'),
							''
						);

						$flash['status'] = 'success';
						$flash['message'] = text_output($message);
					}
					else
					{
						$message = sprintf(
							lang('flash_failure'),
							ucfirst(lang('global_wiki') .' '. lang('labels_page')),
							lang('actions_updated'),
							''
						);

						$flash['status'] = 'error';
						$flash['message'] = text_output($message);
					}
				break;
			}
			
			// set the location of the flash view
			$flashloc = view_location('flash', $this->skin, 'wiki');
			
			// write everything to the template
			$this->template->write_view('flash_message', $flashloc, $flash);
		}
		
		if ($id == 0)
		{
			/* set the field information */
			$data['inputs'] = array(
				'title' => array(
					'name' => 'title',
					'id' => 'title'),
				'content' => array(
					'name' => 'content',
					'id' => 'content',
					'class' => 'markitup',
					'rows' => 30),
				'comments_open' => array(
					'name' => 'comments',
					'id' => 'comments_open',
					'value' => 'open',
					'checked' => TRUE),
				'comments_closed' => array(
					'name' => 'comments',
					'id' => 'comments_closed',
					'value' => 'closed'),
				'summary' => array(
					'name' => 'summary',
					'id' => 'summary',
					'class' => 'full-width',
					'rows' => 2),
			);
			
			$categories = $this->wiki->get_categories();
			
			if ($categories->num_rows() > 0)
			{
				foreach ($categories->result() as $c)
				{
					$data['cats'][] = array(
						'id' => $c->wikicat_id,
						'name' => $c->wikicat_name,
						'desc' => $c->wikicat_desc,
					);
				}
			}
			
			/* set the header */
			$data['header'] = ucwords(lang('actions_create') .' '. lang('global_wiki') .' '. lang('labels_page'));
			
			/* figure out where the view files should be coming from */
			$view_loc = view_location('wiki_page_create', $this->skin, 'wiki');
		}
		else
		{
			/* grab the page information and latest draft */
			$page = $this->wiki->get_page($id);
			
			if ($page->num_rows() > 0)
			{
				foreach ($page->result() as $p)
				{
					/* set the field information */
					$data['inputs'] = array(
						'title' => array(
							'name' => 'title',
							'id' => 'title',
							'value' => (!empty($p->draft_title)) ? $p->draft_title : ''),
						'content' => array(
							'name' => 'content',
							'id' => 'content',
							'class' => 'markitup',
							'rows' => 30,
							'value' => (!empty($p->draft_content)) ? $p->draft_content : ''),
						'comments_open' => array(
							'name' => 'comments',
							'id' => 'comments_open',
							'value' => 'open',
							'checked' => ($p->page_comments == 'open') ? TRUE : FALSE),
						'comments_closed' => array(
							'name' => 'comments',
							'id' => 'comments_closed',
							'value' => 'closed',
							'checked' => ($p->page_comments == 'closed') ? TRUE : FALSE),
						'changes' => array(
							'name' => 'changes',
							'id' => 'changes',
							'class' => 'full-width',
							'rows' => 2),
						'summary' => array(
							'name' => 'summary',
							'id' => 'summary',
							'class' => 'full-width',
							'rows' => 2,
							'value' => (!empty($p->draft_summary)) ? $p->draft_summary : ''),
					);
				}
			}
			
			/* set the id */
			$data['id'] = $id;
			
			// what type of page is it?
			$data['type'] = $p->page_type;
			
			/* build the category list */
			$cats = explode(',', $p->draft_categories);
			
			$categories = $this->wiki->get_categories();
			
			if ($categories->num_rows() > 0)
			{
				foreach ($categories->result() as $c)
				{
					$data['cats'][] = array(
						'id' => $c->wikicat_id,
						'name' => $c->wikicat_name,
						'desc' => $c->wikicat_desc,
						'checked' => (in_array($c->wikicat_id, $cats)) ? TRUE : FALSE,
					);
				}
			}
			
			/* set the header */
			$data['header'] = ucwords(lang('actions_edit') .' '. lang('global_wiki') .' '. lang('labels_page'));
			
			/* figure out where the view files should be coming from */
			$view_loc = view_location('wiki_page_edit', $this->skin, 'wiki');
		}
		
		$data['buttons'] = array(
			'update' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'update',
				'content' => ucwords(lang('actions_update'))),
			'add' => array(
				'type' => 'submit',
				'class' => 'button-main',
				'name' => 'submit',
				'value' => 'add',
				'content' => ucwords(lang('actions_create')))
		);
		
		$data['label'] = array(
			'back' => LARROW .' '. ucwords(lang('actions_manage') .' '. lang('global_wiki') .' '. lang('labels_pages')),
			'categories' => ucfirst(lang('labels_categories')),
			'changes' => ucfirst(lang('actions_changes')),
			'closed' => ucfirst(lang('status_closed')),
			'comments' => ucfirst(lang('labels_comments')),
			'open' => ucfirst(lang('status_open')),
			'summary' => ucfirst(lang('labels_summary')),
			'title' => ucfirst(lang('labels_title')),
		);
		
		/* figure out where the view files should be coming from */
		$js_loc = js_location('wiki_page_js', $this->skin, 'wiki');
		
		/* write the data to the template */
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);
		$this->template->write('title', $data['header']);
		
		/* render the template */
		$this->template->render();
	}
	
	function recent()
	{
		/* set the uri segments */
		$type = $this->uri->segment(3);
		
		switch ($type)
		{
			case 'updates':
			default:
				/* grab the recently updated items */
				$updated = $this->wiki->get_recently_updated(100);
				
				if ($updated->num_rows() > 0)
				{
					foreach ($updated->result() as $u)
					{
						$data['recent']['updates'][] = array(
							'id' => $u->page_id,
							'title' => $u->draft_title,
							'author' => $this->char->get_character_name($u->page_updated_by_character),
							'timespan' => timespan_short($u->page_updated_at, now()),
							'comments' => $u->draft_changed_comments,
							'type' => $u->page_type,
						);
					}
				}
				
				$data['header'] = ucwords(lang('global_wiki') .' - '. lang('status_recently') .' '. lang('actions_updated'));
			break;
				
			case 'created':
				/* grab the recently updated items */
				$created = $this->wiki->get_recently_created(100);
				
				if ($created->num_rows() > 0)
				{
					foreach ($created->result() as $c)
					{
						$data['recent']['created'][] = array(
							'id' => $c->page_id,
							'title' => $c->draft_title,
							'author' => $this->char->get_character_name($c->page_created_by_character),
							'timespan' => timespan_short($c->page_created_at, now()),
							'summary' => $c->draft_summary,
							'type' => $c->page_type,
						);
					}
				}
				
				$data['header'] = ucwords(lang('global_wiki') .' - '. lang('status_recently') .' '. lang('actions_created'));
			break;
		}
		
		$data['label'] = array(
			'ago' => lang('time_ago'),
			'by' => lang('labels_by'),
			'page' => ucfirst(lang('labels_page')),
			'created' => ucwords(lang('actions_show') .' '. lang('status_recently') .' '. lang('actions_created')),
			'updates' => ucwords(lang('actions_show') .' '. lang('status_recently') .' '. lang('actions_updated')),
			'summary' => ucfirst(lang('labels_summary')),
			'system' => ucfirst(lang('labels_system')),
			'update_summary' => ucwords(lang('actions_update') .' '. lang('labels_summary')),
		);
		
		$data['images'] = array(
			'feed' => array(
				'src' => img_location('feed.png', $this->skin, 'wiki'),
				'alt' => ''),
		);
		
		/* figure out where the view files should be coming from */
		$view_loc = view_location('wiki_recent', $this->skin, 'wiki');
		$js_loc = js_location('wiki_recent_js', $this->skin, 'wiki');
		
		/* write the data to the template */
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc);
		$this->template->write('title', $data['header']);
		
		/* render the template */
		$this->template->render();
	}
	
	function view()
	{
		/* check to see if they have access */
		$access = ($this->auth->is_logged_in()) ? $this->auth->check_access('wiki/page', FALSE) : FALSE;
		
		/* get the access level */
		$level = ($this->auth->is_logged_in()) ? $this->auth->get_access_level('wiki/page') : FALSE;
		
		/* set the variables */
		$type = $this->uri->segment(3, 'page');
		$id = $this->uri->segment(4, 0, TRUE);
		$action = $this->uri->segment(5, FALSE);
		
		/* assign the config array to a variable */
		$c = $this->config->item('thresher');
		
		/* load the library and pass the config items in */
		$this->load->library('thresher', $c);
		
		if (isset($_POST['submit']) && $this->auth->is_logged_in())
		{
			if ($action == 'comment')
			{
				$comment_text = $this->input->post('comment_text');
				
				if (!empty($comment_text))
				{
					$status = $this->user->checking_moderation('wiki_comment', $this->session->userdata('userid'));
					
					/* build the insert array */
					$insert = array(
						'wcomment_content' => $comment_text,
						'wcomment_page' => $id,
						'wcomment_date' => now(),
						'wcomment_author_character' => $this->session->userdata('main_char'),
						'wcomment_author_user' => $this->session->userdata('userid'),
						'wcomment_status' => $status
					);
					
					/* insert the data */
					$add = $this->wiki->create_comment($insert);
					
					if ($add > 0)
					{
						$message = sprintf(
							lang('flash_success'),
							ucfirst(lang('labels_comment')),
							lang('actions_added'),
							''
						);
						
						$flash['status'] = 'success';
						$flash['message'] = text_output($message);
						
						/* set the array of data for the email */
						$email_data = array(
							'author' => $this->session->userdata('main_char'),
							'page' => $id,
							'comment' => $comment_text);
							
						$emailaction = ($status == 'pending') ? 'comment_pending' : 'comment';
						
						/* send the email */
						$email = ($this->options['system_email'] == 'on') ? $this->_email($emailaction, $email_data) : FALSE;
					}
					else
					{
						$message = sprintf(
							lang('flash_failure'),
							ucfirst(lang('labels_comment')),
							lang('actions_added'),
							''
						);
						
						$flash['status'] = 'error';
						$flash['message'] = text_output($message);
					}
				}
				else
				{
					$flash['status'] = 'error';
					$flash['message'] = lang_output('flash_add_comment_empty_body');
				}
				
				// set the location of the flash view
				$flashloc = view_location('flash', $this->skin, 'wiki');
				
				// write everything to the template
				$this->template->write_view('flash_message', $flashloc, $flash);
			}
		}
		
		switch ($action)
		{
			case 'comment':
				$js_data['tab'] = 2;
			break;
				
			default:
				$js_data['tab'] = 0;
			break;
		}
		
		/* set the date format */
		$datestring = $this->options['date_format'];
		
		if ($type == 'draft')
		{
			/* grab the information about the page */
			$draft = $this->wiki->get_draft($id);
			
			// get the draft object
			$d = ($draft->num_rows() > 0) ? $draft->row() : FALSE;
			
			if ($d !== FALSE)
			{
				// check page restrictions
				$restrict = $this->wiki->get_page_restrictions($d->draft_page);
				
				if ($restrict->num_rows() > 0)
				{
					// get the row that contains the data
					$r = $restrict->row();
					
					// make an array of the restrictions
					$allowed = explode(',', $r->restrictions);
					
					if ($level < 3)
					{
						if ( ! $this->auth->is_logged_in() || ! in_array($this->session->userdata('role'), $allowed))
						{
							redirect('wiki/error/2');
						}
					}
				}
				
				/* set the date */
				$created = gmt_to_local($d->draft_created_at, $this->timezone, $this->dst);
				
				$data['header'] = ucfirst(lang('labels_draft')) .' - '. $d->draft_title;
				
				$count = substr_count($d->draft_categories, ',');
				
				if ($count === 0 && empty($d->draft_categories))
				{
					$string = sprintf(
						lang('error_not_found'),
						lang('labels_categories')
					);
				}
				else
				{
					$categories = explode(',', $d->draft_categories);
					
					foreach ($categories as $c)
					{
						$name = $this->wiki->get_category($c, 'wikicat_name');
						
						$cat[] = anchor('wiki/category/'. $c, $name);
					}
					
					$string = implode(' | ', $cat);
				}
				
				$data['draft'] = array(
					'content' => $this->thresher->parse($d->draft_content),
					'created' => $this->char->get_character_name($d->draft_author_character, TRUE),
					'created_date' => mdate($datestring, $created),
					'page' => $d->draft_page,
					'categories' => $string,
				);
			}
			
			$view_loc = view_location('wiki_view_draft', $this->skin, 'wiki');
		}
		else
		{
			// check page restrictions
			$restrict = $this->wiki->get_page_restrictions($id);
			
			if ($restrict->num_rows() > 0)
			{
				// get the row that contains the data
				$r = $restrict->row();
				
				// make an array of the restrictions
				$allowed = explode(',', $r->restrictions);
				
				if ($level < 3)
				{
					if ( ! $this->auth->is_logged_in() || ! in_array($this->session->userdata('role'), $allowed))
					{
						redirect('wiki/error/1');
					}
				}
			}
			
			$data['id'] = $id;
			
			/*
			|---------------------------------------------------------------
			| PAGE
			|---------------------------------------------------------------
			*/
			
			/* grab the information about the page */
			$page = $this->wiki->get_page($id);
			
			if ($page->num_rows() > 0)
			{
				// get the row
				$p = $page->row();
				
				/* set the date */
				$created = gmt_to_local($p->page_created_at, $this->timezone, $this->dst);
				$updated = gmt_to_local($p->page_updated_at, $this->timezone, $this->dst);
				
				$data['header'] = $p->draft_title;

				$count = substr_count($p->draft_categories, ',');
				
				if ($count === 0 && empty($p->draft_categories))
				{
					$string = sprintf(
						lang('error_not_found'),
						lang('labels_categories')
					);
				}
				else
				{
					$categories = explode(',', $p->draft_categories);
					
					foreach ($categories as $c)
					{
						$name = $this->wiki->get_category($c, 'wikicat_name');
						
						$cat[] = anchor('wiki/category/'. $c, $name);
					}
					
					$string = implode(' | ', $cat);
				}
				
				$data['page'] = array(
					'content' => $this->thresher->parse($p->draft_content),
					'created' => $this->char->get_character_name($p->page_created_by_character, TRUE),
					'updated' => (!empty($p->page_updated_by_character)) ? $this->char->get_character_name($p->page_updated_by_character, TRUE) : FALSE,
					'created_date' => mdate($datestring, $created),
					'updated_date' => mdate($datestring, $updated),
					'categories' => $string,
					'summary' => $p->draft_summary,
				);
				
				// pass the type of page to the js view
				$js_data['type'] = $p->page_type;
			}
			
			if ($this->auth->is_logged_in())
			{
				if ($level == 3 || $level == 2 || ($level == 1 && ($p->page_created_by_user == $this->session->userdata('userid'))))
				{
					$data['edit'] = TRUE;
				}
				else
				{
					$data['edit'] = FALSE;
				}
			}
			else
			{
				$data['edit'] = FALSE;
			}
			
			/*
			|---------------------------------------------------------------
			| HISTORY
			|---------------------------------------------------------------
			*/
			
			/* grab the information about the page */
			$drafts = $this->wiki->get_drafts($id);
			
			if ($drafts->num_rows() > 0)
			{
				foreach ($drafts->result() as $d)
				{
					$created = gmt_to_local($d->draft_created_at, $this->timezone, $this->dst);
					
					$page = $this->wiki->get_page($d->draft_page);
					$row = ($page->num_rows() > 0) ? $page->row() : FALSE;
					
					$data['history'][$d->draft_id] = array(
						'draft' => $d->draft_id,
						'title' => $d->draft_title,
						'content' => $this->thresher->parse($d->draft_content),
						'created' => $this->char->get_character_name($d->draft_author_character),
						'created_date' => mdate($datestring, $created),
						'old_id' => (!empty($d->draft_id_old)) ? $d->draft_id_old : FALSE,
						'page' => $d->draft_page,
						'changes' => $d->draft_changed_comments,
						'page_draft' => ($row !== FALSE) ? $row->page_draft : FALSE,
					);
				}
			}
			
			/*
			|---------------------------------------------------------------
			| COMMENTS
			|---------------------------------------------------------------
			*/
			
			/* get all the comments */
			$comments = $this->wiki->get_comments($id);
			
			if ($comments->num_rows() > 0)
			{
				foreach ($comments->result() as $cm)
				{
					$date = gmt_to_local($cm->wcomment_date, $this->timezone, $this->dst);
					
					$data['comments'][$cm->wcomment_id]['author'] = $this->char->get_character_name($cm->wcomment_author_character, TRUE);
					$data['comments'][$cm->wcomment_id]['content'] = $cm->wcomment_content;
					$data['comments'][$cm->wcomment_id]['date'] = mdate($datestring, $date);
				}
			}
			
			$data['comment_count'] = $comments->num_rows();
			
			$view_loc = view_location('wiki_view_page', $this->skin, 'wiki');
		}
		
		$data['images'] = array(
			'view' => array(
				'src' => img_location('magnifier.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('actions_view'))),
			'edit' => array(
				'src' => img_location('icon-edit.png', $this->skin, 'wiki'),
				'alt' => '',
				'class' => 'image subnav-icon'),
			'comment' => array(
				'src' => img_location('comment-add.png', $this->skin, 'wiki'),
				'alt' => '',
				'class' => 'image subnav-icon'),
			'page' => array(
				'src' => img_location('page.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('labels_page'))),
			'history' => array(
				'src' => img_location('clock-history.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('labels_history'))),
			'comments' => array(
				'src' => img_location('comment-add.png', $this->skin, 'wiki'),
				'alt' => '',
				'title' => ucfirst(lang('labels_comments'))),
		);
		
		$data['label'] = array(
			'addcomment' => ucfirst(lang('actions_add')) .' '. lang('labels_a') .' '. ucfirst(lang('labels_comment')),
			'back_page' => LARROW .' '. ucfirst(lang('actions_back')) .' '. lang('labels_to') .' '.
				ucwords(lang('global_wiki') .' '. lang('labels_page')),
			'by' => lang('labels_by'),
			'categories' => ucfirst(lang('labels_categories')) .':',
			'comments' => ucfirst(lang('labels_comments')),
			'created' => lang('actions_created'),
			'draft' => ucfirst(lang('labels_draft')),
			'edit' => ucfirst(lang('actions_edit')),
			'history' => ucfirst(lang('labels_history')),
			'nocomments' => sprintf(
				lang('error_not_found'),
				lang('labels_comments')
			),
			'nohistory' => sprintf(
				lang('error_not_found'),
				lang('labels_page') .' '. lang('labels_history')
			),
			'on' => lang('labels_on'),
			'page' => ucfirst(lang('labels_page')),
			'reverted' => lang('actions_reverted'),
			'to' => lang('labels_to'),
		);
		
		/* figure out where the view files should be coming from */
		$js_loc = js_location('wiki_view_js', $this->skin, 'wiki');
		
		/* write the data to the template */
		$this->template->write_view('content', $view_loc, $data);
		$this->template->write_view('javascript', $js_loc, $js_data);
		$this->template->write('title', ucfirst(lang('global_wiki')) .' - '. $data['header']);
		
		/* render the template */
		$this->template->render();
	}
	
	function _email($type = '', $data = '')
	{
		/* load the libraries */
		$this->load->library('email');
		$this->load->library('parser');
		
		/* define the variables */
		$email = FALSE;
		
		/* run the methods */
		$page = $this->wiki->get_page($data['page']);
		$row = $page->row();
		$name = $this->char->get_character_name($data['author']);
		$from = $this->user->get_email_address('character', $data['author']);
		
		switch ($type)
		{
			case 'comment':
				/* get all the contributors of a wiki page */
				$cont = $this->wiki->get_all_contributors($data['page']);
				
				foreach ($cont as $c)
				{
					$pref = $this->user->get_pref('email_new_wiki_comments', $c);
					
					if ($pref == 'y')
					{
						$to_array[] = $this->user->get_email_address('user', $c);
					}
				}
				
				/* set the to string */
				$to = implode(',', $to_array);
				
				/* set the content */	
				$content = sprintf(
					lang('email_content_wiki_comment_added'),
					"<strong>". $row->draft_title ."</strong>",
					$data['comment']
				);
				
				/* create the array passing the data to the email */
				$email_data = array(
					'email_subject' => lang('email_subject_wiki_comment_added'),
					'email_from' => ucfirst(lang('time_from')) .': '. $name .' - '. $from,
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);
				
				/* where should the email be coming from */
				$em_loc = email_location('wiki_comment', $this->email->mailtype);
				
				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);
				
				/* set the parameters for sending the email */
				$this->email->from($from, $name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. $email_data['email_subject']);
				$this->email->message($message);
			break;
				
			case 'comment_pending':
				/* run the methods */
				$to = implode(',', $this->user->get_emails_with_access('manage/comments'));
				
				/* set the content */	
				$content = sprintf(
					lang('email_content_comment_pending'),
					lang('global_wiki'),
					"<strong>". $row->draft_title ."</strong>",
					$data['comment'],
					site_url('login/index')
				);
				
				/* create the array passing the data to the email */
				$email_data = array(
					'email_subject' => lang('email_subject_comment_pending'),
					'email_from' => ucfirst(lang('time_from')) .': '. $name .' - '. $from,
					'email_content' => ($this->email->mailtype == 'html') ? nl2br($content) : $content
				);
				
				/* where should the email be coming from */
				$em_loc = email_location('comment_pending', $this->email->mailtype);
				
				/* parse the message */
				$message = $this->parser->parse($em_loc, $email_data, TRUE);
				
				/* set the parameters for sending the email */
				$this->email->from($from, $name);
				$this->email->to($to);
				$this->email->subject($this->options['email_subject'] .' '. $email_data['email_subject']);
				$this->email->message($message);
			break;
		}
		
		/* send the email */
		$email = $this->email->send();
		
		/* return the email variable */
		return $email;
	}
}