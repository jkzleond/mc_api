<?php
class CollectionController extends ControllerBase
{
	/**
	 * 获取收藏数据列表
	 */
	public function getListAction()
	{
		$page_num = $this->request->get('page');
        $page_size = $this->request->get('rows');

        $criteria = array();

        $user = User::getCurrentUser();
        $criteria['user_id'] = $user['user_id'];

        $collection_list = Collection::getList($criteria, $page_num, $page_size);
        $collection_total = Collection::getCount($criteria);

        $this->view->setVars(array(
            'total' => $collection_total,
            'count' => count($collection_list),
            'rows' => $collection_list
        ));
	}
}