<?php
/**
 * Helper for output tree of the resources.
 *
 * @author Kuksanau Ihnat
 * @copyright 2013 SpurIT <contact@spur-i-t.com>, All rights reserved.
 * @link http://spur-i-t.com
 * @package Admin
 * @version 1.0.0
 */
class Admin_View_Helper_ResourcesTree extends Zend_View_Helper_Abstract
{
	protected $_list = '';

	/**
	 * @param $arr
	 * @param int $parent_id
	 * @return string
	 */
	public function ResourcesTree( $arr, $parent_id = 0 ) {

		//fill list by resources
		$this->_outputTreeRecursive($arr, $parent_id);
		return $this->_list;
	}

	/**
	 * returns list of resources as a html ul
	 * @param $arr
	 * @param int $parent_id
	 */
	protected function _outputTreeRecursive($arr, $parent_id = 0){
		//condition of the stop of recursion
		if(empty($arr[$parent_id])) {
			return;
		}
		//parent tag ul should has id
		$this->_list .= '<ul';
		if($parent_id == 0){
			$this->_list .= ' id="resources_tree">';
		}else{
			$this->_list .= '>';
		}

		for($i = 0; $i < count($arr[$parent_id]);$i++) {
			$checked = $arr[$parent_id][$i]['allowed'] ? 'checked="checked"' : '';
			$this->_list .=  '<li><input type="checkbox" name="'.$arr[$parent_id][$i]['id'].'" '. $checked.'><label>'
				.$this->view->translate( $arr[$parent_id][$i]['title'] ) .'</label>';
			//recursive call
			$this->_outputTreeRecursive($arr,$arr[$parent_id][$i]['id']);
			$this->_list .=  '</li>';
		}
		$this->_list .= '</ul>';
	}

}
