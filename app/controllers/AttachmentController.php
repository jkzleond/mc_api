<?php

/**
 * Created by PhpStorm.
 * User: jkzleond
 * Date: 15-7-21
 * Time: 上午6:21
 */
class AttachmentController extends ControllerBase
{

    private static $upload_dir = '/../../public/uploads';
    private static $file_max_size = 5242880; //最大文件大小,单位字节(这里是5M)

    public function uploadFileAction($data_type)
    {
        $files = $this->request->getUploadedFiles();

        $file = $files[0];

        $dir = self::_dir();

        if(!is_dir($dir))
        {
            mkdir($dir, 0777, true);
        }
        $path = $dir.basename($file->getTempName()).time().'.'.$file->getExtension();
        $file_size = $file->getSize();

        if($file_size > self::$file_max_size)
        {
            $this->view->setVars(array(
                'success' => false,
                'msg' => '文件大小超出'
            ));
            return;
        }

        $success = $file->moveTo($path);
        $file_name = $file->getBasename();
        $mime_type = $file->getRealType();
        
        if($success)
        {
            if($data_type == 'url' || !$data_type)
            {
                $url = $this->url->get( '/uploads/'.basename($path) );
                $new_attach_id = Attachment::addAttachment($url, $file_name, $mime_type, 'url');

                $this->view->setVars(array(
                    'success' => $success,
                    'row' => array(
                        'id' => $new_attach_id,
                        'url' => $url
                    )
                ));
                return;
            }
            else if($data_type == 'base64')
            {
                $data_string = base64_encode(file_get_contents($path));
                unlink($path); //读取内容后删除文件系统文件, 因为直接存数据库

                $new_attach_id = Attachment::addAttachment($data_string, $file_name, $mime_type, 'base64');

                $this->view->setVars(array(
                    'success' => $success,
                    'row' => array(
                        'id' => $new_attach_id
                    )
                ));
            }
            else if($data_type == 'binary')
            {
                $f = fopen($path);
                $data_bin = freah($f, $file_size);
                fclose($f);
                unlink($path); 

                $new_attach_id = Attachment::addAttachment($data_bin, $file_name, $mime_type, 'binary');

                $this->view->setVars(array(
                    'success' => $success,
                    'row' => array(
                        'id' => $new_attach_id
                    )
                ));
            }
        }

        $this->view->setVars(array(
            'success' => false
        ));      
    }

    private static function _dir()
    {
        return __DIR__.self::$upload_dir;
    }
}