<?php
/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-6-14
 * Time: 下午5:40
 */

class DiscoveryController extends ControllerBase
{
    /**
     *  获取发现列表
     */
    public function getListAction()
    {
        $criteria = $this->request->get('criteria');
        $page_num = $this->request->get('page');
        $page_size = $this->request->get('rows');

        $discoverise = LocalFavour::getLocalFavourList($criteria, $page_num, $page_size);
        $discoverise_total = LocalFavour::getLocalFavourCount($criteria);

        $this->view->setVars(array(
            'total' => $discoverise_total,
            'count' => count($discoverise),
            'rows' => $discoverise
        ));
    }

    /**
     * 获取发现详细信息
     * @param $id
     */
    public function getDetailAction($id)
    {
        $discovery = LocalFavour::getLocalFavourDetailById($id);
        LocalFavour::addReadCount($id);
        $this->view->setVars(array(
            'row' => $discovery
        ));
    }

    /**
     * 添加发现回复
     * @param $local_favour_id
     */
    public function addCommentAction($local_favour_id)
    {
        $json_data = $this->request->getJsonRawBody(true);
        $new_comment_id = LocalFavour::addComment($local_favour_id, $json_data);

        if($new_comment_id === false)
        {
            $success = false;
            $err_msg = '回复添加失败!';
        }
        else
        {
            $success = true;
            $err_msg = '回复添加成功!';
        }

        $this->view->setVars(array(
            'success' => $success,
            'err_msg' => $err_msg,
            'row' => array(
                'id' => $new_comment_id
            )
        ));
    }

    /**
     * 获取发现回复
     * @param $local_favour_id
     */
    public function getCommentsAction($local_favour_id)
    {
        $page_num = $this->request->get('page');
        $page_size = $this->request->get('rows');

        $comments = LocalFavour::getDiscoveryCommentList($local_favour_id, $page_num, $page_size);
        $total = LocalFavour::getDiscoveryCommentCount($local_favour_id);
        $this->view->setVars(array(
            'total' => $total,
            'count' => count($comments),
            'rows' => $comments,
        ));
    }
}