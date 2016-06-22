<?php
use \Phalcon\Db;

class Attachment extends ModelEx
{
	/**
	 * 添加附件
	 * @param        $data 附件数据
	 * @param null   $name 附件名称
	 * @param        $mime_type 附件mime类型
	 * @param        $data_type 附件数据类型 (url, base64, binary)
	 * @return bool
	 */
	public static function addAttachment($data, $name=null, $mime_type='text', $data_type='url')
	{
		$sql = '';

		if($data_type == 'binary')
		{
			$sql = 'insert into Attachment (name, mime_type, data_type, data_bin) values(:name, :mime_type, 'binary', :data)';
		}
		else
		{
			$sql = 'insert into Attachment (name, mime_type, data_type, data_string) values (:name, :mime_type, :data_type, :data)';
		}

		$bind = array(
			'name' => $name,
			'mime_type' => $mime_type,
			'data_type' => $data_type,
			'data' => $data
		);

		$success = self::nativeExecute($sql, $bind);

		$db = self::_getConnection();

		return $success ? $db->lastInsertId() : false;
	}

	/**
	 * 获取指定ID附件
	 * @param  $id
	 * @return array
	 */
	public static function getAttachmentById($id)
	{
		$sql = 'select id, name, mime_type, data_type, data_string, data_bin from Attachment where id = :id';
		$bind = array(
			'id' => $id
		);

		return self::nativeQuery($sql, $bind);
	}
}