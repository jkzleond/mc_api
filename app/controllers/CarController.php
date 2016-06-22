<?php
class CarController extends ControllerBase
{
	public function getCarInfoByUserIdAndHphmAction($user_id, $hphm)
	{
		$car_info = CarInfo::getCarInfoByUserIdAndHphm($user_id, $hphm);

		$this->view->setVars(array(
			'row' => $car_info
		));
	}
}